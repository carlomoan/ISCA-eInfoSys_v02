<?php
require_once __DIR__.'/../../config/db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

if(!$data || !isset($data['id'])){
    echo json_encode(['success'=>false,'message'=>'Invalid input']);
    exit;
}

$userId = (int)$data['id'];

try {
    // Start transaction
    $pdo->beginTransaction();

    // 1️⃣ Remove user from user_projects
    $stmt = $pdo->prepare("DELETE FROM user_projects WHERE user_id = :id");
    $stmt->execute([':id'=>$userId]);

    // 2️⃣ Remove from lab_technicians if this user is assigned
    $stmt = $pdo->prepare("DELETE FROM lab_technicians WHERE user_id = :id OR lab_tech_id = :id");
    $stmt->execute([':id'=>$userId]);

    // 3️⃣ Delete the user itself
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([':id'=>$userId]);

    // Commit
    $pdo->commit();

    echo json_encode(['success'=>true, 'message'=>'User deleted successfully']);

} catch(PDOException $e){
    $pdo->rollBack();
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
