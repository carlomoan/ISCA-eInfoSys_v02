<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db_connect.php';

header("Content-Type: application/json");

$userId = $_SESSION['user_id'] ?? null;
$roleId = $_SESSION['role_id'] ?? null;

if (!$userId || !in_array($roleId, [1, 7])) {
    echo json_encode(["success" => false, "message" => "Access denied."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

$round = $_POST['round'] ?? null;
$clusterName = $_POST['cluster_name'] ?? 'all';
$file = $_FILES['report_file'] ?? null;

if (!$round || !$file) {
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit;
}

// ✅ Validate file type
$allowedExtensions = ['xls', 'xlsx', 'csv'];
$fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($fileExt, $allowedExtensions)) {
    echo json_encode(["success" => false, "message" => "Invalid file type. Allowed: xls, xlsx, csv."]);
    exit;
}

// ✅ Create upload directory if not exists
$uploadDir = dirname(__DIR__) . "/uploads/reports/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// ✅ Generate unique file name
$uniqueName = uniqid("report_", true) . "." . $fileExt;
$uploadPath = $uploadDir . $uniqueName;

try {
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // ✅ Save to DB - Check if table exists first
        $stmt = $pdo->prepare("INSERT INTO uploaded_reports (file_name, report_type, round, cluster_name, file_path, uploaded_by, uploaded_at)
                               VALUES (:file_name, :report_type, :round, :cluster_name, :file_path, :uploaded_by, NOW())");
        $stmt->execute([
            ':file_name' => $file['name'],
            ':report_type' => 'Uploaded Report',
            ':round' => $round,
            ':cluster_name' => $clusterName,
            ':file_path' => $uniqueName,
            ':uploaded_by' => $userId
        ]);

        echo json_encode(["success" => true, "message" => "Report uploaded successfully."]);
    } else {
        $uploadError = error_get_last();
        error_log("File upload failed: " . print_r($uploadError, true));
        echo json_encode(["success" => false, "message" => "Failed to move uploaded file. Check directory permissions."]);
    }
} catch (Exception $e) {
    error_log("Upload report error: " . $e->getMessage());

    // If table doesn't exist, provide helpful error
    if (strpos($e->getMessage(), "uploaded_reports") !== false && strpos($e->getMessage(), "doesn't exist") !== false) {
        echo json_encode(["success" => false, "message" => "Database table 'uploaded_reports' does not exist. Please run the database migration script."]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
}
