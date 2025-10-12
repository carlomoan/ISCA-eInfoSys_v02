<?php
/**
 * Get Generated Reports API
 * Returns data from selected view with filters
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';

header('Content-Type: application/json');

$view = $_GET['view'] ?? 'vw_merged_field_lab_data';
$round = $_GET['round'] ?? null;
$limit = intval($_GET['limit'] ?? 10);
$search = $_GET['search'] ?? '';

// Validate view
$allowedViews = [
    'vw_merged_field_lab_data',
    'vw_reports_summary',
    'vw_lab_reports',
    'vw_field_data_reports'
];

if (!in_array($view, $allowedViews)) {
    echo json_encode(['success' => false, 'message' => 'Invalid view', 'data' => []]);
    exit;
}

try {
    $sql = "SELECT * FROM $view WHERE 1=1";
    $params = [];

    if ($round && $round !== '0') {
        $sql .= " AND round = ?";
        $params[] = $round;
    }

    if ($search) {
        $sql .= " AND (hhname LIKE ? OR clstname LIKE ? OR field_recorder LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    $sql .= " LIMIT ?";
    $params[] = $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $data,
        'count' => count($data)
    ]);

} catch (PDOException $e) {
    error_log("Generated reports error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch data',
        'data' => []
    ]);
}
