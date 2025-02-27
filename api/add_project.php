<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (empty($_POST['name'])) {
        throw new Exception('Project name is required');
    }

    $stmt = $pdo->prepare("INSERT INTO projects (name) VALUES (?)");
    $stmt->execute([$_POST['name']]);

    echo json_encode([
        'success' => true,
        'message' => 'Project added successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}