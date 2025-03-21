<?php
// api/update_status.php
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
if (!isset($_POST['project_id']) || !isset($_POST['status'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '必要なパラメータがありません']);
    exit;
}

$projectId = $_POST['project_id'];
$newStatus = $_POST['status'];

// ステータスの検証（必要に応じて追加）
$validStatuses = ["未着手", "進行中", "レビュー中", "保留中", "完了", "中止"];
if (!in_array($newStatus, $validStatuses)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '無効なステータス']);
    exit;
}

try {
    // トランザクション開始
    $pdo->beginTransaction();
    
    // プロジェクトのステータスを更新
    $stmt = $pdo->prepare("UPDATE projects SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newStatus, $projectId]);
    
    // プロジェクト履歴への記録は行わない（コメントアウトまたは削除）
    // $stmt = $pdo->prepare("INSERT INTO project_history (project_id, status, created_at) VALUES (?, ?, NOW())");
    // $stmt->execute([$projectId, $newStatus]);
    
    // トランザクションをコミット
    $pdo->commit();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    // エラー発生時はロールバック
    $pdo->rollBack();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>