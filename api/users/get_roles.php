<?php
/**
 * Get Roles API
 * Returns all available user roles
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, name, description FROM roles WHERE is_active = 1 ORDER BY name ASC");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'roles' => $roles
    ]);

} catch (PDOException $e) {
    error_log("Get roles error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch roles',
        'roles' => []
    ]);
}
