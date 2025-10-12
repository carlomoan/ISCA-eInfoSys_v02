<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db_connect.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(["success" => false, "message" => "Not authenticated"]);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get') {
    $stmt = $pdo->prepare("
        SELECT u.fname, u.lname, u.email, u.phone, u.created_at, r.name AS role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(["success" => true, "user" => $user]);
    } else {
        echo json_encode(["success" => false, "message" => "User not found"]);
    }
    exit;
}

if ($action === 'update') {
    $fname = trim($_POST['fname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($fname) || empty($lname)) {
        echo json_encode(["success" => false, "message" => "First and Last Name are required"]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET fname = ?, lname = ?, phone = ? WHERE id = ?");
    if ($stmt->execute([$fname, $lname, $phone, $userId])) {
        echo json_encode(["success" => true, "message" => "Profile updated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update profile"]);
    }
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid request"]);
