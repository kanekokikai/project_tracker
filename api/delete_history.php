<?php
require_once __DIR__ . '/../config/database.php';

// POSTリクエストからデータを取得
$historyId = isset($_POST['history_id']) ? intval($_POST['history_id']) : 0;

$response = ['success' => false, 'message' => ''];

// 入力チェック
if (empty($historyId)) {
    $response['message'] = '履歴IDが指定されていません';
} else {
    try {
        // 履歴の削除
        $stmt = $pdo->prepare("DELETE FROM project_history WHERE id = ?");
        $result = $stmt->execute([$historyId]);
        
        if ($result) {
            $response['success'] = true;
            $response['message'] = '履歴が削除されました';
        } else {
            $response['message'] = '履歴の削除に失敗しました';
        }
    } catch (PDOException $e) {
        $response['message'] = 'エラーが発生しました: ' . $e->getMessage();
    }
}

// JSONとしてレスポンスを返す
header('Content-Type: application/json');
echo json_encode($response);