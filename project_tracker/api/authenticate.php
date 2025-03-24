<?php
require_once __DIR__ . '/../includes/auth.php';

// POSTリクエストからパスワードを取得
$password = isset($_POST['password']) ? $_POST['password'] : '';
$response = ['success' => false, 'message' => ''];

if (empty($password)) {
    $response['message'] = 'パスワードを入力してください';
} else if (verifyPassword($password)) {
    // パスワードが正しい場合は認証
    authenticateUser();
    $response['success'] = true;
    $response['message'] = '認証に成功しました';
} else {
    $response['message'] = 'パスワードが正しくありません';
}

// JSONとしてレスポンスを返す
header('Content-Type: application/json');
echo json_encode($response);