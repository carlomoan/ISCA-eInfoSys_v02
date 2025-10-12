<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/ISCA-eInfoSys_v02/config/constants.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

if (!checkPermission('download_field_data')) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

require_once ROOT_PATH . 'config/db_connect.php';

$userId = $_SESSION['user_id'] ?? null;
$id = $_GET['id'] ?? null;
$filetype = $_GET['filetype'] ?? 'excel';
$exportAll = isset($_GET['export']) && $_GET['export'] === 'all';

if ($exportAll) {
    // Export all user field data
    $stmt = $pdo->prepare("SELECT * FROM field_collector WHERE user_id = ? ORDER BY round DESC, hhcode ASC");
    $stmt->execute([$userId]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Export single record by hhcode
    if (!$id) {
        http_response_code(400);
        echo "Invalid request.";
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM field_collector WHERE hhcode = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$id, $userId]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$records) {
        http_response_code(404);
        echo "Record not found or access denied.";
        exit;
    }
}

// Export logic for Excel/CSV
if ($filetype === 'excel' || $filetype === 'csv') {
    $filename = $exportAll ? "field_data_all_" . date('Ymd_His') : "field_data_" . ($id ?? 'unknown') . "_" . date('Ymd_His');
    $filename .= ($filetype === 'excel') ? ".csv" : ".csv"; // Simplified: CSV for both for now

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    if (!empty($records)) {
        // Output header row
        fputcsv($output, array_keys($records[0]));
        // Output data rows
        foreach ($records as $row) {
            fputcsv($output, $row);
        }
    }
    fclose($output);
    exit;
}

// PDF export - Optional: Implement if you have PDF library

http_response_code(400);
echo "Unsupported file type.";
exit;
