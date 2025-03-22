<?php

// キャッシュ制御ヘッダーを追加
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// 認証状態を確認
$isAuth = isAuthenticated();

// ステータスの選択肢を定義
$statusOptions = [
    "未着手",
    "進行中",
    "レビュー中",
    "保留中", 
    "完了",
    "中止"
];


// 最新の履歴を持つプロジェクト順に親プロジェクトを取得
try {
    // まず、親プロジェクトとその子プロジェクトの最新履歴を取得
    // より互換性のあるクエリを使用
    $stmt = $pdo->query("
        SELECT p.id, p.name, p.status, p.updated_at, p.team_members,
               IFNULL(
                   (SELECT MAX(h.created_at)
                    FROM project_history h
                    LEFT JOIN projects sp ON h.project_id = sp.id
                    WHERE h.project_id = p.id 
                       OR sp.parent_id = p.id
                   ), '1900-01-01'
               ) as latest_history_date
        FROM projects p
        WHERE p.parent_id IS NULL
        ORDER BY latest_history_date DESC, p.updated_at DESC
    ");
    $parentProjects = $stmt->fetchAll();
} catch (PDOException $e) {
    // エラーが発生した場合は、より単純なクエリを試す
    error_log('Advanced query failed: ' . $e->getMessage());
    $stmt = $pdo->query("SELECT id, name, status, updated_at, team_members FROM projects WHERE parent_id IS NULL ORDER BY updated_at DESC");
    $parentProjects = $stmt->fetchAll();
}


// 親プロジェクトのIDリストを作成
$parentIds = array_column($parentProjects, 'id');


// 子プロジェクトの取得 - 最適化: IN句を使用して一度に取得
$childProjects = [];
if (!empty($parentIds)) {
    $placeholders = str_repeat('?,', count($parentIds) - 1) . '?';
    $stmt = $pdo->prepare("SELECT id, parent_id, name, status, updated_at, team_members FROM projects WHERE parent_id IN ($placeholders) ORDER BY parent_id, updated_at DESC");
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
            SELECT id, project_id, author, status, content, created_at
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
    
    <div class="search-wrapper">
        <div class="search-bar">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="memberSearch" class="search-input" placeholder="名前で検索..." autocomplete="off">
            <label class="toggle-switch">
                <input type="checkbox" id="searchModeToggle">
                <span class="toggle-slider"></span>
            </label>
            <i class="fas fa-times-circle clear-search" id="clearSearch" style="display: none;"></i>
        </div>
    </div>
    
    <div class="filter-wrapper">
        <select id="statusFilter" class="form-control" onchange="filterByStatus(this.value)">
            <option value="all">すべてのステータス</option>
            <?php foreach ($statusOptions as $status): ?>
                <option value="<?= $status ?>"><?= $status ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>


    <!-- プロジェクト一覧 -->
    <?php foreach ($parentProjects as $project): ?>
        <div class="project-card parent-project">
            <div class="project-header">
                <div class="title-section">

<!-- 親プロジェクトのタイトル部分を修正 -->
<h2 class="project-title">
    <span class="attachment-icon" data-project-id="<?= $project['id'] ?>">
        <i class="fas fa-paperclip"></i>
    </span>


    <span class="project-name">
    <?= htmlspecialchars($project['name']) ?>
    <?php if (!empty($project['team_members'])): ?>
        <span class="team-members-tooltip">
            <?php 
            $members = json_decode($project['team_members'], true) ?: [];
            foreach ($members as $index => $member): ?>
                <span class="team-member-name"><?= htmlspecialchars($member) ?></span>
            <?php endforeach; ?>
        </span>
    <?php endif; ?>
</span>


    <i class="fas fa-plus-circle action-icon add-sub-project" onclick="openSubProjectModal('<?= $project['id'] ?>')" data-tooltip="サブプロジェクト追加"></i>
    <i class="fas fa-comment action-icon" onclick="openProgressModal(<?= $project['id'] ?>)" data-tooltip="コメント追加"></i>
</h2>


                </div>
<!-- 親プロジェクトのボタン部分を修正 -->
<div class="project-actions">
    <span class="status-badge status-<?= $project['status'] ?> clickable-status" 
          data-project-id="<?= $project['id'] ?>" 
          onclick="showStatusDropdown(this, event)">
        <?= htmlspecialchars($project['status']) ?>
        <i class="fas fa-caret-down status-caret"></i>
    </span>
    <i class="fas fa-book-open action-icon" onclick="openHistoryModal(<?= $project['id'] ?>)" data-tooltip="すべての履歴"></i>
    <i class="fas fa-trash-alt action-icon delete-icon" onclick="confirmDelete(<?= $project['id'] ?>)" data-tooltip="削除"></i>
    <i class="fas fa-edit action-icon" onclick="openEditProjectModal(<?= $project['id'] ?>, '<?= htmlspecialchars(addslashes($project['name'])) ?>')" data-tooltip="プロジェクト名編集"></i>
</div>


            </div>

<!-- 親プロジェクトの履歴表示 -->
<?php if (!empty($histories[$project['id']])): ?>
    <div class="project-history">
        <?php foreach ($histories[$project['id']] as $hist): ?>
            <div class="history-item" data-history-id="<?= $hist['id'] ?>">
                <!-- 作成者アイコン（2文字表示） -->
                <div class="author-avatar" data-author-name="<?= htmlspecialchars($hist['author']) ?>">
                    <?= mb_substr(htmlspecialchars($hist['author']), 0, 2) ?>
                </div>
                
                <!-- コメント吹き出し - 名前を削除 -->
                <?php if (!empty($hist['status'])): ?>
                    <div class="bubble status-bubble">
                        <div class="content">
                            ステータスを「<?= htmlspecialchars($hist['status']) ?>」に変更
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($hist['content'])): ?>
                    <div class="bubble">
                        <div class="content <?= (mb_strlen($hist['content']) > 100) ? 'expandable' : '' ?>" 
                             id="content-<?= $hist['id'] ?>">
                            <?= nl2br(htmlspecialchars($hist['content'])) ?>
                        </div>
                        <?php if (mb_strlen($hist['content']) > 100): ?>
                            <div class="content-toggle" onclick="toggleContent('content-<?= $hist['id'] ?>')">
                                続きを読む
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- 日付と編集/削除ボタン -->
                <div class="date-actions">
                    <span class="date"><?= date('Y/m/d H:i', strtotime($hist['created_at'])) ?></span>
                    <div class="inline-actions">
                    <i class="fas fa-edit mini-btn edit-btn" onclick="openEditHistoryModal(<?= $hist['id'] ?>)" title="編集"></i>
                    <i class="fas fa-trash-alt mini-btn delete-btn" onclick="confirmDeleteHistory(<?= $hist['id'] ?>)" title="削除"></i>                    
                    </div>
                </div>
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
      
<!-- 子プロジェクトのタイトル部分を修正 -->
<h3 class="project-title">
    <span class="attachment-icon" data-project-id="<?= $childProject['id'] ?>">
        <i class="fas fa-paperclip"></i>
    </span>


    <span class="project-name">
    <?= htmlspecialchars($childProject['name']) ?>
    <?php if (!empty($childProject['team_members'])): ?>
        <span class="team-members-tooltip">
            <?php 
            $members = json_decode($childProject['team_members'], true) ?: [];
            foreach ($members as $index => $member): ?>
                <span class="team-member-name"><?= htmlspecialchars($member) ?></span>
            <?php endforeach; ?>
        </span>
    <?php endif; ?>
</span>


    <span class="toggle-history" data-project-id="<?= $childProject['id'] ?>" 
  onclick="
    var content = document.getElementById('history-content-<?= $childProject['id'] ?>');
    if (content) {
      content.style.cssText = content.style.display === 'none' ? 'display: block !important;' : 'display: none !important;';
      this.textContent = content.style.display === 'none' ? '▶' : '▼';
    }
    return false;
  ">
    <?= ($childProject['status'] === '完了') ? '▶' : '▼' ?>
</span>
    <i class="fas fa-comment action-icon" onclick="openProgressModal(<?= $childProject['id'] ?>)" data-tooltip="コメント追加"></i>
</h3>


<!-- 子プロジェクトのボタン部分 -->
<div class="project-actions">
    <span class="status-badge status-<?= htmlspecialchars($childProject['status']) ?> clickable-status" 
          data-project-id="<?= $childProject['id'] ?>" 
          onclick="showStatusDropdown(this, event)">
        <?= htmlspecialchars($childProject['status']) ?>
        <i class="fas fa-caret-down status-caret"></i>
    </span>
    <i class="fas fa-book-open action-icon" onclick="openHistoryModal(<?= $childProject['id'] ?>)" data-tooltip="すべての履歴"></i>
    <i class="fas fa-trash-alt action-icon delete-icon" onclick="confirmDelete(<?= $childProject['id'] ?>)" data-tooltip="削除"></i>
    <i class="fas fa-edit action-icon" onclick="openEditProjectModal(<?= $childProject['id'] ?>, '<?= htmlspecialchars(addslashes($childProject['name'])) ?>')" data-tooltip="プロジェクト名編集"></i>
</div>


    </div>                            

<!-- 子プロジェクトの履歴表示 -->
<?php if (!empty($histories[$childProject['id']])): ?>
    <div id="history-content-<?= $childProject['id'] ?>" 
        class="project-history <?= ($childProject['status'] === '完了') ? 'collapsed' : '' ?>"
        style="<?= ($childProject['status'] === '完了') ? 'display: none;' : 'display: block;' ?>">
        <?php foreach ($histories[$childProject['id']] as $hist): ?>
            <div class="history-item" data-history-id="<?= $hist['id'] ?>">
                <!-- 作成者アイコン（2文字表示） -->
                <div class="author-avatar" data-author-name="<?= htmlspecialchars($hist['author']) ?>">
                    <?= mb_substr(htmlspecialchars($hist['author']), 0, 2) ?>
                </div>
                
                <!-- コメント吹き出し - 名前を削除 -->
                <?php if (!empty($hist['status'])): ?>
                    <div class="bubble status-bubble">
                        <div class="content">
                            ステータスを「<?= htmlspecialchars($hist['status']) ?>」に変更
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($hist['content'])): ?>
                    <div class="bubble">
                        <div class="content <?= (mb_strlen($hist['content']) > 100) ? 'expandable' : '' ?>" 
                             id="content-child-<?= $hist['id'] ?>">
                            <?= nl2br(htmlspecialchars($hist['content'])) ?>
                        </div>
                        <?php if (mb_strlen($hist['content']) > 100): ?>
                            <div class="content-toggle" onclick="toggleContent('content-child-<?= $hist['id'] ?>')">
                                続きを読む
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- 日付と編集/削除ボタン -->
                <div class="date-actions">
                    <span class="date"><?= date('Y/m/d H:i', strtotime($hist['created_at'])) ?></span>
                    <div class="inline-actions">
                    <i class="fas fa-edit mini-btn edit-btn" onclick="openEditHistoryModal(<?= $hist['id'] ?>)" title="編集"></i>
                    <i class="fas fa-trash-alt mini-btn delete-btn" onclick="confirmDeleteHistory(<?= $hist['id'] ?>)" title="削除"></i>                    
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php else: ?>
    <!-- 履歴がない場合は何も表示しない -->
    <div id="history-content-<?= $childProject['id'] ?>" 
        class="project-history <?= ($childProject['status'] === '完了') ? 'collapsed' : '' ?>"
        style="<?= ($childProject['status'] === '完了') ? 'display: none;' : 'display: block;' ?>">
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
                <label for="projectAuthor">作成者</label>
                <input type="text" id="projectAuthor" name="author" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="teamMemberInput">チームメンバー</label>
                <div id="teamMemberTags" class="team-member-tags"></div>
                <input type="text" id="teamMemberInput" class="form-control" placeholder="名前を入力してEnterで追加">
                <input type="hidden" id="teamMembers" name="team_members" value="">
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
                <label for="subProjectAuthor">作成者</label>
                <input type="text" id="subProjectAuthor" name="author" class="form-control" required>
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
            <!-- ここにコメント欄を追加 -->
            <div class="form-group">
                <label for="statusComment">コメント (任意)</label>
                <textarea id="statusComment" name="comment" class="form-control"></textarea>
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

