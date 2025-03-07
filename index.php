<?php
// キャッシュ制御ヘッダーを追加
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

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

// 親プロジェクトの取得（parent_idがNULLのもの）- 最適化: 必要なカラムだけを取得
$stmt = $pdo->query("SELECT id, name, status, updated_at FROM projects WHERE parent_id IS NULL ORDER BY updated_at DESC");
$parentProjects = $stmt->fetchAll();

// 親プロジェクトのIDリストを作成
$parentIds = array_column($parentProjects, 'id');

// 子プロジェクトの取得 - 最適化: IN句を使用して一度に取得
$childProjects = [];
if (!empty($parentIds)) {
    $placeholders = str_repeat('?,', count($parentIds) - 1) . '?';
    $stmt = $pdo->prepare("SELECT id, parent_id, name, status, updated_at FROM projects WHERE parent_id IN ($placeholders) ORDER BY parent_id, updated_at DESC");
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

// 履歴の取得 - 最適化: サブクエリの代わりにLIMITを使用したクエリ
$histories = [];
if (!empty($allProjectIds)) {
    // 各プロジェクトごとにクエリを実行するが、制限を付ける（最新3件のみ）
    foreach ($allProjectIds as $projectId) {
        $stmt = $pdo->prepare("
            SELECT project_id, author, status, content, created_at
            FROM project_history
            WHERE project_id = ?
            ORDER BY created_at DESC
            LIMIT 3
        ");
        $stmt->execute([$projectId]);
        $histories[$projectId] = $stmt->fetchAll();
    }
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
<!-- ... -->

<?php include 'includes/footer.php'; ?>

<!-- モーダル部分 -->
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
                <input type="text" id="progressAuthor" name="author" class="form-control" required style="width: 100% !important; padding: 0.8rem !important; font-size: 1rem !important; box-sizing: border-box !important;">
            </div>
            <div class="form-group">
                <label for="progressContent">進捗内容</label>
                <textarea id="progressContent" name="content" class="form-control" required style="width: 100% !important; min-height: 150px !important; padding: 0.8rem !important; font-size: 1rem !important; box-sizing: border-box !important;"></textarea>
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