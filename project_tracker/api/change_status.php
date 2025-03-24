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

    if (empty($_POST['project_id']) || empty($_POST['status']) || empty($_POST['author'])) {
        throw new Exception('Missing required fields');
    }

    // コメントは任意なのでempty()チェックは不要
    $comment = isset($_POST['comment']) ? $_POST['comment'] : null;

    // プロジェクトのステータスと更新日時を更新
    $stmt = $pdo->prepare("UPDATE projects SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$_POST['status'], $_POST['project_id']]);

    // 履歴に記録（コメントも保存）
    $stmt = $pdo->prepare("INSERT INTO project_history (project_id, status, author, content, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([
        $_POST['project_id'],
        $_POST['status'],
        $_POST['author'],
        $comment
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}