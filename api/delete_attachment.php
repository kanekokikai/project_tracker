<?php
// 添付ファイルを削除するAPI
header('Content-Type: application/json');
require_once '../config/database.php';

// POSTリクエストと添付ファイルIDの確認
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['attachment_id'])) {
    echo json_encode(['status' => 'error', 'message' => '不正なリクエスト']);
    exit;
}

$attachment_id = intval($_POST['attachment_id']);

try {
    // トランザクション開始
    $pdo->beginTransaction();
    
    // 添付ファイル情報の取得
    $stmt = $pdo->prepare("SELECT * FROM project_attachments WHERE id = ?");
    $stmt->execute([$attachment_id]);
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$attachment) {
        throw new Exception('添付ファイルが見つかりません');
    }
    
// ファイルの物理的な削除
// 絶対パスで指定
$filePath = '/home/xs765558/xs765558.xsrv.jp/public_html/project_tracker/uploads/project_files/' . $attachment['project_id'] . '/' . $attachment['file_name'];

// デバッグ用：削除しようとしているファイルパスを記録
error_log("Attempting to delete file: " . $filePath);

    // ファイルが存在するか確認してから削除
    if (file_exists($filePath)) {
        if (!unlink($filePath)) {
            throw new Exception('ファイルの削除に失敗しました');
        }
        // デバッグ用：ファイル削除成功を記録
        error_log("File successfully deleted: " . $filePath);
    } else {
        // ファイルが見つからなかった場合は警告だけ出して続行
        error_log("Warning: File not found for deletion: " . $filePath);
    }
    
    // データベースから添付ファイル情報を削除
    $stmt = $pdo->prepare("DELETE FROM project_attachments WHERE id = ?");
    $stmt->execute([$attachment_id]);
    
    // トランザクションをコミット
    $pdo->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => '添付ファイルが削除されました'
    ]);
} catch (Exception $e) {
    // エラーが発生した場合はロールバック
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    
    // デバッグ用：エラー情報を記録
    error_log("File deletion error: " . $e->getMessage());
}