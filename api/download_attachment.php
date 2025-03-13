<?php
// 添付ファイルのダウンロード/閲覧を処理するAPI
require_once '../config/database.php';

// GETパラメータの確認
if (!isset($_GET['attachment_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => '不正なリクエスト']);
    exit;
}

$attachment_id = intval($_GET['attachment_id']);
$view_mode = isset($_GET['view']) && $_GET['view'] == 1;

try {
    // 添付ファイル情報の取得
    $stmt = $pdo->prepare("SELECT * FROM project_attachments WHERE id = ?");
    $stmt->execute([$attachment_id]);
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$attachment) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => '添付ファイルが見つかりません']);
        exit;
    }
    
    // ファイルパスの構築
    $rootPath = $_SERVER['DOCUMENT_ROOT']; // ドキュメントルート（例：C:/xampp/htdocs）
    $filePath = $rootPath . '/project_tracker/uploads/project_files/' . $attachment['project_id'] . '/' . $attachment['file_name'];
    
    // デバッグ用：ファイルパスをログに記録
    error_log("Attempting to access file: " . $filePath);
    
    // ファイルの存在確認
    if (!file_exists($filePath)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'ファイルが見つかりません']);
        error_log("File not found: " . $filePath);
        exit;
    }
    
    // ファイルサイズ
    $fileSize = filesize($filePath);
    if ($fileSize === false) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'ファイルサイズの取得に失敗しました']);
        exit;
    }
    
    // ファイルタイプに応じたContent-Typeの設定
    $contentType = $attachment['file_type'];
    if (empty($contentType)) {
        // MIMEタイプが不明な場合はapplication/octet-streamを使用
        $contentType = 'application/octet-stream';
    }
    
    // 出力バッファをクリア
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // ヘッダーの設定
    header('Content-Type: ' . $contentType);
    
    if ($view_mode) {
        // 閲覧モードの場合
        header('Content-Disposition: inline; filename="' . $attachment['original_file_name'] . '"');
    } else {
        // ダウンロードモードの場合
        header('Content-Disposition: attachment; filename="' . $attachment['original_file_name'] . '"');
    }
    
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: public, max-age=0');
    
    // ファイルの読み込みと出力
    readfile($filePath);
    exit;
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    error_log("File download error: " . $e->getMessage());
}