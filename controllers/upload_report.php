<?php
// Load config FIRST to ensure proper session initialization
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db_connect.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
$permissions = $_SESSION['permissions'] ?? [];
$roleId = $_SESSION['role_id'] ?? null;
$isAdmin = $_SESSION['is_admin'] ?? false;

// Check if user is logged in
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to upload reports.']);
    exit;
}

// Check user permissions - admin, role_id 1, or has upload_report permission
$hasPermission = $isAdmin || $roleId == 1 || in_array('upload_report', $permissions);

if (!$hasPermission) {
    // Add debug info for development
    error_log("Upload permission denied - user_id: $userId, role_id: $roleId, is_admin: " . ($isAdmin ? 'true' : 'false') . ", permissions: " . json_encode($permissions));
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

    $uploadDir = dirname(__DIR__) . '/uploads/reports/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
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
        error_log("Upload report error: " . $e->getMessage());

        // Provide helpful error message if table doesn't exist
        if (strpos($e->getMessage(), "uploaded_reports") !== false && strpos($e->getMessage(), "doesn't exist") !== false) {
            echo json_encode(['success' => false, 'message' => 'Database table missing. Please run: database/migrations/create_uploaded_reports_table.sql']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
