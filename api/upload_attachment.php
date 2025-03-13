<?php
// ファイルアップロードを処理するAPI
header('Content-Type: application/json');
require_once '../config/database.php';

// POSTリクエストとファイルの存在確認
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file']) || !isset($_POST['project_id'])) {
    echo json_encode(['status' => 'error', 'message' => '不正なリクエスト']);
    exit;
}

$project_id = intval($_POST['project_id']);

// プロジェクトの存在確認
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    echo json_encode(['status' => 'error', 'message' => 'プロジェクトが存在しません']);
    exit;
}

// アップロードされたファイルの検証
$file = $_FILES['file'];
$fileName = basename($file['name']);
$fileSize = $file['size'];
$fileType = $file['type'];
$fileTmp = $file['tmp_name'];
$fileError = $file['error'];

// ファイルエラーチェック
if ($fileError !== 0) {
    $errorMessages = [
        1 => 'アップロードされたファイルがPHP.iniのupload_max_filesizeディレクティブを超えています',
        2 => 'アップロードされたファイルがHTMLフォームで指定されたMAX_FILE_SIZEを超えています',
        3 => '一部のみアップロードされました',
        4 => 'ファイルがアップロードされませんでした',
        6 => '一時フォルダがありません',
        7 => 'ディスクへの書き込みに失敗しました',
        8 => 'PHPの拡張モジュールがファイルのアップロードを停止しました'
    ];
    
    $errorMessage = isset($errorMessages[$fileError]) ? $errorMessages[$fileError] : '不明なエラー';
    echo json_encode(['status' => 'error', 'message' => 'ファイルアップロードエラー: ' . $errorMessage]);
    exit;
}

// ファイルサイズ制限（10MB）
if ($fileSize > 10 * 1024 * 1024) {
    echo json_encode(['status' => 'error', 'message' => 'ファイルサイズは10MB以下である必要があります']);
    exit;
}

// =========== 修正箇所開始 ===========
// アップロードディレクトリの準備
// 絶対パスで指定
$uploadDir = '/home/xs765558/public_html/project_tracker/uploads/project_files/' . $project_id . '/';

// デバッグ用：アップロードディレクトリを記録
error_log("Upload directory: " . $uploadDir);
// =========== 修正箇所終了 ===========

// ディレクトリが存在しない場合は作成
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode(['status' => 'error', 'message' => 'アップロードディレクトリの作成に失敗しました']);
        exit;
    }
}

// ファイル名の衝突を避けるためにユニークなファイル名を生成
$fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
$uniqueFileName = uniqid() . '_' . time() . '.' . $fileExtension;
$uploadPath = $uploadDir . $uniqueFileName;

// =========== 修正箇所開始 ===========
// デバッグ用：アップロードパスを記録（リリース時にコメントアウト可）
error_log("File upload path: " . $uploadPath);
// =========== 修正箇所終了 ===========

// ファイルの移動
if (!move_uploaded_file($fileTmp, $uploadPath)) {
    echo json_encode(['status' => 'error', 'message' => 'ファイルの保存に失敗しました']);
    exit;
}

// データベースに情報を保存
try {
    // 正しいカラム名を使用（phpMyAdminでの確認結果に基づく）
    $stmt = $pdo->prepare("
        INSERT INTO project_attachments 
        (project_id, file_name, original_file_name, file_size, file_type, uploaded_by, uploaded_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $project_id,
        $uniqueFileName,
        $fileName,
        $fileSize,
        $fileType,
        1  // ユーザーIDは固定値として1を使用
    ]);
    
    $attachment_id = $pdo->lastInsertId();
    
    // =========== 修正箇所開始 ===========
    // レスポンスにデバッグ情報を追加（リリース時に不要であれば削除可）
    echo json_encode([
        'status' => 'success',
        'message' => 'ファイルがアップロードされました',
        'data' => [
            'id' => $attachment_id,
            'file_name' => $uniqueFileName,
            'original_file_name' => $fileName,
            'file_size' => $fileSize,
            'file_type' => $fileType,
            'debug_path' => str_replace($_SERVER['DOCUMENT_ROOT'], '[DOCUMENT_ROOT]', $uploadPath) // デバッグ用
        ]
    ]);
    // =========== 修正箇所終了 ===========
} catch (PDOException $e) {
    // アップロードしたファイルを削除
    @unlink($uploadPath);
    echo json_encode(['status' => 'error', 'message' => 'データベースエラー: ' . $e->getMessage()]);
}
?>