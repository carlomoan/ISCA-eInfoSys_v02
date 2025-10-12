<?php
require_once "../../config.php"; // adjust path if needed
require_once "../../db_connect.php"; // PDO connection
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['user_id'])) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

$user_id = $data['user_id'];
$password = $data['password'] ?? null;
$is_verified = $data['is_verified'] ?? null;
$is_admin = $data['is_admin'] ?? null;
$role_id = $data['role_id'] ?? null;
$project_id = $data['project_id'] ?? null;
$cluster_id = $data['cluster_id'] ?? null;
$lab_action = $data['lab_action'] ?? null; // "assign" or "revoke"

try {
    $pdo->beginTransaction();

    // ===== PASSWORD =====
    if ($password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $user_id]);
    }

    // ===== VERIFIED =====
    if ($is_verified !== null) {
        $stmt = $pdo->prepare("UPDATE users SET is_verified = ? WHERE id = ?");
        $stmt->execute([$is_verified, $user_id]);
    }

    // ===== ADMIN =====
    if ($is_admin !== null) {
        $stmt = $pdo->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
        $stmt->execute([$is_admin, $user_id]);
    }

    // ===== ROLE =====
    if ($role_id) {
        // Remove old roles
        $stmt = $pdo->prepare("DELETE FROM user_role WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // Assign new role
        $stmt = $pdo->prepare("INSERT INTO user_role (user_id, role_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $role_id]);
    }

    // ===== PROJECT =====
    if ($project_id) {
        $stmt = $pdo->prepare("DELETE FROM user_project WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stmt = $pdo->prepare("INSERT INTO user_project (user_id, project_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $project_id]);
    }

    // ===== CLUSTER =====
    if ($cluster_id) {
        $stmt = $pdo->prepare("DELETE FROM user_cluster WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stmt = $pdo->prepare("INSERT INTO user_cluster (user_id, cluster_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $cluster_id]);
    }

    // ===== LAB TECHNICIAN =====
    if ($lab_action) {
        if ($lab_action === "assign") {
            // Check if already in lab_technicians
            $stmt = $pdo->prepare("SELECT lab_tech_id FROM lab_technicians WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $labTech = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($labTech) {
                $labTechId = $labTech['lab_tech_id'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO lab_technicians (user_id, created_at) VALUES (?, NOW())");
                $stmt->execute([$user_id]);
                $labTechId = $pdo->lastInsertId();
            }

            // Update users table
            $stmt = $pdo->prepare("UPDATE users SET lab_tech_id = ? WHERE id = ?");
            $stmt->execute([$labTechId, $user_id]);

        } elseif ($lab_action === "revoke") {
            // Remove lab_tech_id from users table
            $stmt = $pdo->prepare("UPDATE users SET lab_tech_id = NULL WHERE id = ?");
            $stmt->execute([$user_id]);

            // Optionally delete from lab_technicians
            $stmt = $pdo->prepare("DELETE FROM lab_technicians WHERE user_id = ?");
            $stmt->execute([$user_id]);
        }
    }

    $pdo->commit();
    echo json_encode(["success" => true, "message" => "User updated successfully"]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
