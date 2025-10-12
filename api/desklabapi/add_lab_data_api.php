<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

header('Content-Type: application/json');

// Check permission
if (!checkPermission('add_lab_data')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

// Accept JSON payload
$data = json_decode(file_get_contents('php://input'), true);

// Required fields
$required = ['start','end','deviceid','lab_date','srtname','round','hhcode','hhname','cluster_id','instanceID'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

try {
    $sql = "INSERT INTO desk_lab_sorter (
        start, end, deviceid, ento_lab_frm_title, lab_date,
        srtname, round, hhname, hhcode, field_coll_date,
        male_ag, female_ag, fed_ag, unfed_ag, gravid_ag, semi_gravid_ag,
        male_af, female_af, fed_af, unfed_af, gravid_af, semi_gravid_af,
        male_oan, female_oan, fed_oan, unfed_oan, gravid_oan, semi_gravid_oan,
        male_culex, female_culex, fed_culex, unfed_culex, gravid_culex, semi_gravid_culex,
        male_other_culex, female_other_culex,
        male_aedes, female_aedes,
        user_id, cluster_id, instanceID, created_at
    ) VALUES (
        :start, :end, :deviceid, :ento_lab_frm_title, :lab_date,
        :srtname, :round, :hhname, :hhcode, :field_coll_date,
        :male_ag, :female_ag, :fed_ag, :unfed_ag, :gravid_ag, :semi_gravid_ag,
        :male_af, :female_af, :fed_af, :unfed_af, :gravid_af, :semi_gravid_af,
        :male_oan, :female_oan, :fed_oan, :unfed_oan, :gravid_oan, :semi_gravid_oan,
        :male_culex, :female_culex, :fed_culex, :unfed_culex, :gravid_culex, :semi_gravid_culex,
        :male_other_culex, :female_other_culex,
        :male_aedes, :female_aedes,
        :user_id, :cluster_id, :instanceID, NOW()
    )";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':start' => $data['start'] ?? null,
        ':end' => $data['end'] ?? null,
        ':deviceid' => $data['deviceid'] ?? null,
        ':ento_lab_frm_title' => $data['ento_lab_frm_title'] ?? null,
        ':lab_date' => $data['lab_date'] ?? null,
        ':srtname' => $data['srtname'] ?? null,
        ':round' => $data['round'] ?? null,
        ':hhname' => $data['hhname'] ?? null,
        ':hhcode' => $data['hhcode'] ?? null,
        ':field_coll_date' => $data['field_coll_date'] ?? null,

        ':male_ag' => $data['male_ag'] ?? 0,
        ':female_ag' => $data['female_ag'] ?? 0,
        ':fed_ag' => $data['fed_ag'] ?? 0,
        ':unfed_ag' => $data['unfed_ag'] ?? 0,
        ':gravid_ag' => $data['gravid_ag'] ?? 0,
        ':semi_gravid_ag' => $data['semi_gravid_ag'] ?? 0,

        ':male_af' => $data['male_af'] ?? 0,
        ':female_af' => $data['female_af'] ?? 0,
        ':fed_af' => $data['fed_af'] ?? 0,
        ':unfed_af' => $data['unfed_af'] ?? 0,
        ':gravid_af' => $data['gravid_af'] ?? 0,
        ':semi_gravid_af' => $data['semi_gravid_af'] ?? 0,

        ':male_oan' => $data['male_oan'] ?? 0,
        ':female_oan' => $data['female_oan'] ?? 0,
        ':fed_oan' => $data['fed_oan'] ?? 0,
        ':unfed_oan' => $data['unfed_oan'] ?? 0,
        ':gravid_oan' => $data['gravid_oan'] ?? 0,
        ':semi_gravid_oan' => $data['semi_gravid_oan'] ?? 0,

        ':male_culex' => $data['male_culex'] ?? 0,
        ':female_culex' => $data['female_culex'] ?? 0,
        ':fed_culex' => $data['fed_culex'] ?? 0,
        ':unfed_culex' => $data['unfed_culex'] ?? 0,
        ':gravid_culex' => $data['gravid_culex'] ?? 0,
        ':semi_gravid_culex' => $data['semi_gravid_culex'] ?? 0,

        ':male_other_culex' => $data['male_other_culex'] ?? 0,
        ':female_other_culex' => $data['female_other_culex'] ?? 0,

        ':male_aedes' => $data['male_aedes'] ?? 0,
        ':female_aedes' => $data['female_aedes'] ?? 0,

        ':user_id' => $userId,
        ':cluster_id' => $data['cluster_id'] ?? null,
        ':instanceID' => $data['instanceID'] ?? null
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "success" => true, 
            "message" => "Laboratory data added successfully for Household '{$data['hhcode']}' and Round '{$data['round']}'"
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to add data"]);
    }

} catch(PDOException $e){
    echo json_encode(["success"=>false,"message"=>"Database error: ".$e->getMessage()]);
}
