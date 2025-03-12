<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['project_id'])) {
        throw new Exception('Project ID is required');
    }
    
    $project_id = $data['project_id'];
    
    // トランザクション開始
    $pdo->beginTransaction();
    
    // 添付ファイルのディレクトリを削除
    $attachmentDir = "../uploads/project_files/{$project_id}/";
    if (file_exists($attachmentDir)) {
        // ディレクトリ内のすべてのファイルを削除
        $files = glob($attachmentDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        // ディレクトリを削除
        rmdir($attachmentDir);
    }
    
    // プロジェクトの削除
    // ON DELETE CASCADEが設定されていれば、関連する添付ファイル情報も自動的に削除されます
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    
    // トランザクションをコミット
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Project deleted successfully'
    ]);

} catch (Exception $e) {
    // エラーが発生した場合はロールバック
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}