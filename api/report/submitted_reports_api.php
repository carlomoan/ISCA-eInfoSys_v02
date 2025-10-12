<?php
// Load config FIRST to ensure proper session initialization
require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

header('Content-Type: application/json');

if (!checkPermission('view_report')) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT * FROM uploaded_reports LIMIT 200");
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $reports
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
