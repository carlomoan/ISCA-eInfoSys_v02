<?php
header('Content-Type: application/json');
require_once '../../config/db_connect.php'; // hakikisha path yako iko sahihi

$userId = $_GET['user_id'] ?? null;

if (!$userId) {
    echo json_encode([
        "success" => false,
        "message" => "Missing user_id"
    ]);
    exit;
}

try {
    // ================= BASIC USER INFO =================
    $stmt = $pdo->prepare("SELECT id, fname, lname, email, phone, is_verified, is_admin, role_id
                           FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            "success" => false,
            "message" => "User not found"
        ]);
        exit;
    }

    // ================= ROLES =================
    // Get role from users.role_id (one-to-many relationship)
    $user['roles'] = [];
    if (!empty($user['role_id'])) {
        $stmt = $pdo->prepare("SELECT id, name FROM roles WHERE id = ?");
        $stmt->execute([$user['role_id']]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($role) {
            $user['roles'] = [$role]; // Return as array for backward compatibility
        }
    }


    // ================= PROJECTS =================
    $stmt = $pdo->prepare("SELECT p.project_id, p.project_name 
                           FROM projects p
                           INNER JOIN user_projects up ON up.project_id = p.project_id
                           WHERE up.user_id = ?");
    $stmt->execute([$userId]);
    $user['projects'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================= CLUSTERS =================
// Pata clusters zote za user kutoka user_clusters + join clusters table
$stmt = $pdo->prepare("
    SELECT uc.cluster_id AS id, c.cluster_name
    FROM user_clusters uc
    LEFT JOIN clusters c ON c.cluster_id = uc.cluster_id
    WHERE uc.user_id = ?
");
$stmt->execute([$userId]);
$user['clusters'] = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ================= LAB TECH =================

// Pata lab_tech_id ya user
$stmt = $pdo->prepare("SELECT lab_tech_id FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$userLabTechId = (int) $stmt->fetchColumn();

// Initialize lab_tech null
$user['lab_tech'] = null;

// Angalia kama lab_tech_id ipo na > 0
if ($userLabTechId > 0) {
    // Angalia kama kuna assignment kwenye lab_technicians
    $stmt = $pdo->prepare("
        SELECT lt.lab_tech_id, u.fname, u.lname, u.email, lt.project_id
        FROM lab_technicians lt
        INNER JOIN users u ON u.id = lt.lab_tech_id
        WHERE lt.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $labTech = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ikiwa assignment ipo, jenga array ya lab_tech
    if ($labTech) {
        $user['lab_tech'] = [
            'id' => $labTech['lab_tech_id'],
            'name' => trim($labTech['fname'] . ' ' . $labTech['lname']),
            'email' => $labTech['email'],
            'project_id' => $labTech['project_id']
        ];
    } else {
        // Hakuna assignment: lab_tech_id bado > 0 lakini user hana project assigned
        $stmt = $pdo->prepare("SELECT fname, lname, email FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userLabTechId]);
        $techInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($techInfo) {
            $user['lab_tech'] = [
                'id' => $userLabTechId,
                'name' => trim($techInfo['fname'] . ' ' . $techInfo['lname']),
                'email' => $techInfo['email'],
                'project_id' => null
            ];
        }
    }
}

// Hii array ya $user['lab_tech'] sasa inaweza kurudishwa kwenye JS kwa modal



    // ================= SUCCESS RESPONSE =================
    echo json_encode([
        "success" => true,
        "user" => $user
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
