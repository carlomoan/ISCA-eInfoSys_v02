<?php
/**
 * ================================================================
 * User Permission Management API
 * ================================================================
 * Handles direct user permission assignments (bypassing roles)
 * Supports project-scoped permissions
 * ================================================================
 */

// Load config FIRST to ensure proper session initialization
require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';
require_once ROOT_PATH . 'classes/Permission.php';

header('Content-Type: application/json');

// ===== Authentication Check =====
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
    exit;
}

// ===== Authorization Check =====
// Only superadmin or users with manage_permissions can manage user permissions
$isSuperAdmin = ($_SESSION['is_admin'] ?? false) && ($_SESSION['role_name'] ?? '') === 'Superuser';
$canManagePermissions = checkPermission('manage_permissions') || $isSuperAdmin;

if (!$canManagePermissions) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Only superadmin can manage user permissions.'
    ]);
    exit;
}

$permission = new Permission($pdo);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {

        // ===== Get User List =====
        case 'get_users':
            $users = $permission->getUsersList();
            echo json_encode([
                'success' => true,
                'users' => $users
            ]);
            break;

        // ===== Get User Permissions =====
        case 'get_user_permissions':
            $userId = (int)($_GET['user_id'] ?? 0);
            $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;

            if (!$userId) {
                throw new Exception('User ID is required');
            }

            // Get effective permissions (both role and direct)
            $effectivePermissions = $permission->getUserEffectivePermissions($userId, $projectId);

            // Get direct permissions only
            $directPermissions = $permission->getUserDirectPermissions($userId, $projectId);

            // Get user info
            $stmt = $pdo->prepare("
                SELECT u.id, u.email, u.fname, u.lname, u.is_admin, r.name AS role_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.id = ?
            ");
            $stmt->execute([$userId]);
            $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'user' => $userInfo,
                'effective_permissions' => $effectivePermissions,
                'direct_permissions' => $directPermissions
            ]);
            break;

        // ===== Grant Permission to User =====
        case 'grant_permission':
            $userId = (int)($_POST['user_id'] ?? 0);
            $permissionId = (int)($_POST['permission_id'] ?? 0);
            $projectId = (int)($_POST['project_id'] ?? 0);
            $expiresAt = $_POST['expires_at'] ?? null;
            $notes = $_POST['notes'] ?? null;

            if (!$userId || !$permissionId) {
                throw new Exception('User ID and Permission ID are required');
            }

            $result = $permission->grantUserPermission(
                $userId,
                $permissionId,
                $projectId,
                $_SESSION['user_id'],
                $expiresAt,
                $notes
            );

            if ($result) {
                // Log activity
                $stmt = $pdo->prepare("
                    INSERT INTO user_activity_log (user_id, action, details, created_at)
                    VALUES (?, 'grant_user_permission', ?, NOW())
                ");
                $stmt->execute([$_SESSION['user_id'], json_encode([
                    'target_user_id' => $userId,
                    'permission_id' => $permissionId,
                    'project_id' => $projectId
                ])]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Permission granted successfully'
                ]);
            } else {
                throw new Exception('Failed to grant permission');
            }
            break;

        // ===== Revoke Permission from User =====
        case 'revoke_permission':
            $userId = (int)($_POST['user_id'] ?? 0);
            $permissionId = (int)($_POST['permission_id'] ?? 0);
            $projectId = (int)($_POST['project_id'] ?? 0);

            if (!$userId || !$permissionId) {
                throw new Exception('User ID and Permission ID are required');
            }

            $result = $permission->revokeUserPermission(
                $userId,
                $permissionId,
                $projectId,
                $_SESSION['user_id']
            );

            if ($result) {
                // Log activity
                $stmt = $pdo->prepare("
                    INSERT INTO user_activity_log (user_id, action, details, created_at)
                    VALUES (?, 'revoke_user_permission', ?, NOW())
                ");
                $stmt->execute([$_SESSION['user_id'], json_encode([
                    'target_user_id' => $userId,
                    'permission_id' => $permissionId,
                    'project_id' => $projectId
                ])]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Permission revoked successfully'
                ]);
            } else {
                throw new Exception('Failed to revoke permission');
            }
            break;

        // ===== Sync All User Permissions =====
        case 'sync_user_permissions':
            $userId = (int)($_POST['user_id'] ?? 0);
            $permissionsData = json_decode($_POST['permissions_data'] ?? '[]', true);

            if (!$userId) {
                throw new Exception('User ID is required');
            }

            $result = $permission->syncUserPermissions(
                $userId,
                $permissionsData,
                $_SESSION['user_id']
            );

            if ($result) {
                // Log activity
                $stmt = $pdo->prepare("
                    INSERT INTO user_activity_log (user_id, action, details, created_at)
                    VALUES (?, 'sync_user_permissions', ?, NOW())
                ");
                $stmt->execute([$_SESSION['user_id'], json_encode([
                    'target_user_id' => $userId,
                    'permission_count' => count($permissionsData)
                ])]);

                echo json_encode([
                    'success' => true,
                    'message' => 'User permissions synchronized successfully'
                ]);
            } else {
                throw new Exception('Failed to sync user permissions');
            }
            break;

        // ===== Get All Projects =====
        case 'get_projects':
            $projects = $permission->getAllProjects();
            echo json_encode([
                'success' => true,
                'projects' => $projects
            ]);
            break;

        // ===== Get All Permissions =====
        case 'get_all_permissions':
            $allPermissions = $permission->getPermissionsByCategory();
            echo json_encode([
                'success' => true,
                'permissions' => $allPermissions
            ]);
            break;

        // ===== Get Permission Audit Log =====
        case 'get_audit_log':
            $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
            $limit = (int)($_GET['limit'] ?? 50);

            $sql = "
                SELECT pal.*, p.name AS permission_name, p.description,
                       u.fname, u.lname, proj.project_name
                FROM permission_audit_log pal
                LEFT JOIN permissions p ON pal.permission_id = p.id
                LEFT JOIN users u ON pal.performed_by = u.id
                LEFT JOIN projects proj ON pal.project_id = proj.project_id
                WHERE pal.target_type = 'user'
            ";

            $params = [];
            if ($userId) {
                $sql .= " AND pal.target_id = ?";
                $params[] = $userId;
            }

            $sql .= " ORDER BY pal.performed_at DESC LIMIT ?";
            $params[] = $limit;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $auditLog = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'audit_log' => $auditLog
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    error_log("User Permission API Error: " . $e->getMessage());
}
