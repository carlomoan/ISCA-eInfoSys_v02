<?php
require_once __DIR__.'/../../config/db_connect.php';

$stmt = $pdo->query("
    SELECT l.*, u1.fname AS user_name, u2.fname AS performed_by_name
    FROM user_activity_log l
    LEFT JOIN users u1 ON l.user_id=u1.id
    LEFT JOIN users u2 ON l.performed_by=u2.id
    ORDER BY l.created_at DESC
    LIMIT 10
");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['success'=>true,'notifications'=>$logs]);
