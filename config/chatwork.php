<?php
// config/chatwork.php

// Chatwork API設定
define('CHATWORK_API_TOKEN', '3db459267f47f409fb534a7880508870'); // 発行されたAPIトークン
define('CHATWORK_GROUP_ID', '394762259');    // プロジェクト管理グループのルームID（先頭の#!ridを削除）

// メンバー名とChatworkアカウントのマッピング
// キー: プロジェクト管理システムで使用する名前
// 値: Chatworkのアカウント名またはID
$CHATWORK_MEMBER_MAPPING = [
    '堀内' => '1406764', // 例：ChatworkのユーザーID
    '金子' => '1419661',
    '只川' => '1407283',
    '星' => '2215971',
    '安井' => '1406770',
    // 必要に応じて他のメンバーを追加
];

/**
 * Chatworkにメッセージを送信する関数
 * 
 * @param string $to カンマ区切りのChatworkアカウント名またはID
 * @param string $message 送信するメッセージ本文
 * @return bool 送信が成功したかどうか
 */
function sendChatworkNotification($to, $message) {
    global $CHATWORK_MEMBER_MAPPING;
    $apiToken = CHATWORK_API_TOKEN;
    $roomId = CHATWORK_GROUP_ID;
    
    // TO指定がある場合は追加
    $toPrefix = '';
    if (!empty($to)) {
        $toMembers = explode(',', $to);
        foreach ($toMembers as $member) {
            $member = trim($member);
            // マッピングテーブルを使用してChatworkアカウントに変換
            $chatworkAccount = isset($CHATWORK_MEMBER_MAPPING[$member]) 
                            ? $CHATWORK_MEMBER_MAPPING[$member] 
                            : $member;
            $toPrefix .= "[To:" . $chatworkAccount . "]";
        }
        $toPrefix .= "\n";
    }
    
    $fullMessage = $toPrefix . $message;
    
    // デバッグ用ログ（本番環境では削除または無効化）
    error_log("Sending to Chatwork: " . $fullMessage);
    
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
    
    // エラーログ記録
    if ($httpCode < 200 || $httpCode >= 300) {
        error_log("Chatwork API Error: HTTP Code $httpCode, Error: $error, Response: $response");
        return false;
    }
    
    return true;
}

/**
 * Chatworkのルームメンバー情報を取得する関数（必要に応じて使用）
 * この関数はデバッグや初期設定時に役立ちます
 */
function getChatworkRoomMembers() {
    $apiToken = CHATWORK_API_TOKEN;
    $roomId = CHATWORK_GROUP_ID;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.chatwork.com/v2/rooms/{$roomId}/members");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-ChatWorkToken: {$apiToken}"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return json_decode($response, true);
    } else {
        error_log("Failed to get Chatwork members: HTTP Code $httpCode, Response: $response");
        return false;
    }
}