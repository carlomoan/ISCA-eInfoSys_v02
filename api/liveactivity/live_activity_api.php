<?php
require_once __DIR__ . '/../../config/db_connect.php';
header('Content-Type: application/json');

try {
    $activities = [];

    // Desk Field Collector
    $stmtField = $pdo->query("
        SELECT CONCAT(hhcode,'-',round) AS row_id, user_id, created_at, 'desk_field_collector' AS table_name
        FROM desk_field_collector
        WHERE created_at >= NOW() - INTERVAL 1 DAY
        ORDER BY created_at ASC
    ");
    $fieldRows = $stmtField->fetchAll(PDO::FETCH_ASSOC);

    foreach($fieldRows as $r){
        // Get user name
        $userStmt = $pdo->prepare("SELECT fname, lname FROM users WHERE id=?");
        $userStmt->execute([$r['user_id']]);
        $u = $userStmt->fetch(PDO::FETCH_ASSOC);
        $activities[] = [
            'table' => $r['table_name'],
            'row_id' => $r['row_id'],
            'user_name' => $u ? $u['fname'].' '.$u['lname'] : 'Unknown',
            'created_at' => $r['created_at']
        ];
    }

    // Desk Lab Sorter
    $stmtLab = $pdo->query("
        SELECT CONCAT(hhcode,'-',round) AS row_id, lab_tech_id, cluster_id, created_at, 'desk_lab_sorter' AS table_name
        FROM desk_lab_sorter
        WHERE created_at >= NOW() - INTERVAL 1 DAY
        ORDER BY created_at ASC
    ");
    $labRows = $stmtLab->fetchAll(PDO::FETCH_ASSOC);

    foreach($labRows as $r){
        // Get lab tech name
        $techStmt = $pdo->prepare("SELECT fname,lname FROM users WHERE id=?");
        $techStmt->execute([$r['lab_tech_id']]);
        $t = $techStmt->fetch(PDO::FETCH_ASSOC);
        $activities[] = [
            'table' => $r['table_name'],
            'row_id' => $r['row_id'],
            'user_name' => $t ? $t['fname'].' '.$t['lname'] : 'Unknown',
            'created_at' => $r['created_at']
        ];
    }

    echo json_encode(['success'=>true, 'activities'=>$activities]);

} catch(PDOException $e){
    echo json_encode(['success'=>false,'message'=>'DB error: '.$e->getMessage()]);
}
