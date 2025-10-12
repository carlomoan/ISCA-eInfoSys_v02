<?php
if(session_status()===PHP_SESSION_NONE) session_start();

require_once __DIR__.'/../config/config.php';
require_once ROOT_PATH.'config/db_connect.php';
require_once ROOT_PATH.'helpers/permission_helper.php';

header('Content-Type: application/json');

if(!checkPermission('add_field_data')) {
    http_response_code(403);
    echo json_encode(["success"=>false,"message"=>"Access denied"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

$required = ['hhcode','clstid','round'];
foreach($required as $f){
    if(empty($input[$f])){
        echo json_encode(["success"=>false,"message"=>"Missing field: $f"]);
        exit;
    }
}

try {
    $sql = "UPDATE desk_field_collector_summary
            SET latest_round = :round
            WHERE hhcode = :hhcode AND clstid = :clstid";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':round' => $input['round'],
        ':hhcode' => $input['hhcode'],
        ':clstid' => $input['clstid']
    ]);
    echo json_encode(["success"=>true,"message"=>"Latest round updated"]);
} catch(PDOException $e){
    echo json_encode(["success"=>false,"message"=>$e->getMessage()]);
}

