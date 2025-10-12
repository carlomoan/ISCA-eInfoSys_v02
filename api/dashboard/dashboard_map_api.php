<?php
// /api/dashboard/cluster_totals.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

if(!checkPermission('view_dashboard')){
    http_response_code(403);
    echo json_encode(['error'=>'Access denied']);
    exit;
}

try {
    // --- Species: female total per household ---
    $speciesCols = ['female_ag','female_af','female_oan','female_culex','female_other_culex','female_aedes'];

    $speciesSum = [];
    foreach($speciesCols as $col) $speciesSum[] = "COALESCE($col,0)";
    $totalFemaleExpr = implode('+',$speciesSum);

    // --- Cluster totals with center (average lat/lng) ---
    $stmt = $pdo->query("
        SELECT clstname AS cluster_name,
               SUM($totalFemaleExpr) AS total_female,
               AVG(h.latitude) AS lat,
               AVG(h.longitude) AS lng
        FROM vw_merged_field_lab_data v
        LEFT JOIN households h ON v.hhcode=h.hhcode
        GROUP BY clstname
        ORDER BY clstname
    ");
    $clusterTotals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Households latest round ---
    $stmt2 = $pdo->query("
        SELECT v.hhcode,
               SUM($totalFemaleExpr) AS total,
               h.latitude, h.longitude
        FROM vw_merged_field_lab_data v
        LEFT JOIN households h ON v.hhcode=h.hhcode
        WHERE h.latitude IS NOT NULL AND h.longitude IS NOT NULL
        GROUP BY v.hhcode
        ORDER BY v.hhcode
    ");
    $householdTable = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'clusterTotals'=>$clusterTotals,
        'householdTable'=>$householdTable
    ], JSON_UNESCAPED_UNICODE);

} catch(PDOException $e){
    http_response_code(500);
    echo json_encode(['error'=>$e->getMessage()]);
}
