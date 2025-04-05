<?php
require_once '../config/database.php';
require_once '../config/chatwork.php'; // 新しく追加したChatwork設定ファイル

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (empty($_POST['project_id']) || empty($_POST['content']) || empty($_POST['author'])) {
        throw new Exception('Missing required fields');
    }

    // トランザクション開始
    $pdo->beginTransaction();

    // 履歴の追加
    $stmt = $pdo->prepare("INSERT INTO project_history (project_id, content, author) VALUES (?, ?, ?)");
    $stmt->execute([
        $_POST['project_id'],
        $_POST['content'],
        $_POST['author']
    ]);

    // プロジェクトの更新日時を更新
    $stmt = $pdo->prepare("UPDATE projects SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$_POST['project_id']]);

    // コミット
    $pdo->commit();

    // プロジェクト情報の取得（Chatwork通知用）
    $stmt = $pdo->prepare("
        SELECT p.name, p.team_members, 
               CASE WHEN p.parent_id IS NOT NULL THEN 
                   (SELECT name FROM projects WHERE id = p.parent_id) 
               ELSE NULL END as parent_name
        FROM projects p 
        WHERE p.id = ?
    ");
    $stmt->execute([$_POST['project_id']]);
    $projectInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    // Chatworkへの通知処理
    if ($projectInfo) {
        $projectName = $projectInfo['name'];
        $parentName = $projectInfo['parent_name'];
        $fullProjectName = $parentName ? "$parentName > $projectName" : $projectName;
        
        // チームメンバーの取得
        $teamMembers = [];
        if (!empty($projectInfo['team_members'])) {
            $teamMembers = json_decode($projectInfo['team_members'], true) ?: [];
        }
        
        // 親プロジェクトのチームメンバーも取得（子プロジェクトの場合）
        if ($parentName) {
            $stmt = $pdo->prepare("SELECT team_members FROM projects WHERE name = ?");
            $stmt->execute([$parentName]);
            $parentInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!empty($parentInfo['team_members'])) {
                $parentMembers = json_decode($parentInfo['team_members'], true) ?: [];
                // 重複を避けるためマージ
                $teamMembers = array_unique(array_merge($teamMembers, $parentMembers));
            }
        }
        
        // メッセージ作成
        $message = "【{$fullProjectName}】に新しいコメントが追加されました\n\n";
        $message .= "投稿者: {$_POST['author']}\n";
        $message .= "コメント内容:\n{$_POST['content']}";
        
        // Chatworkへ通知
        if (!empty($teamMembers)) {
            // チームメンバーにTO指定で通知
            $memberIds = implode(',', $teamMembers);
            sendChatworkNotification($memberIds, $message);
        } else {
            // チームメンバーが設定されていない場合はグループ全体に通知
            sendChatworkNotification('', $message);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Progress added successfully'
    ]);

} catch (Exception $e) {
    // エラー発生時はロールバック
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Chatwork APIのconfig/chatwork.phpがない場合のためのフォールバック関数
 * 実際の実装ではconfig/chatwork.phpにこの関数を配置することを推奨
 */
if (!function_exists('sendChatworkNotification')) {
    function sendChatworkNotification($to, $message) {
        // APIトークンとルームIDを設定（実際の値に置き換えてください）
        $apiToken = 'YOUR_CHATWORK_API_TOKEN'; // ここに発行されたAPIトークンを設定
        $roomId = 'YOUR_CHATWORK_ROOM_ID';     // ここにプロジェクト管理グループのルームIDを設定
        
        // TO指定がある場合は追加
        $toPrefix = '';
        if (!empty($to)) {
            $toMembers = explode(',', $to);
            foreach ($toMembers as $member) {
                $toPrefix .= "[To:" . trim($member) . "]";
            }
            $toPrefix .= "\n";
        }
        
        $fullMessage = $toPrefix . $message;
        
        // Chatwork APIリクエスト
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.chatwork.com/v2/rooms/{$roomId}/messages");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['body' => $fullMessage]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-ChatWorkToken: {$apiToken}",
            "Content-Type: application/x-www-form-urlencoded"
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // エラーログ記録（必要に応じて）
        if ($httpCode < 200 || $httpCode >= 300) {
            error_log("Chatwork API Error: HTTP Code $httpCode, Error: $error, Response: $response");
            return false;
        }
        
        return true;
    }
}
?>