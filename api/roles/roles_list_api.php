<?php
// File: api/roles/roles_list_api.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../helpers/permission_helper.php';

// Permission check - same as user_permissions.php page
// Allow users who can access the user management page
if (!checkPermission('add_field_data') && !checkPermission('add_lab_data') && !($_SESSION['is_admin'] ?? false)) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "Access denied. You must have permission to access user management.",
        "debug" => [
            "is_admin" => $_SESSION['is_admin'] ?? false,
            "user_id" => $_SESSION['user_id'] ?? null,
            "permissions" => $_SESSION['permissions'] ?? []
        ]
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, name FROM roles ORDER BY name ASC");
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'roles' => $roles
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
