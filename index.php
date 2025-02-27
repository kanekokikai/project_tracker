<?php
require_once 'config/database.php';

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

// 子プロジェクトの取得
$childProjects = [];
foreach ($parentProjects as $parent) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE parent_id = ? ORDER BY updated_at DESC");
    $stmt->execute([$parent['id']]);
    $childProjects[$parent['id']] = $stmt->fetchAll();
}

// 最新の履歴を取得（各プロジェクトの最新3件）
$histories = [];
foreach ($parentProjects as $project) {
    $stmt = $pdo->prepare("
        SELECT * FROM project_history 
        WHERE project_id = ? 
        ORDER BY created_at DESC 
        LIMIT 3
    ");
    $stmt->execute([$project['id']]);
    $histories[$project['id']] = $stmt->fetchAll();

    // 子プロジェクトの履歴も取得
    if (isset($childProjects[$project['id']])) {
        foreach ($childProjects[$project['id']] as $child) {
            $stmt = $pdo->prepare("
                SELECT * FROM project_history 
                WHERE project_id = ? 
                ORDER BY created_at DESC 
                LIMIT 3
            ");
            $stmt->execute([$child['id']]);
            $histories[$child['id']] = $stmt->fetchAll();
        }
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
                            <?php if ($hist['status']): ?>
                                <div class="status-change">
                                    ステータスを「<?= htmlspecialchars($hist['status']) ?>」に変更
                                </div>
                            <?php endif; ?>
                            <?php if ($hist['content']): ?>
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
                        <div class="project-card child-project">
                            <div class="project-header">
                                <h3 class="project-title">
                                    <?= htmlspecialchars($childProject['name']) ?>
                                </h3>
                                <div class="project-actions">
                                    <span class="status-badge status-<?= $childProject['status'] ?>">
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
                                <div class="project-history">
                                    <?php foreach ($histories[$childProject['id']] as $hist): ?>
                                        <div class="history-item">
                                            <div class="history-header">
                                                <span class="author"><?= htmlspecialchars($hist['author']) ?></span>
                                                <span class="date">
                                                    <?= date('Y/m/d H:i', strtotime($hist['created_at'])) ?>
                                                </span>
                                            </div>
                                            <?php if ($hist['status']): ?>
                                                <div class="status-change">
                                                    ステータスを「<?= htmlspecialchars($hist['status']) ?>」に変更
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($hist['content']): ?>
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

<?php include 'includes/footer.php'; ?>