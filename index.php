<?php
require_once __DIR__ . '/config/database.php';

// ステータスの選択肢を定義
$statusOptions = [
    "未着手",
    "進行中",
    "レビュー中",
    "保留中",
    "完了",
    "中止"
];

// 親プロジェクトの取得（parent_idがNULLのもの）
$stmt = $pdo->query("SELECT * FROM projects WHERE parent_id IS NULL ORDER BY updated_at DESC");
$parentProjects = $stmt->fetchAll();

// 親プロジェクトのIDリストを作成
$parentIds = array_column($parentProjects, 'id');

// 一度に全ての子プロジェクトを取得（IN句を使用）
$childProjects = [];
if (!empty($parentIds)) {
    $placeholders = str_repeat('?,', count($parentIds) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE parent_id IN ($placeholders) ORDER BY parent_id, updated_at DESC");
    $stmt->execute($parentIds);
    $allChildProjects = $stmt->fetchAll();
    
    // 親プロジェクトIDでグループ化
    foreach ($allChildProjects as $child) {
        $childProjects[$child['parent_id']][] = $child;
    }
}

// すべてのプロジェクトIDs（親+子）を取得
$allProjectIds = $parentIds;
foreach ($childProjects as $children) {
    foreach ($children as $child) {
        $allProjectIds[] = $child['id'];
    }
}

// 一度に全ての履歴を取得（プロジェクトごとに最新3件）
$histories = [];
if (!empty($allProjectIds)) {
    // この部分はMySQLの例です。他のDBでは異なる実装が必要な場合があります
    $placeholders = str_repeat('?,', count($allProjectIds) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT h.* FROM project_history h
        INNER JOIN (
            SELECT project_id, created_at
            FROM project_history
            WHERE project_id IN ($placeholders)
            ORDER BY project_id, created_at DESC
        ) AS ranked 
        ON h.project_id = ranked.project_id AND h.created_at = ranked.created_at
        ORDER BY h.project_id, h.created_at DESC
    ");
    $stmt->execute($allProjectIds);
    $allHistories = $stmt->fetchAll();
    
    // プロジェクトIDごとにグループ化し、最大3件のみ保持
    $tempHistories = [];
    foreach ($allHistories as $history) {
        $projectId = $history['project_id'];
        if (!isset($tempHistories[$projectId])) {
            $tempHistories[$projectId] = [];
        }
        if (count($tempHistories[$projectId]) < 3) {
            $tempHistories[$projectId][] = $history;
        }
    }
    $histories = $tempHistories;
}

include 'includes/header.php';
?>

<div class="project-list">
    <!-- フィルターとプロジェクト追加ボタン -->
    <div class="action-buttons">
        <button class="btn btn-primary" onclick="openAddProjectModal()">新規プロジェクト</button>
        <select id="statusFilter" class="form-control" onchange="filterByStatus(this.value)">
            <option value="all">すべてのステータス</option>
            <?php foreach ($statusOptions as $status): ?>
                <option value="<?= $status ?>"><?= $status ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- プロジェクト一覧 -->
    <?php foreach ($parentProjects as $project): ?>
        <div class="project-card parent-project">
            <div class="project-header">
                <div class="title-section">
                    <h2 class="project-title">
                        <?= htmlspecialchars($project['name']) ?>
                        <button class="btn btn-success btn-sm add-sub-project" onclick="openSubProjectModal('<?= $project['id'] ?>')">＋</button>
                    </h2>
                </div>
                <div class="project-actions">
                    <span class="status-badge status-<?= $project['status'] ?>">
                        <?= htmlspecialchars($project['status']) ?>
                    </span>
                    <button class="btn btn-primary" onclick="openProgressModal(<?= $project['id'] ?>)">進捗追加</button>
                    <button class="btn btn-primary" onclick="openStatusModal(<?= $project['id'] ?>)">ステータス変更</button>
                    <button class="btn btn-info" onclick="openHistoryModal(<?= $project['id'] ?>)">すべての履歴</button>
                    <button class="btn btn-danger" onclick="confirmDelete(<?= $project['id'] ?>)">削除</button>
                </div>
            </div>

            <!-- 親プロジェクトの履歴表示 -->
            <?php if (!empty($histories[$project['id']])): ?>
                <div class="project-history">
                    <?php foreach ($histories[$project['id']] as $hist): ?>
                        <div class="history-item">
                            <div class="history-header">
                                <span class="author"><?= htmlspecialchars($hist['author']) ?></span>
                                <span class="date">
                                    <?= date('Y/m/d H:i', strtotime($hist['created_at'])) ?>
                                </span>
                            </div>
                            <?php if (!empty($hist['status'])): ?>
                                <div class="status-change">
                                    ステータスを「<?= htmlspecialchars($hist['status']) ?>」に変更
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($hist['content'])): ?>
                                <div class="content">
                                    <?= nl2br(htmlspecialchars($hist['content'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- 子プロジェクト表示 -->
            <?php if (isset($childProjects[$project['id']]) && !empty($childProjects[$project['id']])): ?>
                <div class="sub-projects">
<!-- 子プロジェクト表示部分のコード修正 -->
<?php foreach ($childProjects[$project['id']] as $childProject): ?>
    <div class="project-card child-project" data-status="<?= htmlspecialchars($childProject['status']) ?>">
        <div class="project-header">
            <h3 class="project-title">
                <?= htmlspecialchars($childProject['name']) ?>
                <span class="toggle-history" data-project-id="<?= $childProject['id'] ?>">
                    <?= ($childProject['status'] === '完了') ? '▶' : '▼' ?>
                </span>
            </h3>
            <div class="project-actions">
                <span class="status-badge status-<?= htmlspecialchars($childProject['status']) ?>">
                    <?= htmlspecialchars($childProject['status']) ?>
                </span>
                <button class="btn btn-primary" onclick="openProgressModal(<?= $childProject['id'] ?>)">進捗追加</button>
                <button class="btn btn-primary" onclick="openStatusModal(<?= $childProject['id'] ?>)">ステータス変更</button>
                <button class="btn btn-info" onclick="openHistoryModal(<?= $childProject['id'] ?>)">すべての履歴</button>
                <button class="btn btn-danger" onclick="confirmDelete(<?= $childProject['id'] ?>)">削除</button>
            </div>
        </div>

        <!-- 子プロジェクトの履歴表示 -->
        <?php if (!empty($histories[$childProject['id']])): ?>
            <div id="history-content-<?= $childProject['id'] ?>" 
                 class="project-history <?= ($childProject['status'] === '完了') ? 'collapsed' : '' ?>"
                 style="<?= ($childProject['status'] === '完了') ? 'display: none;' : 'display: block;' ?>">
                <?php foreach ($histories[$childProject['id']] as $hist): ?>
                    <div class="history-item">
                        <div class="history-header">
                            <span class="author"><?= htmlspecialchars($hist['author']) ?></span>
                            <span class="date">
                                <?= date('Y/m/d H:i', strtotime($hist['created_at'])) ?>
                            </span>
                        </div>
                        <?php if (!empty($hist['status'])): ?>
                            <div class="status-change">
                                ステータスを「<?= htmlspecialchars($hist['status']) ?>」に変更
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($hist['content'])): ?>
                            <div class="content">
                                <?= nl2br(htmlspecialchars($hist['content'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<!-- モーダル部分は変更なし -->
<!-- 新規プロジェクト追加モーダル -->
<div id="addProjectModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>新規プロジェクト追加</h3>
        <form id="addProjectForm">
            <div class="form-group">
                <label for="projectName">プロジェクト名</label>
                <input type="text" id="projectName" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">追加</button>
                <button type="button" class="btn" onclick="closeAddProjectModal()">キャンセル</button>
            </div>
        </form>
    </div>
</div>

<!-- 子プロジェクト追加モーダル -->
<div id="subProjectModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>子プロジェクト追加</h3>
        <form id="addSubProjectForm">
            <input type="hidden" id="parentProjectId" name="parent_id">
            <div class="form-group">
                <label for="subProjectName">プロジェクト名</label>
                <input type="text" id="subProjectName" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">追加</button>
                <button type="button" class="btn" onclick="closeSubProjectModal()">キャンセル</button>
            </div>
        </form>
    </div>
</div>

<!-- 進捗追加モーダル -->
<div id="progressModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>進捗追加</h3>
        <form id="addProgressForm">
            <input type="hidden" id="progressProjectId" name="project_id">
            <div class="form-group">
                <label for="progressAuthor">名前</label>
                <input type="text" id="progressAuthor" name="author" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="progressContent">進捗内容</label>
                <textarea id="progressContent" name="content" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">追加</button>
                <button type="button" class="btn" onclick="closeProgressModal()">キャンセル</button>
            </div>
        </form>
    </div>
</div>

<!-- ステータス変更モーダル -->
<div id="statusModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>ステータス変更</h3>
        <form id="changeStatusForm">
            <input type="hidden" id="statusProjectId" name="project_id">
            <div class="form-group">
                <label for="statusAuthor">名前</label>
                <input type="text" id="statusAuthor" name="author" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="newStatus">新しいステータス</label>
                <select id="newStatus" name="status" class="form-control" required>
                    <?php foreach ($statusOptions as $status): ?>
                        <option value="<?= $status ?>"><?= $status ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">変更</button>
                <button type="button" class="btn" onclick="closeStatusModal()">キャンセル</button>
            </div>
        </form>
    </div>
</div>

<!-- 履歴一覧モーダル -->
<div id="historyModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>履歴一覧</h3>
        <div id="historyList" class="history-list"></div>
        <div class="form-group">
            <button type="button" class="btn" onclick="closeHistoryModal()">閉じる</button>
        </div>
    </div>
</div>

<script>
// 履歴の表示・非表示を切り替える関数
function toggleHistory(projectId) {
    const historyContent = document.getElementById(`history-content-${projectId}`);
    const toggleButton = document.querySelector(`.toggle-history[data-project-id="${projectId}"]`);
    
    if (historyContent) {
        if (historyContent.classList.contains('collapsed')) {
            historyContent.classList.remove('collapsed');
            toggleButton.textContent = '▼';
        } else {
            historyContent.classList.add('collapsed');
            toggleButton.textContent = '▶';
        }
    }
}

// ステータスでフィルタリングする関数
function filterByStatus(status) {
    const childProjects = document.querySelectorAll('.child-project');
    
    childProjects.forEach(project => {
        if (status === 'all' || project.dataset.status === status) {
            project.style.display = 'block';
        } else {
            project.style.display = 'none';
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>