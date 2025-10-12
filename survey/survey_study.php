<?php
session_start();
header('Content-Type: application/json');
require_once('../config/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT role_id FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$stmtRole = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
$stmtRole->execute([$user['role_id']]);
$role = $stmtRole->fetchColumn();

$allowedRoles = ['data_entrants', 'lab_sorter', 'admin'];

if (!in_array($role, $allowedRoles)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

if ($role === 'admin') {
    $stmtData = $pdo->query("SELECT * FROM lab_sorter ORDER BY created_at DESC LIMIT 100");
} else {
    $stmtData = $pdo->prepare("SELECT * FROM lab_sorter WHERE user_id = ? ORDER BY created_at DESC LIMIT 100");
    $stmtData->execute([$userId]);
}

$data = $stmtData->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['data' => $data]);
