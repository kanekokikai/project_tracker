<?php
// api/get_project_details.php

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// プロジェクトIDの取得
$projectId = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

if (!$projectId) {
    echo json_encode(['success' => false, 'message' => 'プロジェクトIDが指定されていません']);
    exit;
}

try {
    // プロジェクト情報の取得 - department と parent_id カラムを追加
    $stmt = $pdo->prepare("SELECT id, name, status, team_members, department, parent_id FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'プロジェクトが見つかりません']);
        exit;
    }
    
    // デバッグ情報を追加
    $project['debug_info'] = [
        'has_team_members' => isset($project['team_members']),
        'team_members_type' => gettype($project['team_members']),
        'team_members_raw' => $project['team_members'],
        'has_department' => isset($project['department']),
        'department_value' => $project['department']
    ];
    
    echo json_encode(['success' => true, 'project' => $project]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'データベースエラー: ' . $e->getMessage()]);
}