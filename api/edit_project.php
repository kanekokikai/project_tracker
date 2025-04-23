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

// JSONリクエストを取得
$data = json_decode(file_get_contents('php://input'), true);

// データが無効な場合
if (!$data) {
    // 従来のPOSTリクエストを試行
    $data = $_POST;
}

// 必要なパラメータの確認
if (empty($data['project_id']) || empty($data['name'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '必要なパラメータがありません']);
    exit;
}

$projectId = $data['project_id'];
$newName = $data['name'];

$teamMembers = isset($data['team_members']) ? $data['team_members'] : '[]';
// 部署情報を取得
$department = isset($data['department']) ? $data['department'] : '選択なし';

try {
    // プロジェクト名と部署を更新
    $stmt = $pdo->prepare("UPDATE projects SET name = ?, team_members = ?, department = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newName, $teamMembers, $department, $projectId]);
        
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>