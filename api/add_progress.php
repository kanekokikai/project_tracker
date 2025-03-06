<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (empty($_POST['project_id']) || empty($_POST['content']) || empty($_POST['author'])) {
        throw new Exception('Missing required fields');
    }

    $stmt = $pdo->prepare("INSERT INTO project_history (project_id, content, author) VALUES (?, ?, ?)");
    $stmt->execute([
        $_POST['project_id'],
        $_POST['content'],
        $_POST['author']
    ]);

    // プロジェクトの更新日時を更新
    $stmt = $pdo->prepare("UPDATE projects SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$_POST['project_id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Progress added successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}