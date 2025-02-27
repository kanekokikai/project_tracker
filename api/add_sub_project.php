<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (empty($_POST['name']) || empty($_POST['parent_id'])) {
        throw new Exception('Project name and parent ID are required');
    }

    $stmt = $pdo->prepare("INSERT INTO projects (name, parent_id) VALUES (?, ?)");
    $stmt->execute([$_POST['name'], $_POST['parent_id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Sub-project added successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}