<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../helpers/permission_helper.php';

if (!checkPermission('view_lab_data')) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$rawInput = file_get_contents("php://input");
$input = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE && !empty($rawInput)) {
    echo json_encode(["success" => false, "message" => "Invalid JSON input"]);
    exit;
}

$search      = $input['search']    ?? ($_GET['search']    ?? null);
$page        = isset($input['page']) ? (int)$input['page'] : (isset($_GET['page']) ? (int)$_GET['page'] : 1);
$rowsPerPage = isset($input['rowsPerPage']) ? (int)$input['rowsPerPage'] : (isset($_GET['rowsPerPage']) ? (int)$_GET['rowsPerPage'] : 10);

if ($page < 1) $page = 1;
if ($rowsPerPage < 1) $rowsPerPage = 10;

try {
    $query = "SELECT * FROM desk_lab_sorter WHERE 1=1";
    $params = [];

    if ($search) {
        $query .= " AND (hhcode LIKE :search OR hhname LIKE :search OR clustername LIKE :search)";
        $params[':search'] = "%$search%";
    }

    // Count total
    $countQuery = "SELECT COUNT(*) FROM ($query) as sub";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalRecords = (int)$countStmt->fetchColumn();

    // Pagination
    $offset = ($page - 1) * $rowsPerPage;
    $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($query);

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $rowsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $data,
        "totalRecords" => $totalRecords,
        "currentPage" => $page,
        "rowsPerPage" => $rowsPerPage
    ]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
