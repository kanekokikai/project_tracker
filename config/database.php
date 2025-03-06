<?php
// 環境の切り替え
$environment = 'xserver';  // 'local' または 'xserver'

// データベース接続情報
if ($environment === 'local') {
    // ローカル環境（XAMPP）用の設定
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'project_tracker');
} else {
    // エックスサーバー用の設定
    define('DB_HOST', 'localhost');  // 変更済み
    define('DB_USER', 'xs765558_kaneko');
    define('DB_PASS', 'kaneko0911');
    define('DB_NAME', 'xs765558_projecttracker');
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
            PDO::ATTR_PERSISTENT => true,  // 永続的接続を使用
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,  // バッファリングクエリを使用
        ]
    );
} catch(PDOException $e) {
    die('データベース接続に失敗しました。: ' . $e->getMessage());
}