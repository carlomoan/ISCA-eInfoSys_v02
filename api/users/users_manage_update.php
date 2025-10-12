<?php
require_once __DIR__.'/../../config/db_connect.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if(!$input || !isset($input['user_id'])){
    echo json_encode(['success'=>false,'message'=>'Invalid input']);
    exit;
}

$user_id = (int)$input['user_id'];
$is_verified = isset($input['is_verified']) ? (int)$input['is_verified'] : 0;
$is_admin = isset($input['is_admin']) ? (int)$input['is_admin'] : 0;
$project_ids = $input['project_ids'] ?? [];
$cluster_ids = $input['cluster_ids'] ?? [];
$lab_tech_id = $input['lab_tech_id'] ?? null;

try{
    $pdo->beginTransaction();

    // 1. Update is_verified and is_admin
    $stmt = $pdo->prepare("UPDATE users SET is_verified=?, is_admin=?, lab_tech_id=? WHERE id=?");
    $stmt->execute([$is_verified, $is_admin, $lab_tech_id, $user_id]);

    // 2. Update Projects
    $pdo->prepare("DELETE FROM user_projects WHERE user_id=?")->execute([$user_id]);
    if(count($project_ids)){
        $stmt = $pdo->prepare("INSERT INTO user_projects (user_id, project_id) VALUES (?,?)");
        foreach($project_ids as $pid){
            $stmt->execute([$user_id, $pid]);
        }
    }

    // 3. Update Clusters
    $pdo->prepare("DELETE FROM user_clusters WHERE user_id=?")->execute([$user_id]);
    if(count($cluster_ids)){
        $stmt = $pdo->prepare("INSERT INTO user_clusters (user_id, cluster_id) VALUES (?,?)");
        foreach($cluster_ids as $cid){
            $stmt->execute([$user_id, $cid]);
        }
    }

    $pdo->commit();
    echo json_encode(['success'=>true,'message'=>'User updated successfully']);

} catch(PDOException $e){
    $pdo->rollBack();
    echo json_encode(['success'=>false,'message'=>'DB error: '.$e->getMessage()]);
}
