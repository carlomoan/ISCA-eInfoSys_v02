<?php
/**
 * Reject Desk Data API
 *
 * Rejects and removes data from desk_* tables with reason
 * Part of the 3-layer data architecture approval workflow
 *
 * @requires Permission: approve_data or is_admin
 * @input JSON: { data_type: 'field'|'lab', hhcode: string, round: int, reason: string }
 * @output JSON: { success: bool, message: string }
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../helpers/permission_helper.php';

header('Content-Type: application/json');

// Permission check - must be admin or have approve_data permission
$user_id = $_SESSION['user_id'] ?? null;
$is_admin = $_SESSION['is_admin'] ?? false;

if (!$user_id) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

if (!$is_admin && !checkPermission('approve_data')) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Access denied. Only supervisors can reject data."]);
    exit;
}

// Read POST body
$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid JSON input"]);
    exit;
}

// Validate required fields
$required = ['data_type', 'hhcode', 'round', 'reason'];
foreach ($required as $field) {
    if (!isset($input[$field]) || $input[$field] === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Missing field: $field"]);
        exit;
    }
}

$data_type = $input['data_type'];
$hhcode = $input['hhcode'];
$round = $input['round'];
$reason = trim($input['reason']);

// Validate data_type
if (!in_array($data_type, ['field', 'lab'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid data_type. Must be 'field' or 'lab'"]);
    exit;
}

// Validate reason (minimum 5 characters)
if (strlen($reason) < 5) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Rejection reason must be at least 5 characters"]);
    exit;
}

// Set table name based on data type
$desk_table = ($data_type === 'field') ? 'desk_field_collector' : 'desk_lab_sorter';

try {
    // Start transaction
    $pdo->beginTransaction();

    // 1. Check if record exists in desk table
    $checkSql = "SELECT * FROM $desk_table WHERE hhcode = :hhcode AND round = :round";
    $stmtCheck = $pdo->prepare($checkSql);
    $stmtCheck->execute([':hhcode' => $hhcode, ':round' => $round]);
    $deskRecord = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$deskRecord) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Record not found in pending data (desk table)"
        ]);
        exit;
    }

    // 2. Log rejection action (optional - will implement in Phase 4)
    // TODO: Add to data_audit_log table with action='rejected' and reason

    // 3. Delete from desk table
    $deleteSql = "DELETE FROM $desk_table WHERE hhcode = :hhcode AND round = :round";
    $stmtDelete = $pdo->prepare($deleteSql);
    $stmtDelete->execute([':hhcode' => $hhcode, ':round' => $round]);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => ucfirst($data_type) . " data rejected and removed for HH: $hhcode, Round: $round",
        "data" => [
            "data_type" => $data_type,
            "hhcode" => $hhcode,
            "round" => $round,
            "reason" => $reason,
            "rejected_by" => $user_id,
            "rejected_at" => date('Y-m-d H:i:s')
        ]
    ]);

} catch (PDOException $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Rejection error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error during rejection: " . $e->getMessage()
    ]);
}
