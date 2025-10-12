<?php
// ajax/add_lab_data.php

session_start();
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/helpers/permissions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$isAdmin = $_SESSION['is_admin'] ?? false;

if (!checkPermission('add_lab_data') && !$isAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$data = $_POST;

// Required fields validation example
$required = ['start', 'end', 'deviceid', 'lab_date', 'srtname', 'round', 'hhname', 'hhcode', 'instanceID'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Field $field is required"]);
        exit;
    }
}

try {
    $sql = "INSERT INTO lab_sorter (
        start, end, deviceid, ento_lab_frm_title, lab_date, srtname, round, hhname, hhcode, field_coll_date,
        male_ag, female_ag, fed_ag, unfed_ag, gravid_ag, semi_gravid_ag,
        male_af, female_af, fed_af, unfed_af, gravid_af, semi_gravid_af,
        male_oan, female_oan, fed_oan, unfed_oan, gravid_oan, semi_gravid_oan,
        male_culex, female_culex, fed_culex, unfed_culex, gravid_culex, semi_gravid_culex,
        male_other_culex, female_other_culex, male_aedes, female_aedes,
        instanceID, user_id
    ) VALUES (
        :start, :end, :deviceid, :ento_lab_frm_title, :lab_date, :srtname, :round, :hhname, :hhcode, :field_coll_date,
        :male_ag, :female_ag, :fed_ag, :unfed_ag, :gravid_ag, :semi_gravid_ag,
        :male_af, :female_af, :fed_af, :unfed_af, :gravid_af, :semi_gravid_af,
        :male_oan, :female_oan, :fed_oan, :unfed_oan, :gravid_oan, :semi_gravid_oan,
        :male_culex, :female_culex, :fed_culex, :unfed_culex, :gravid_culex, :semi_gravid_culex,
        :male_other_culex, :female_other_culex, :male_aedes, :female_aedes,
        :instanceID, :user_id
    )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':start' => $data['start'],
        ':end' => $data['end'],
        ':deviceid' => $data['deviceid'],
        ':ento_lab_frm_title' => $data['ento_lab_frm_title'] ?? null,
        ':lab_date' => $data['lab_date'],
        ':srtname' => $data['srtname'],
        ':round' => $data['round'],
        ':hhname' => $data['hhname'],
        ':hhcode' => $data['hhcode'],
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
        ':instanceID' => $data['instanceID'],
        ':user_id' => $userId,
    ]);

    echo json_encode(['success' => true, 'message' => 'Lab data added successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
