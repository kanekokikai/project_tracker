<?php
require_once 'config/database.php';

try {
    // department カラムを追加
    $sql = "ALTER TABLE projects ADD COLUMN department VARCHAR(50) DEFAULT '選択なし'";
    $pdo->exec($sql);
    
    echo "データベースが正常に更新されました。";
} catch (PDOException $e) {
    echo "エラー: " . $e->getMessage();
}
?>