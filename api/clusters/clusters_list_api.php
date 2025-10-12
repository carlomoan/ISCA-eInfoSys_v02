<?php
require_once __DIR__ . '/../../config/db_connect.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT cluster_id, cluster_name 
        FROM clusters 
        ORDER BY cluster_name ASC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $clusters = [];
    foreach($rows as $row){
        $clusters[] = [
            'id' => $row['cluster_id'],
            'name' => $row['cluster_name']
        ];
    }
    echo json_encode(['success'=>true,'items'=>$clusters]);
} catch(PDOException $e){
    echo json_encode(['success'=>false,'message'=>'DB error: '.$e->getMessage()]);
}
