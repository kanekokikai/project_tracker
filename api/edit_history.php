<?php
require_once __DIR__ . '/../config/database.php';

// POSTリクエストからデータを取得
$historyId = isset($_POST['history_id']) ? intval($_POST['history_id']) : 0;
$author = isset($_POST['author']) ? $_POST['author'] : '';
$content = isset($_POST['content']) ? $_POST['content'] : '';

$response = ['success' => false, 'message' => ''];

// 入力チェック
if (empty($historyId)) {
    $response['message'] = '履歴IDが指定されていません';
} else if (empty($author)) {
    $response['message'] = '名前を入力してください';
} else {
    try {
        // 履歴の更新
        $stmt = $pdo->prepare("UPDATE project_history SET author = ?, content = ? WHERE id = ?");
        $result = $stmt->execute([$author, $content, $historyId]);
        
        if ($result) {
            $response['success'] = true;
            $response['message'] = '履歴が更新されました';
        } else {
            $response['message'] = '履歴の更新に失敗しました';
        }
    } catch (PDOException $e) {
        $response['message'] = 'エラーが発生しました: ' . $e->getMessage();
    }
}

// JSONとしてレスポンスを返す
header('Content-Type: application/json');
echo json_encode($response);