<?php
// 添付ファイルをダウンロードするAPI
require_once '../config/database.php';

// GETパラメータの確認
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['attachment_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => '不正なリクエスト']);
    exit;
}

$attachment_id = intval($_GET['attachment_id']);

try {
    // 添付ファイル情報を取得
    $stmt = $pdo->prepare("
        SELECT pa.*, p.name as project_name
        FROM project_attachments pa
        JOIN projects p ON pa.project_id = p.id
        WHERE pa.id = ?
    ");
    $stmt->execute([$attachment_id]);
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$attachment) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => '添付ファイルが見つかりません']);
        exit;
    }
    
    // ファイルパスを構築
    $filePath = __DIR__ . "/../../uploads/project_files/{$attachment['project_id']}/{$attachment['file_name']}"; 
    
    // ファイルの存在確認
    if (!file_exists($filePath)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'ファイルが見つかりません']);
        exit;
    }
    
// ファイルのダウンロード
header('Content-Description: File Transfer');
header('Content-Type: ' . $attachment['file_type']);
// viewパラメータが設定されていれば「inline」で表示、そうでなければダウンロード
if (isset($_GET['view']) && $_GET['view'] == '1') {
    header('Content-Disposition: inline; filename="' . $attachment['original_file_name'] . '"');
} else {
    header('Content-Disposition: attachment; filename="' . $attachment['original_file_name'] . '"');
}
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;    

} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'データベースエラー: ' . $e->getMessage()]);
}
?>