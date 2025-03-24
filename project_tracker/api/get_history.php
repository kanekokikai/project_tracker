<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Invalid request method');
    }

    if (empty($_GET['project_id'])) {
        throw new Exception('Project ID is required');
    }

    $stmt = $pdo->prepare("
        SELECT * FROM project_history 
        WHERE project_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$_GET['project_id']]);
    $history = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'history' => $history
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}