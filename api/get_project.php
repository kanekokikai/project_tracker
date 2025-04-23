<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ?');
        $stmt->execute([$_GET['id']]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($project) {
            echo json_encode(['success' => true, 'project' => $project]);
        } else {
            echo json_encode(['success' => false, 'message' => 'プロジェクトが見つかりません。']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'データベースエラー: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => '無効なリクエストです。']);
}
?>