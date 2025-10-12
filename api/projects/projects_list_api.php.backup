<?php
require_once __DIR__ . '/../../config/db_connect.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT project_id, project_name 
        FROM projects 
        ORDER BY project_name ASC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $projects = [];
    foreach($rows as $row){
        $projects[] = [
            'id' => (int)$row['project_id'],
            'name' => $row['project_name']
        ];
    }
    echo json_encode(['success'=>true,'items'=>$projects]);
} catch(PDOException $e){
    echo json_encode(['success'=>false,'message'=>'DB error: '.$e->getMessage()]);
}
