<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (empty($_POST['project_id']) || empty($_POST['status']) || empty($_POST['author'])) {
        throw new Exception('Missing required fields');
    }

    // プロジェクトのステータスを更新
    $stmt = $pdo->prepare("UPDATE projects SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['status'], $_POST['project_id']]);

    // 履歴に記録
    $stmt = $pdo->prepare("INSERT INTO project_history (project_id, status, author) VALUES (?, ?, ?)");
    $stmt->execute([
        $_POST['project_id'],
        $_POST['status'],
        $_POST['author']
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