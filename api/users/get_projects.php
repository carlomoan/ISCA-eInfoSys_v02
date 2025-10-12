<?php
/**
 * Get Projects API
 * Returns all available projects (admin use only)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

header('Content-Type: application/json');

// Check if user has permission (admin use only)
if (!checkPermission('add_user') && !($_SESSION['is_admin'] ?? false)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied',
        'projects' => []
    ]);
    exit;
}

try {
    $stmt = $pdo->query("SELECT project_id, project_name, principal_investigator, project_status FROM projects ORDER BY project_name ASC");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'projects' => $projects
    ]);

} catch (PDOException $e) {
    error_log("Get projects error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch projects',
        'projects' => []
    ]);
}
