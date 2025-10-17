<?php
header('Content-Type: application/json');
require_once '../../config.php';   // adjust path to your config.php
require_once '../../db_connect.php'; // adjust path to your PDO connection

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success'=>false,'message'=>'Invalid JSON input']);
    exit;
}

$user_id = $input['user_id'] ?? null;
$password = $input['password'] ?? null;
$is_verified = isset($input['is_verified']) ? intval($input['is_verified']) : null;
$is_admin = isset($input['is_admin']) ? intval($input['is_admin']) : null;
$role_id = $input['role_id'] ?? null;
$project_id = $input['project_id'] ?? null;
$cluster_id = $input['cluster_id'] ?? null;
$lab_tech_id = $input['lab_tech_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success'=>false,'message'=>'User ID missing']);
    exit;
}

try {
    $pdo->beginTransaction();

    // =================== Update password ===================
    if ($password && strlen($password) > 3) { // simple min length check
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password=:pwd WHERE id=:uid");
        $stmt->execute([':pwd'=>$hash, ':uid'=>$user_id]);
    }

    // =================== Update verified & admin ===================
    $fields = [];
    $params = [':uid'=>$user_id];

    if ($is_verified !== null) { $fields[] = "is_verified=:verified"; $params[':verified']=$is_verified; }
    if ($is_admin !== null) { $fields[] = "is_admin=:admin"; $params[':admin']=$is_admin; }

    if ($fields) {
        $sql = "UPDATE users SET ".implode(',', $fields)." WHERE id=:uid";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    // =================== Update role assignment ===================
    // Using one-to-many relationship: role_id is directly in users table
    if ($role_id) {
        $stmt = $pdo->prepare("UPDATE users SET role_id=:rid WHERE id=:uid");
        $stmt->execute([':rid'=>$role_id, ':uid'=>$user_id]);
    }

    // =================== Update project assignment ===================
    if ($project_id) {
        $stmt = $pdo->prepare("UPDATE users SET user_project_id=:pid WHERE id=:uid");
        $stmt->execute([':pid'=>$project_id, ':uid'=>$user_id]);
    }

    // =================== Update cluster assignment ===================
    if ($cluster_id) {
        $stmt = $pdo->prepare("UPDATE users SET cluster_id=:cid WHERE id=:uid");
        $stmt->execute([':cid'=>$cluster_id, ':uid'=>$user_id]);
    }

    // =================== Update lab technician ===================
    if ($lab_tech_id !== null) {
        $stmt = $pdo->prepare("UPDATE users SET lab_tech_id=:lid WHERE id=:uid");
        $stmt->execute([':lid'=>$lab_tech_id, ':uid'=>$user_id]);
    }

    $pdo->commit();
    echo json_encode(['success'=>true,'message'=>'User updated successfully']);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false,'message'=>'DB Error: '.$e->getMessage()]);
}
