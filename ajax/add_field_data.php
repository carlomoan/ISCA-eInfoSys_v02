<?php
// ajax/add_field_data.php

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

if (!checkPermission('add_field_data') && !$isAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$data = $_POST;

// Validate required fields (example)
$required = ['start','end','deviceid','field_coll_date','fldrecname','clstname','clstid','clsttype_lst','round','hhcode','hhname','instanceID'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Field $field is required"]);
        exit;
    }
}

try {
    $sql = "INSERT INTO desk_field_collector (
        start, end, deviceid, ento_fld_frm_title, field_coll_date,
        fldrecname, clstname, clstid, clsttype_lst, round, hhcode, hhname,
        ddrln, aninsln, ddltwrk, ddltwrk_gcomment, lighttrapid,
        collectionbgid, instanceID, user_id
    ) VALUES (
        :start, :end, :deviceid, :ento_fld_frm_title, :field_coll_date,
        :fldrecname, :clstname, :clstid, :clsttype_lst, :round, :hhcode, :hhname,
        :ddrln, :aninsln, :ddltwrk, :ddltwrk_gcomment, :lighttrapid,
        :collectionbgid, :instanceID, :user_id
    )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':start' => $data['start'],
        ':end' => $data['end'],
        ':deviceid' => $data['deviceid'],
        ':ento_fld_frm_title' => $data['ento_fld_frm_title'] ?? null,
        ':field_coll_date' => $data['field_coll_date'],
        ':fldrecname' => $data['fldrecname'],
        ':clstname' => $data['clstname'],
        ':clstid' => $data['clstid'],
        ':clsttype_lst' => $data['clsttype_lst'],
        ':round' => $data['round'],
        ':hhcode' => $data['hhcode'],
        ':hhname' => $data['hhname'],
        ':ddrln' => $data['ddrln'] ?? null,
        ':aninsln' => $data['aninsln'] ?? null,
        ':ddltwrk' => $data['ddltwrk'] ?? null,
        ':ddltwrk_gcomment' => $data['ddltwrk_gcomment'] ?? null,
        ':lighttrapid' => $data['lighttrapid'] ?? null,
        ':collectionbgid' => $data['collectionbgid'] ?? null,
        ':instanceID' => $data['instanceID'],
        ':user_id' => $userId,
    ]);

    echo json_encode(['success' => true, 'message' => 'Field data added successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
