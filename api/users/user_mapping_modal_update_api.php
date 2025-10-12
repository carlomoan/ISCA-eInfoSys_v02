<?php
header('Content-Type: application/json');
require_once '../../config/db_connect.php'; // hakikisha path yako iko sahihi

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['user_id'])) {
    echo json_encode(["success" => false, "message" => "Invalid input: user_id missing"]);
    exit;
}

$user_id     = $data['user_id'];
$password    = $data['password'] ?? null;
$is_verified = $data['is_verified'] ?? null;
$is_admin    = $data['is_admin'] ?? null;
$role_id     = $data['role_id'] ?? null;
$project_id  = $data['project_id'] ?? null;
$cluster_id  = $data['cluster_id'] ?? null;
$lab_action  = $data['lab_action'] ?? null; // "assign" or "revoke"

try {
    $pdo->beginTransaction();

    // ===== PASSWORD =====
    if (!empty($password)) {
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
if (!empty($role_id)) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_roles WHERE user_id = ? AND role_id = ?");
    $stmt->execute([$user_id, $role_id]);
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $role_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE user_roles SET role_id = ? WHERE user_id = ? AND role_id = ?");
        $stmt->execute([$role_id, $user_id, $role_id]);
    }
}

// ===== PROJECT =====
if (!empty($project_id)) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_projects WHERE user_id = ? AND project_id = ?");
    $stmt->execute([$user_id, $project_id]);
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO user_projects (user_id, project_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $project_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE user_projects SET project_id = ? WHERE user_id = ? AND project_id = ?");
        $stmt->execute([$project_id, $user_id, $project_id]);
    }
}

// ===== CLUSTER =====
if (!empty($cluster_id)) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_clusters WHERE user_id = ? AND cluster_id = ?");
    $stmt->execute([$user_id, $cluster_id]);
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO user_clusters (user_id, cluster_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $cluster_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE user_clusters SET cluster_id = ? WHERE user_id = ? AND cluster_id = ?");
        $stmt->execute([$cluster_id, $user_id, $cluster_id]);
    }
}


    // ===== LAB TECHNICIAN =====
    if (!empty($lab_action)) {
        if ($lab_action === "assign") {
            // Angalia kama user tayari ame-assign
            $stmt = $pdo->prepare("SELECT lab_tech_id FROM lab_technicians WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $labTech = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($labTech) {
                // User tayari assigned – toa error badala ya ku-insert
                echo json_encode([
                    "success" => false,
                    "message" => "User has already been assigned to Lab"
                ]);
                $pdo->rollBack();
                exit;
            }

            // Ikiwa bado hajasajiliwa
            $stmt = $pdo->prepare("INSERT INTO lab_technicians (user_id, created_at) VALUES (?, NOW())");
            $stmt->execute([$user_id]);
            $labTechId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("UPDATE users SET lab_tech_id = ? WHERE id = ?");
            $stmt->execute([$labTechId, $user_id]);

        } elseif ($lab_action === "revoke") {
            $stmt = $pdo->prepare("UPDATE users SET lab_tech_id = NULL WHERE id = ?");
            $stmt->execute([$user_id]);

            $stmt = $pdo->prepare("DELETE FROM lab_technicians WHERE user_id = ?");
            $stmt->execute([$user_id]);
        }
    }

    $pdo->commit();
    echo json_encode(["success" => true, "message" => "User updated successfully"]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
