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

// Optional: hakikisha user ana ruhusa ya kuona clusters
if (!checkPermission('add_field_data') || !checkPermission('add_lab_data')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT cluster_id AS cluster_id, cluster_name AS cluster_name
        FROM clusters
        WHERE user_id = :user_id
        ORDER BY cluster_name
    ");
    $stmt->execute([':user_id' => $userId]);
    $clusters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count'   => count($clusters),
        'data'    => $clusters
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: '.$e->getMessage()]);
}
