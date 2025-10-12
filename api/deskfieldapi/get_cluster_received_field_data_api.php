<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// ruhusa
if (!checkPermission('add_lab_data')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$clusterId = $_GET['cluster_id'] ?? null;
$hhcode    = $_GET['hhcode'] ?? null;

if (!$clusterId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cluster ID is required']);
    exit;
}

try {
    if ($hhcode) {
        // Single household info (used for autopopulate hhname + latest field date)
        $stmt = $pdo->prepare("
            SELECT 
                h.hhcode,
                h.head_name AS hhname,
                h.cluster_id,
                MAX(f.field_date) AS field_coll_date
            FROM households h
            INNER JOIN desk_field_collector f ON f.hhcode = h.hhcode AND f.cluster_id = h.cluster_id
            WHERE h.cluster_id = :cluster_id AND h.hhcode = :hhcode
            GROUP BY h.hhcode, h.head_name, h.cluster_id
        ");
        $stmt->execute([
            ':cluster_id' => $clusterId,
            ':hhcode'     => $hhcode
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => $result ? true : false,
            'data'    => $result ?: null
        ]);
    } else {
        // All households under a cluster (for dropdown search)
        $stmt = $pdo->prepare("
            SELECT DISTINCT 
                h.hhcode,
                h.head_name AS hhname
            FROM households h
            INNER JOIN desk_field_collector f ON f.hhcode = h.hhcode AND f.cluster_id = h.cluster_id
            WHERE h.cluster_id = :cluster_id
            ORDER BY h.hhcode
        ");
        $stmt->execute([':cluster_id' => $clusterId]);
        $households = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'count'   => count($households),
            'data'    => $households
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: '.$e->getMessage()]);
}
