<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once($_SERVER['DOCUMENT_ROOT'] . '/ISCA-eInfoSys_v02/config/db_connect.php');

$userId = $_SESSION['user_id'] ?? null;
$permissions = $_SESSION['permissions'] ?? [];
$roleId = $_SESSION['role_id'] ?? null;

// Check user permissions - upload_reports permission or role admin (1)
if (!$userId || (!in_array('upload_report', $permissions) && $roleId != 1)) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to upload reports.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['report_file']) || $_FILES['report_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
        exit;
    }

    $allowedExtensions = ['xls', 'xlsx', 'csv'];
    $fileName = $_FILES['report_file']['name'];
    $fileTmp = $_FILES['report_file']['tmp_name'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExtensions)) {
        echo json_encode(['success' => false, 'message' => 'Only Excel/CSV files are allowed.']);
        exit;
    }

    $round = $_POST['round'] ?? null;
    $clusterName = $_POST['cluster_name'] ?? 'all';
    $reportType = 'excel';

    if (!$round || !is_numeric($round) || $round <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valid round number is required.']);
        exit;
    }

    // Sanitize file name for safety
    $sanitizedFileName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $fileName);
    $newFileName = time() . '_' . $sanitizedFileName;

    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/ISCA-eInfoSys_v02/uploads/reports/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory.']);
        exit;
    }

    $filePath = $uploadDir . $newFileName;
    $dbPath = 'uploads/reports/' . $newFileName;

    if (!move_uploaded_file($fileTmp, $filePath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO uploaded_reports (file_name, file_path, uploaded_by, report_type, round, cluster_name) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$newFileName, $dbPath, $userId, $reportType, $round, $clusterName]);

        echo json_encode(['success' => true, 'message' => 'Report uploaded successfully!']);
    } catch (Exception $e) {
        // Delete file if DB insert fails to avoid orphan files
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
