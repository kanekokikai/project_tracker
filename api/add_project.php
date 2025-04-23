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

    if (empty($_POST['name'])) {
        throw new Exception('Project name is required');
    }
    
    // 作成者名の取得（必須）
    if (empty($_POST['author'])) {
        throw new Exception('Author name is required');
    }
    $author = $_POST['author'];

    // チームメンバー情報の取得（オプション）
    $teamMembers = isset($_POST['team_members']) ? $_POST['team_members'] : '[]';
    
    // 部署情報の取得（オプション）
    $department = isset($_POST['department']) ? $_POST['department'] : '選択なし';

    // トランザクション開始
    $pdo->beginTransaction();

    // プロジェクト追加
    $stmt = $pdo->prepare("INSERT INTO projects (name, status, team_members, department, created_at, updated_at) VALUES (?, '未着手', ?, ?, NOW(), NOW())");
    $stmt->execute([$_POST['name'], $teamMembers, $department]);    

    // 追加されたプロジェクトのIDを取得
    $projectId = $pdo->lastInsertId();
    
    // 履歴にプロジェクト作成の記録を追加
    $content = "新規プロジェクト「{$_POST['name']}」を作成しました";
    $stmt = $pdo->prepare("INSERT INTO project_history (project_id, author, content, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([
        $projectId,
        $author,
        $content
    ]);
    
    // トランザクションをコミット
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Project added successfully'
    ]);

} catch (Exception $e) {
    // エラー発生時はロールバック
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}