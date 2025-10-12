<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Optional: hakikisha user ana ruhusa ya kuona households
if (!checkPermission('add_field_data') || !checkPermission('add_lab_data')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$clusterId = $_GET['cluster_id'] ?? null;
if (!$clusterId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'cluster_id is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT hhcode AS hh_code, head_name AS hh_name
        FROM households
        WHERE cluster_id = :cluster_id
        ORDER BY hhcode
    ");
    $stmt->execute([':cluster_id' => $clusterId]);
    $households = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count'   => count($households),
        'data'    => $households
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: '.$e->getMessage()]);
}
