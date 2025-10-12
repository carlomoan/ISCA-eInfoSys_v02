<?php
session_start();
header('Content-Type: application/json');
require_once($_SERVER['DOCUMENT_ROOT'].'/ISCA-eInfoSys_v02/config/db_connect.php');

$userId      = $_SESSION['user_id'] ?? null;
$roleId      = $_SESSION['role_id'] ?? null;
$permissions = $_SESSION['permissions'] ?? [];

if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = trim($_POST['action'] ?? '');

if ($action === 'upload') {
    if (!in_array('upload_reports', $permissions) && $roleId != 1) {
        echo json_encode(['status' => 'error', 'message' => 'You do not have permission to upload reports.']);
        exit;
    }

    if (!isset($_FILES['report_file']) || $_FILES['report_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error.']);
        exit;
    }

    $allowedExt = ['csv', 'xls', 'xlsx'];
    $ext = strtolower(pathinfo($_FILES['report_file']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only CSV or Excel allowed.']);
        exit;
    }

    $uploadDir = $_SERVER['DOCUMENT_ROOT'].'/ISCA-eInfoSys_v02/uploads/reports/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9_\.-]/", "_", $_FILES['report_file']['name']);
    $targetPath = $uploadDir . $fileName;
    $relativePath = 'uploads/reports/' . $fileName;

    if (move_uploaded_file($_FILES['report_file']['tmp_name'], $targetPath)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO uploaded_reports 
                (file_name, file_path, uploaded_by, report_type, round, cluster_name) 
                VALUES (?,?,?,?,?,?)");
            $stmt->execute([
                $_POST['report_type'] ?? '',
                $fileName,
                $userId,
                $_POST['report_type'] ?? '',
                $_POST['round'] ?? '',
                $_POST['cluster_name'] ?? ''
            ]);

            echo json_encode(['status' => 'success', 'message' => 'Report uploaded successfully!']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'DB Error: '.$e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file.']);
    }
    exit;
}

// Default invalid action
echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
exit;