<!-- 履歴編集モーダル -->
<div id="editHistoryModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>進捗内容の編集</h3>
        <form id="editHistoryForm">
            <input type="hidden" id="editHistoryId" name="history_id">
            <div class="form-group">
                <label for="editHistoryAuthor">名前</label>
                <input type="text" id="editHistoryAuthor" name="author" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="editHistoryContent">進捗内容</label>
                <textarea id="editHistoryContent" name="content" class="form-control"></textarea>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">更新</button>
                <button type="button" class="btn" onclick="closeEditHistoryModal()">キャンセル</button>
            </div>
        </form>
    </div>
</div>

<!-- ステータスドロップダウン (index.phpのbody終了前に追加) -->
<div id="status-dropdown" class="status-dropdown" style="display:none;">
    <?php foreach ($statusOptions as $status): ?>
        <div class="status-option" data-status="<?= $status ?>"><?= $status ?></div>
    <?php endforeach; ?>
</div>

<!-- プロジェクト名編集モーダル -->
<div id="editProjectModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>プロジェクト編集</h3>
        <form id="editProjectForm">
            <input type="hidden" id="editProjectId" name="project_id">
            <div class="form-group">
                <label for="editProjectName">プロジェクト名</label>
                <input type="text" id="editProjectName" name="name" class="form-control" required>
            </div>


<div class="form-group">
    <label for="editTeamMemberInput">チームメンバー</label>
    <div id="editTeamMemberTags" class="team-member-tags"></div>
    <input type="text" id="editTeamMemberInput" class="form-control" placeholder="名前を入力してEnterで追加">
    <input type="hidden" id="editTeamMembers" name="team_members" value="">
</div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">更新</button>
                <button type="button" class="btn" onclick="closeEditProjectModal()">キャンセル</button>
            </div>
        </form>
    </div>
</div>