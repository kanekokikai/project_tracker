<?php
// セッション設定 - デバッグ用
ini_set('session.cookie_secure', 0);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_cookies', 1);
ini_set('session.use_trans_sid', 0);
ini_set('session.cookie_httponly', 1);

// セッション開始
session_start();

// エックスサーバー環境での自動認証（デバッグ用）
if (strpos($_SERVER['HTTP_HOST'], 'xsrv.jp') !== false) {
    // 本番環境でデバッグしやすいように自動認証（デバッグ後に削除すること）
    $_SESSION['authenticated'] = true;
    $_SESSION['auth_time'] = time();
    $_SESSION['user_id'] = 1;
}

// パスワード保護の設定
define('AUTH_PASSWORD', 'kaneko0911'); // ここにパスワードを設定

/**
 * ユーザーが認証済みかどうかを確認
 * @return bool 認証済みならtrue、そうでなければfalse
 */
function isAuthenticated() {
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

/**
 * パスワードを検証する
 * @param string $password 入力されたパスワード
 * @return bool パスワードが正しければtrue、そうでなければfalse
 */
function verifyPassword($password) {
    return $password === AUTH_PASSWORD;
}

/**
 * ユーザーを認証する（セッションに認証情報を保存）
 */
function authenticateUser() {
    $_SESSION['authenticated'] = true;
    $_SESSION['auth_time'] = time();
    
    // 添付ファイル機能用にユーザーIDを設定
    // 実際のユーザー管理がない場合は1を固定値として使用
    $_SESSION['user_id'] = 1;
}

/**
 * ユーザーのログアウト（セッションから認証情報を削除）
 */
function logoutUser() {
    $_SESSION['authenticated'] = false;
    unset($_SESSION['authenticated']);
    unset($_SESSION['auth_time']);
    unset($_SESSION['user_id']); // 追加: user_idも削除
    
    // セッションを完全に破棄
    session_unset();
    session_destroy();
}