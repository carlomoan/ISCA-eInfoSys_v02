<?php
/**
 * Get Uploaded Reports API
 * Returns list of uploaded principal reports
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT id, file_name, report_type, round, cluster_name, uploaded_at
        FROM uploaded_reports
        ORDER BY uploaded_at DESC
    ");

    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'reports' => $reports,
        'count' => count($reports)
    ]);

} catch (PDOException $e) {
    error_log("Uploaded reports error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch uploaded reports',
        'reports' => []
    ]);
}
