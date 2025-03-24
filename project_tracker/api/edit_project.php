<?php
// api/edit_project.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// 認証確認
if (!isAuthenticated()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '認証されていません']);
    exit;
}

// POSTリクエストの確認
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '不正なリクエストメソッド']);
    exit;
}

// 必要なパラメータの確認
if (!isset($_POST['project_id']) || !isset($_POST['name']) || empty($_POST['name'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '必要なパラメータがありません']);
    exit;
}

$projectId = $_POST['project_id'];
$newName = $_POST['name'];

$teamMembers = isset($_POST['team_members']) ? $_POST['team_members'] : '[]';

try {
    // プロジェクト名を更新
    $stmt = $pdo->prepare("UPDATE projects SET name = ?, team_members = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newName, $teamMembers, $projectId]);
        
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>