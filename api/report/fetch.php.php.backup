<?php
// api/report/fetch.php

header('Content-Type: application/json');
require_once($_SERVER['DOCUMENT_ROOT'] . '/ISCA-eInfoSys_v02/config/config.php');
require_once(ROOT_PATH . 'config/db_connect.php');

// Safety: Allow only specific views
$allowedViews = [
    'vw_reports_summary',
    'vw_annual_reports',
    'vw_monthly_stats',
    'vw_lab_reports',
    'vw_field_data_reports'
    // Add more if needed
];

// Get and sanitize view name
$view = $_GET['view'] ?? '';
$view = preg_replace('/[^a-zA-Z0-9_]/', '', $view);

// Check if view is allowed
if (!in_array($view, $allowedViews)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid view selected.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM `$view` ORDER BY 1 DESC LIMIT 100");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $results
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error.',
        'error' => $e->getMessage()
    ]);
}
