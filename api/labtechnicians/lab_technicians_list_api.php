<?php
require_once __DIR__ . '/../../config/db_connect.php';
header('Content-Type: application/json');

try {
    // Lab technicians are stored in 'lab_technicians' table
    // Join with users table to get full name
    $stmt = $pdo->query("
        SELECT lt.lab_tech_id, u.fname, u.lname
        FROM lab_technicians lt
        LEFT JOIN users u ON lt.user_id = u.id
        ORDER BY u.fname, u.lname
    ");
    
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $labTechs = [];
    foreach($rows as $row){
        $labTechs[] = [
            'id' => $row['lab_tech_id'],
            'name' => trim($row['fname'].' '.$row['lname'])
        ];
    }
    
    echo json_encode(['success'=>true,'items'=>$labTechs]);

} catch(PDOException $e){
    echo json_encode(['success'=>false,'message'=>'DB error: '.$e->getMessage()]);
}
