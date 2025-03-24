<?php
// team_members_database.php
require_once __DIR__ . '/config/database.php';  // パスを修正

try {
    // team_members カラムを追加
    $stmt = $pdo->prepare("ALTER TABLE projects ADD COLUMN IF NOT EXISTS team_members TEXT DEFAULT NULL");
    $stmt->execute();
    
    echo "データベースの更新が完了しました。";
} catch (PDOException $e) {
    echo "エラー: " . $e->getMessage();
}