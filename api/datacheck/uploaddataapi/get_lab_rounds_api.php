<?php
session_start();

require_once($_SERVER['DOCUMENT_ROOT'] . '/ISCA-eInfoSys_v02/config/db_connect.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/ISCA-eInfoSys_v02/helpers/permission_helper.php');

header('Content-Type: application/json');

if (!checkPermission('data_entry')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    // Pata distinct rounds kutoka temp_lab_sorter
    $stmt = $pdo->query("SELECT DISTINCT round FROM temp_Lab_sorter ORDER BY round DESC");
    $rounds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'rounds' => $rounds,
    ]);
} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $ex->getMessage()
    ]);
}
