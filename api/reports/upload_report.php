<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/db_connect.php';
require_once '../../helpers/permission_helper.php';

header('Content-Type: application/json');

if (!checkPermission('upload_report') && !($_SESSION['is_admin'] ?? false)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not have permission to upload reports.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$round = filter_input(INPUT_POST, 'round', FILTER_VALIDATE_INT);
$cluster_name = filter_input(INPUT_POST, 'cluster_name', FILTER_SANITIZE_STRING);
$file = $_FILES['report_file'] ?? null;

if (!$round || !$cluster_name || !$file) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// Validate file type
$allowedTypes = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$fileType = $finfo->file($file['tmp_name']);

if (!in_array($fileType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only Excel and CSV allowed.']);
    exit;
}

// Save uploaded file
$uploadDir = defined('UPLOADS_REPORTS') ? UPLOADS_REPORTS : dirname(dirname(__DIR__)) . '/uploads/reports/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$filename = basename($file['name']);
$ext = pathinfo($filename, PATHINFO_EXTENSION);
$timestamp = date('Ymd_His');
$safeName = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', pathinfo($filename, PATHINFO_FILENAME));
$newFileName = $safeName . '_' . $timestamp . '.' . $ext;
$destination = $uploadDir . $newFileName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file.']);
    exit;
}

// Insert into DB
try {
    $stmt = $pdo->prepare("INSERT INTO uploaded_reports (file_name, report_type, round, cluster_name, uploaded_at, file_path, uploaded_by) VALUES (?, 'Uploaded', ?, ?, NOW(), ?, ?)");
    $stmt->execute([$filename, $round, $cluster_name, $newFileName, $_SESSION['user_id'] ?? 0]);
} catch (Exception $e) {
    error_log("Upload DB insert error: " . $e->getMessage());

    // Delete uploaded file if DB insert fails
    if (file_exists($destination)) {
        unlink($destination);
    }

    // Provide helpful error message
    if (strpos($e->getMessage(), "uploaded_reports") !== false && strpos($e->getMessage(), "doesn't exist") !== false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database table missing. Please run: database/migrations/create_uploaded_reports_table.sql']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save report record: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => true, 'message' => 'Report uploaded successfully.']);
