<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../helpers/permission_helper.php';

// Permission check
if (!checkPermission('add_field_data')) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Access denied"]);
    exit;
}

header('Content-Type: application/json');

// Get logged in user
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

// Read POST body
$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    echo json_encode(["success" => false, "message" => "Invalid JSON input"]);
    exit;
}

// Required fields
$required = ['round','hhcode','hhname','clstid','clstname','field_coll_date'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        echo json_encode(["success" => false, "message" => "Missing field: $field"]);
        exit;
    }
}

// Check for existing record
try {
    $checkSql = "SELECT COUNT(*) FROM desk_field_collector WHERE hhcode=:hhcode AND round=:round";
    $stmtCheck = $pdo->prepare($checkSql);
    $stmtCheck->execute([
        ':hhcode' => $input['hhcode'],
        ':round' => $input['round']
    ]);
    if ($stmtCheck->fetchColumn() > 0) {
        echo json_encode(["success" => false, "message" => "Record already exists for Household '{$input['hhcode']}' and Round '{$input['round']}'"]);
        exit;
    }
} catch(PDOException $e){
    echo json_encode(["success"=>false,"message"=>"Database check error: ".$e->getMessage()]);
    exit;
}

// Prepare insert
$sql = "INSERT INTO desk_field_collector 
        (start, end, deviceid, ento_fld_frm_title, field_coll_date, fldrecname,
         clstname, clstid, clsttype_lst, round, hhcode, hhname, ddrln, aninsln,
         ddltwrk, ddltwrk_gcomment, lighttrapid, collectionbgid, user_id, instanceID)
        VALUES 
        (:start, :end, :deviceid, :ento_fld_frm_title, :field_coll_date, :fldrecname,
         :clstname, :clstid, :clsttype_lst, :round, :hhcode, :hhname, :ddrln, :aninsln,
         :ddltwrk, :ddltwrk_gcomment, :lighttrapid, :collectionbgid, :user_id, :instanceID)";

$stmt = $pdo->prepare($sql);

try {
    $success = $stmt->execute([
        ':start'            => $input['start'] ?? null,
        ':end'              => $input['end'] ?? null,
        ':deviceid'         => $input['deviceid'] ?? null,
        ':ento_fld_frm_title' => $input['ento_fld_frm_title'] ?? null,
        ':field_coll_date'  => $input['field_coll_date'],
        ':fldrecname'       => $input['fldrecname'] ?? null,
        ':clstname'         => $input['clstname'],
        ':clstid'           => $input['clstid'],
        ':clsttype_lst'     => $input['clsttype_lst'] ?? null,
        ':round'            => $input['round'],
        ':hhcode'           => $input['hhcode'],
        ':hhname'           => $input['hhname'],
        ':ddrln'            => $input['ddrln'] ?? null,
        ':aninsln'          => $input['aninsln'] ?? null,
        ':ddltwrk'          => $input['ddltwrk'] ?? null,
        ':ddltwrk_gcomment' => $input['ddltwrk_gcomment'] ?? null,
        ':lighttrapid'      => $input['lighttrapid'] ?? null,
        ':collectionbgid'   => $input['collectionbgid'] ?? null, 
        ':user_id'          => $user_id,
        ':instanceID'       => $input['instanceID'] ?? null,
    ]);

    if ($success) {
        echo json_encode([
            "success" => true, 
            "message" => "Field data added successfully for Household number '{$input['hhcode']}' and Round '{$input['round']}'"
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to add data"]);
    }

} catch(PDOException $e){
    echo json_encode(["success"=>false,"message"=>"Database error: ".$e->getMessage()]);
}
