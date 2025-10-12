<?php
// Load config FIRST to ensure proper session initialization
require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

header('Content-Type: application/json');

// Hakikisha user ana ruhusa
if (!checkPermission('view_report')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    // Query ya kupata rounds distinct
    $sql = "SELECT DISTINCT round 
            FROM vw_merged_field_lab_data
            ORDER BY round DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rounds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'data'    => $rounds,
        'total'   => count($rounds)
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    exit;
}
