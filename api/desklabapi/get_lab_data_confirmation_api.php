<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../helpers/permission_helper.php';

// Hakikisha user ana ruhusa
if (!checkPermission('add_lab_data')) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Access denied"]);
    exit;
}

header('Content-Type: application/json');

$cluster_id = $_GET['cluster_id'] ?? null;
$hhcode     = $_GET['hhcode'] ?? null;

if (!$cluster_id || !$hhcode) {
    echo json_encode(["success" => false, "message" => "Missing cluster_id or hhcode"]);
    exit;
}

try {
    // Tafuta latest round
    $stmt = $pdo->prepare("SELECT MAX(round) as latest_round 
                           FROM desk_lab_sorter 
                           WHERE cluster_id = :cluster_id 
                           AND hhcode = :hhcode");
    $stmt->execute([
        ':cluster_id' => $cluster_id,
        ':hhcode'     => $hhcode
    ]);
    $latest = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$latest || !$latest['latest_round']) {
        echo json_encode(["success" => true, "data" => null]);
        exit;
    }

    // Fetch meta info kutoka desk_field_collector badala ya lab_sorter
    $stmt = $pdo->prepare("SELECT start as starttime, end as endtime, round, clstid as cluster_id, hhcode, fldrecname as Field_recorder, deviceid
                           FROM desk_field_collector 
                           WHERE clstid = :cluster_id 
                           AND hhcode = :hhcode 
                           AND round = :round 
                           ORDER BY round DESC LIMIT 1");
    $stmt->execute([
        ':cluster_id' => $cluster_id,
        ':hhcode'     => $hhcode,
        ':round'      => $latest['latest_round']
    ]);
    $meta = $stmt->fetch(PDO::FETCH_ASSOC);



    echo json_encode([
        "success" => true,
        "meta"    => $meta,
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
