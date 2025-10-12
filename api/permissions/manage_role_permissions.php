<?php
/**
 * ================================================
 * Role-Permission Management API
 * ================================================
 *
 * Handles AJAX requests for role-permission management
 * Maintains session and enforces permissions
 */

// Load config FIRST to ensure proper session initialization
require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';
require_once ROOT_PATH . 'classes/Permission.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    $sessionConfig = Config::session();
    session_name($sessionConfig['name']);
    session_start();
}

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please login.'
    ]);
    exit;
}

// Check if user has permission to manage roles
if (!checkPermission('manage_roles') && !($_SESSION['is_admin'] ?? false)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. You need "manage_roles" permission.'
    ]);
    exit;
}

$permission = new Permission($pdo);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {

        case 'get_role_permissions':
            // Get permissions assigned to a specific role
            $roleId = (int)($_GET['role_id'] ?? 0);
            $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;

            if ($roleId <= 0) {
                throw new Exception('Invalid role ID');
            }

            // Use new project-aware method
            $perms = $permission->getRolePermissionsByProject($roleId, $projectId);

            echo json_encode([
                'success' => true,
                'permissions' => $perms,
                'count' => count($perms)
            ]);
            break;

        case 'save_role_permissions':
            // Save/update permissions for a role with project scope
            $roleId = (int)($_POST['role_id'] ?? 0);
            $projectId = (int)($_POST['project_id'] ?? 0); // 0 = global
            $permissionIds = json_decode($_POST['permission_ids'] ?? '[]', true);

            if ($roleId <= 0) {
                throw new Exception('Invalid role ID');
            }

            if (!is_array($permissionIds)) {
                throw new Exception('Invalid permission IDs format');
            }

            // Convert to integers and filter
            $permissionIds = array_map('intval', array_filter($permissionIds));

            // Use project-scoped method
            $result = $permission->syncRolePermissionsWithProject($roleId, $permissionIds, $projectId);

            if ($result) {
                // Log activity
                $stmt = $pdo->prepare("
                    INSERT INTO user_activity_log (user_id, action, details, created_at)
                    VALUES (?, 'update_role_permissions', ?, NOW())
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    json_encode([
                        'role_id' => $roleId,
                        'project_id' => $projectId,
                        'permission_count' => count($permissionIds)
                    ])
                ]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Permissions updated successfully',
                    'count' => count($permissionIds)
                ]);
            } else {
                throw new Exception('Failed to update permissions');
            }
            break;

        case 'sync_permissions':
            // Auto-discover and sync page permissions
            $pagesDir = ROOT_PATH . 'pages/views';
            $results = $permission->syncPagePermissions($pagesDir);

            // Log sync activity
            $stmt = $pdo->prepare("
                INSERT INTO user_activity_log (user_id, action, details, created_at)
                VALUES (?, 'sync_permissions', ?, NOW())
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                json_encode($results)
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Permissions synchronized successfully',
                'results' => $results
            ]);
            break;

        case 'get_all_permissions':
            // Get all permissions grouped by category
            $permsByCategory = $permission->getPermissionsByCategory();

            echo json_encode([
                'success' => true,
                'permissions' => $permsByCategory
            ]);
            break;

        case 'create_permission':
            // Create a new custom permission
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category = trim($_POST['category'] ?? 'custom');

            if (empty($name)) {
                throw new Exception('Permission name is required');
            }

            // Validate name format (lowercase, underscores only)
            if (!preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
                throw new Exception('Permission name must be lowercase with underscores only');
            }

            $result = $permission->createPermission($name, $description, $category);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Permission created successfully',
                    'permission' => [
                        'name' => $name,
                        'description' => $description,
                        'category' => $category
                    ]
                ]);
            } else {
                throw new Exception('Failed to create permission (may already exist)');
            }
            break;

        case 'delete_permission':
            // Delete a permission (admin only)
            if (!($_SESSION['is_admin'] ?? false)) {
                throw new Exception('Only admins can delete permissions');
            }

            $permissionId = (int)($_POST['permission_id'] ?? 0);

            if ($permissionId <= 0) {
                throw new Exception('Invalid permission ID');
            }

            $result = $permission->deletePermission($permissionId);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Permission deleted successfully'
                ]);
            } else {
                throw new Exception('Failed to delete permission');
            }
            break;

        case 'get_projects':
            // Get all projects for project selector
            $projects = $permission->getAllProjects();

            echo json_encode([
                'success' => true,
                'projects' => $projects
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
