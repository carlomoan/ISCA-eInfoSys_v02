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
$column = $_POST['column'] ?? null;
$value = $_POST['value'] ?? null;

if (!$id || !$table || !$column || $value === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE `$table` SET `$column` = ? WHERE id = ?");
    $stmt->execute([$value, $id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Report updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes made or report not found.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error occurred.']);
}
