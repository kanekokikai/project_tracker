<?php
// 環境の切り替え
$environment = 'local';  // 'local' または 'xserver'

// データベース接続情報
if ($environment === 'local') {
    // ローカル環境（XAMPP）用の設定
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'project_tracker');
} else {
    // エックスサーバー用の設定
    define('DB_HOST', 'localhost');  // 後で変更
    define('DB_USER', '***');        // 後で変更
    define('DB_PASS', '***');        // 後で変更
    define('DB_NAME', '***');        // 後で変更
}

// データベース接続
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch(PDOException $e) {
    die('データベース接続に失敗しました。: ' . $e->getMessage());
}