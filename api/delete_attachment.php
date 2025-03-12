<?php
// 添付ファイルを削除するAPI
header('Content-Type: application/json');
require_once '../config/database.php';

// POSTリクエストかつパラメータの確認
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['attachment_id'])) {
    echo json_encode(['status' => 'error', 'message' => '不正なリクエスト']);
    exit;
}

$attachment_id = intval($_POST['attachment_id']);

try {
    // トランザクション開始
    $pdo->beginTransaction();
    
    // 添付ファイル情報を取得
    $stmt = $pdo->prepare("
        SELECT pa.*, p.id as project_id
        FROM project_attachments pa
        JOIN projects p ON pa.project_id = p.id
        WHERE pa.id = ?
    ");
    $stmt->execute([$attachment_id]);
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$attachment) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => '添付ファイルが見つかりません']);
        exit;
    }
    
    // ファイルパスを構築
    $filePath = __DIR__ . "/../../uploads/project_files/{$attachment['project_id']}/{$attachment['file_name']}";    

    // ファイルが存在すれば削除
    if (file_exists($filePath)) {
        if (!unlink($filePath)) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'ファイルの削除に失敗しました']);
            exit;
        }
    }
    
    // データベースから削除
    $stmt = $pdo->prepare("DELETE FROM project_attachments WHERE id = ?");
    $stmt->execute([$attachment_id]);
    
    // トランザクションコミット
    $pdo->commit();
    
    echo json_encode(['status' => 'success', 'message' => '添付ファイルが削除されました']);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'データベースエラー: ' . $e->getMessage()]);
}
?>