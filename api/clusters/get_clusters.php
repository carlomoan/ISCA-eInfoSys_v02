<?php
/**
 * Get Clusters API
 * Returns all clusters from the database
 */

require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    $sessionConfig = Config::session();
    session_name($sessionConfig['name']);
    session_start();
}

header('Content-Type: application/json');

try {
    // Query to get all clusters with state information
    $stmt = $pdo->query("
        SELECT
            c.cluster_id,
            c.cluster_name,
            c.region_name,
            c.district_name,
            c.ward_name,
            c.created_at,
            cs.state_name as status
        FROM clusters c
        LEFT JOIN cluster_states cs ON c.cluster_state_id = cs.id
        ORDER BY c.cluster_name ASC
    ");

    $clusters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'clusters' => $clusters,
        'count' => count($clusters)
    ]);

} catch (PDOException $e) {
    error_log("Get clusters error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch clusters: ' . $e->getMessage(),
        'clusters' => []
    ]);
}
