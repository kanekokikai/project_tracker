<?php
require_once __DIR__ . '/../config/database.php';

// GETリクエストからデータを取得
$historyId = isset($_GET['history_id']) ? intval($_GET['history_id']) : 0;

$response = ['success' => false, 'message' => '', 'history' => null];

// 入力チェック
if (empty($historyId)) {
    $response['message'] = '履歴IDが指定されていません';
} else {
    try {
        // 履歴の取得
        $stmt = $pdo->prepare("SELECT id, project_id, author, status, content FROM project_history WHERE id = ?");
        $stmt->execute([$historyId]);
        $history = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($history) {
            $response['success'] = true;
            $response['history'] = $history;
        } else {
            $response['message'] = '指定された履歴が見つかりませんでした';
        }
    } catch (PDOException $e) {
        $response['message'] = 'エラーが発生しました: ' . $e->getMessage();
    }
}

// JSONとしてレスポンスを返す
header('Content-Type: application/json');
echo json_encode($response);