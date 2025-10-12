<?php
require_once __DIR__ . '/../../config/db_connect.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['users'])) {
        throw new Exception("Invalid input");
    }

    $pdo->beginTransaction();

    foreach ($input['users'] as $user) {
        $id = (int)$user['id'];
        $fname = $user['fname'] ?? '';
        $lname = $user['lname'] ?? '';
        $email = $user['email'] ?? '';
        $phone = $user['phone'] ?? '';
        $role_id = isset($user['role_id']) ? (int)$user['role_id'] : null;
        $lab_tech_id = isset($user['lab_tech_id']) ? (int)$user['lab_tech_id'] : null;

        // 1️⃣ Update users table
        $stmt = $pdo->prepare("UPDATE users SET fname=?, lname=?, email=?, phone=?, role_id=?, lab_tech_id=? WHERE id=?");
        $stmt->execute([$fname,$lname,$email,$phone,$role_id,$lab_tech_id,$id]);

        // 2️⃣ Update projects (user_projects table)
        if (isset($user['projects']) && is_array($user['projects'])) {
            // Delete existing
            $stmt = $pdo->prepare("DELETE FROM user_projects WHERE user_id=?");
            $stmt->execute([$id]);

            // Insert new
            $stmtInsert = $pdo->prepare("INSERT INTO user_projects (user_id, project_id, project_code, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");

            foreach ($user['projects'] as $projName) {
                // Lookup project_id by name
                $stmtP = $pdo->prepare("SELECT project_id, project_code FROM projects WHERE project_name=? LIMIT 1");
                $stmtP->execute([$projName]);
                $proj = $stmtP->fetch(PDO::FETCH_ASSOC);
                if($proj){
                    $stmtInsert->execute([$id, $proj['project_id'], $proj['project_code']]);
                }
            }
        }

        // 3️⃣ Update permissions (user_permissions table)
        if (isset($user['permissions']) && is_array($user['permissions'])) {
            // Delete existing
            $stmt = $pdo->prepare("DELETE FROM user_permissions WHERE user_id=?");
            $stmt->execute([$id]);

            // Insert new
            $stmtInsertPerm = $pdo->prepare("INSERT INTO user_permissions (user_id, permission_id) VALUES (?, ?)");
            foreach ($user['permissions'] as $permName) {
                $stmtPerm = $pdo->prepare("SELECT id FROM permissions WHERE name=? LIMIT 1");
                $stmtPerm->execute([$permName]);
                $perm = $stmtPerm->fetch(PDO::FETCH_ASSOC);
                if($perm){
                    $stmtInsertPerm->execute([$id, $perm['id']]);
                }
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success'=>true]);

} catch (Exception $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'success'=>false,
        'message'=>$e->getMessage()
    ]);
}
