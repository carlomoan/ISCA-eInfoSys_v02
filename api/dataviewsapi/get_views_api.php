<?php
// Load config FIRST to ensure proper session initialization
require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

header('Content-Type: application/json');

if (!checkPermission('data_entry')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    $round = isset($_GET['round']) ? (int)$_GET['round'] : 0;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 500;

    $sql = "SELECT *
            FROM vw_merged_field_lab_data";
    $params = [];
    $where = [];

    if ($round > 0) {
        $where[] = "round = ?";
        $params[] = $round;
    }

    if ($search !== '') {
        $where[] = "(fldrecname LIKE ? OR srtname LIKE ? OR hhcode LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    if ($where) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }

    $sql .= " ORDER BY round DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => count($data),
        'message' => count($data) === 0 ? 'No matching records found' : 'Data loaded successfully'
    ]);
} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $ex->getMessage()
    ]);
}
