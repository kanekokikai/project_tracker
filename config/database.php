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
    define('DB_HOST', 'localhost');
    define('DB_USER', 'xs765558_kaneko');
    define('DB_PASS', 'kaneko0911');
    define('DB_NAME', 'xs765558_projecttracker');
}

// データベース接続
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        
        // 永続的接続は共有ホスティングでは無効化（リソース消費を抑える）
        PDO::ATTR_PERSISTENT => false,
        
        // クエリのタイムアウト設定（秒）
        PDO::ATTR_TIMEOUT => 3,
        
        // バッファリングクエリの最適化
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch(PDOException $e) {
    // エラーログを記録し、ユーザーフレンドリーなエラーメッセージを表示
    error_log('Database connection error: ' . $e->getMessage());
    die('データベース接続に失敗しました。管理者にお問い合わせください。');
}
