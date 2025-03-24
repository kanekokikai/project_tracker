<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    // 認証チェック
    if (!isAuthenticated()) {
        throw new Exception('認証が必要です');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (empty($_POST['name']) || empty($_POST['parent_id'])) {
        throw new Exception('Project name and parent ID are required');
    }
    
    // 作成者名の取得（必須）
    if (empty($_POST['author'])) {
        throw new Exception('Author name is required');
    }
    $author = $_POST['author'];

    // トランザクション開始
    $pdo->beginTransaction();

    // 子プロジェクト追加（ステータスと日時を明示的に設定）
    $stmt = $pdo->prepare("INSERT INTO projects (name, parent_id, status, created_at, updated_at) VALUES (?, ?, '未着手', NOW(), NOW())");
    $stmt->execute([$_POST['name'], $_POST['parent_id']]);
    
    // 追加された子プロジェクトのIDを取得
    $subProjectId = $pdo->lastInsertId();
    
    // 履歴に子プロジェクト作成の記録を追加（親プロジェクトの履歴として記録）
    $content = "サブプロジェクト「{$_POST['name']}」を作成しました";
    $stmt = $pdo->prepare("INSERT INTO project_history (project_id, author, content, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([
        $_POST['parent_id'],  // 親プロジェクトの履歴として記録
        $author,              // 固定値から変数に変更
        $content
    ]);
    
    // 親プロジェクトの更新日時を更新
    $stmt = $pdo->prepare("UPDATE projects SET updated_at = NOW() WHERE id = ?");
    $stmt->execute([$_POST['parent_id']]);
    
    // トランザクションをコミット
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Sub-project added successfully'
    ]);

} catch (Exception $e) {
    // エラー発生時はロールバック
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}