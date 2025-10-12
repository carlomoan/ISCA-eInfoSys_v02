<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/ISCA-eInfoSys_v02/config/db_connect.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method.']);
    exit;
}

$id = $_POST['id'] ?? null;
$table = $_POST['table'] ?? null;

if (!$id || !$table) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing report ID or table.']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Report deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Report not found or already deleted.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error occurred.']);
}
