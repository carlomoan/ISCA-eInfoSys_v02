<?php
/**
 * Get Pending Approvals API
 *
 * Fetches all pending data from desk_* tables awaiting supervisor approval
 * Uses merged_data_view for unified display
 *
 * @requires Permission: approve_data or is_admin (or view_data for read-only)
 * @input GET/POST: { data_type?: 'field'|'lab'|'all', round?: int, user_id?: int }
 * @output JSON: { success: bool, data: array, count: int }
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../helpers/permission_helper.php';

header('Content-Type: application/json');

// Permission check
$user_id = $_SESSION['user_id'] ?? null;
$is_admin = $_SESSION['is_admin'] ?? false;

if (!$user_id) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

// Allow users with approve_data, view_data permissions, or admins
if (!$is_admin && !checkPermission('approve_data') && !checkPermission('view_data')) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Access denied"]);
    exit;
}

// Get filters from GET or POST
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true) ?? [];
} else {
    $input = $_GET;
}

$filter_data_type = $input['data_type'] ?? 'all'; // 'field', 'lab', or 'all'
$filter_round = isset($input['round']) && $input['round'] !== '' ? (int)$input['round'] : null;
$filter_user_id = isset($input['user_id']) && $input['user_id'] !== '' ? (int)$input['user_id'] : null;

// Validate data_type
if (!in_array($filter_data_type, ['field', 'lab', 'all'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid data_type. Must be 'field', 'lab', or 'all'"]);
    exit;
}

try {
    // Build query using merged_data_view
    $sql = "SELECT
                data_type,
                source_table,
                data_status,
                data_source,
                round,
                hhcode,
                hhname,
                clstid,
                clstname,
                field_coll_date,
                fldrecname,
                lab_date,
                srtname,
                deviceid,
                instanceID,
                user_id,
                created_at
            FROM merged_data_view
            WHERE data_status = 'pending'";

    $params = [];

    // Add data_type filter
    if ($filter_data_type !== 'all') {
        $sql .= " AND data_type = :data_type";
        $params[':data_type'] = $filter_data_type;
    }

    // Add round filter
    if ($filter_round !== null) {
        $sql .= " AND round = :round";
        $params[':round'] = $filter_round;
    }

    // Add user_id filter
    if ($filter_user_id !== null) {
        $sql .= " AND user_id = :user_id";
        $params[':user_id'] = $filter_user_id;
    }

    // Order by most recent first
    $sql .= " ORDER BY created_at DESC, round DESC";

    // Execute query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pending_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get summary counts
    $countSql = "SELECT
                    data_type,
                    COUNT(*) as count
                FROM merged_data_view
                WHERE data_status = 'pending'
                GROUP BY data_type";

    $stmtCount = $pdo->prepare($countSql);
    $stmtCount->execute();
    $counts = $stmtCount->fetchAll(PDO::FETCH_ASSOC);

    $summary = [
        'field' => 0,
        'lab' => 0,
        'total' => 0
    ];

    foreach ($counts as $row) {
        $summary[$row['data_type']] = (int)$row['count'];
        $summary['total'] += (int)$row['count'];
    }

    echo json_encode([
        "success" => true,
        "data" => $pending_data,
        "count" => count($pending_data),
        "summary" => $summary,
        "filters" => [
            "data_type" => $filter_data_type,
            "round" => $filter_round,
            "user_id" => $filter_user_id
        ]
    ]);

} catch (PDOException $e) {
    error_log("Get pending approvals error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
