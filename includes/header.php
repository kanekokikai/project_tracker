<?php
// 環境の切り替え（database.phpと同じ変数を使用）
require_once __DIR__ . '/../config/database.php';

// 環境に応じたベースパスを設定
$basePath = ($environment === 'local') ? '/project_tracker' : '/project-tracker';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>プロジェクト管理</title>
    <!-- 強制的にキャッシュをクリアするためのバージョン番号を追加 -->
    <link rel="stylesheet" href="<?php echo $basePath; ?>/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1>プロジェクト管理</h1>
        </div>
    </header>
    <main class="main-content">