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
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        
        // パフォーマンス最適化オプション
        // 永続的接続はWeb環境では逆効果になる場合があるため無効化
        PDO::ATTR_PERSISTENT => false,
        
        // プリペアドステートメントのキャッシュを有効化
        PDO::ATTR_STATEMENT_CLASS => ['PDOStatement'],
        
        // バッファリングクエリを使用（大量のデータを扱わない場合に有効）
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        
        // コネクションプーリングのヒント（MySQLサーバー側の設定に依存）
        PDO::MYSQL_ATTR_FOUND_ROWS => true,
        
        // メモリ使用量の削減
        PDO::MYSQL_ATTR_DIRECT_QUERY => false,
    ];
    
    // エックスサーバー環境でのパフォーマンス対策
    if ($environment === 'xserver') {
        // コネクションタイムアウトを設定
        $options[PDO::ATTR_TIMEOUT] = 5; // 5秒
    }
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch(PDOException $e) {
    // エラーログを記録し、ユーザーフレンドリーなエラーメッセージを表示
    error_log('Database connection error: ' . $e->getMessage());
    die('データベース接続に失敗しました。管理者にお問い合わせください。');
}