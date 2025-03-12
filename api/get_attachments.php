<?php
// プロジェクトの添付ファイル一覧を取得するAPI
header('Content-Type: application/json');
require_once '../config/database.php';

// GETパラメータの確認
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['project_id'])) {
    echo json_encode(['status' => 'error', 'message' => '不正なリクエスト']);
    exit;
}

$project_id = intval($_GET['project_id']);

// プロジェクトの存在確認
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    echo json_encode(['status' => 'error', 'message' => 'プロジェクトが存在しません']);
    exit;
}

try {
    // 添付ファイル一覧を取得（正しい日付カラム名でソート）
    $stmt = $pdo->prepare("
        SELECT *
        FROM project_attachments
        WHERE project_id = ?
        ORDER BY uploaded_at DESC
    ");
    $stmt->execute([$project_id]);
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ファイルサイズを読みやすい形式に変換
    foreach ($attachments as &$attachment) {
        $attachment['file_size_formatted'] = formatFileSize($attachment['file_size']);
        // アップロード者は固定値を設定
        $attachment['uploader_name'] = 'システム';
        
        // 日付を標準化（JavaScriptで使用される形式に）
        if (isset($attachment['uploaded_at'])) {
            $attachment['upload_date'] = $attachment['uploaded_at'];
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $attachments
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'データベースエラー: ' . $e->getMessage()]);
}

// ファイルサイズを読みやすい形式に変換する関数
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>