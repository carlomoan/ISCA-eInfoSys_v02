<?php
/**
 * ================================================
 * Permission Class - Modern Permission Management
 * ================================================
 *
 * Handles:
 * - Auto-discovery of pages and generation of permissions
 * - CRUD operations for permissions
 * - Role-permission assignments
 * - Permission checking with caching
 *
 * Permission Naming Convention:
 * - Pages: "view_page_name" (e.g., "view_dashboard", "view_field_collection")
 * - Actions: "action_resource" (e.g., "add_field_data", "edit_user", "delete_report")
 */

class Permission {
    private PDO $pdo;
    private static ?array $cache = null;
    private const CACHE_DURATION = 600; // 10 minutes

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Auto-discover pages and sync permissions to database
     * Should be run periodically or on deployment
     */
    public function syncPagePermissions(string $pagesDir): array {
        $results = [
            'added' => [],
            'existing' => [],
            'errors' => []
        ];

        try {
            // Get all PHP files in pages/views directory
            $files = glob($pagesDir . '/*.php');

            foreach ($files as $file) {
                $pageName = basename($file, '.php');

                // Skip utility pages
                if (in_array($pageName, ['404', 'access_denied', 'under_construction'])) {
                    continue;
                }

                $permissionName = "view_" . $pageName;
                $description = $this->generateDescription($pageName);

                // Check if permission exists
                if ($this->permissionExists($permissionName)) {
                    $results['existing'][] = $permissionName;
                } else {
                    // Create new permission
                    if ($this->createPermission($permissionName, $description)) {
                        $results['added'][] = $permissionName;
                    } else {
                        $results['errors'][] = "Failed to create: $permissionName";
                    }
                }
            }

            // Clear cache after sync
            $this->clearCache();

        } catch (Exception $e) {
            $results['errors'][] = "Sync failed: " . $e->getMessage();
        }

        return $results;
    }

    /**
     * Create a new permission
     */
    public function createPermission(string $name, string $description = '', string $category = 'page'): bool {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO permissions (name, description, category, created_at)
                VALUES (:name, :description, :category, NOW())
                ON DUPLICATE KEY UPDATE
                    description = VALUES(description),
                    category = VALUES(category),
                    updated_at = NOW()
            ");

            $result = $stmt->execute([
                'name' => $name,
                'description' => $description ?: $this->generateDescription($name),
                'category' => $category
            ]);

            if ($result) {
                $this->clearCache();
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Failed to create permission '$name': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if permission exists
     */
    public function permissionExists(string $name): bool {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM permissions WHERE name = ?");
        $stmt->execute([$name]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Get all permissions
     */
    public function getAllPermissions(): array {
        try {
            // Try with 'name' column first
            $stmt = $this->pdo->query("
                SELECT id, name, description, category
                FROM permissions
                ORDER BY category, name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // If 'name' doesn't exist, try 'permission_name'
            try {
                $stmt = $this->pdo->query("
                    SELECT id, permission_name as name, description, category
                    FROM permissions
                    ORDER BY category, permission_name
                ");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e2) {
                $errorMsg = "Failed to fetch permissions: " . $e->getMessage() . " | Fallback: " . $e2->getMessage();
                error_log($errorMsg);
                // In production, also throw to expose the error
                if (defined('APP_ENV') && APP_ENV === 'production') {
                    throw new Exception($errorMsg);
                }
                return [];
            }
        }
    }

    /**
     * Get permissions grouped by category
     */
    public function getPermissionsByCategory(): array {
        $permissions = $this->getAllPermissions();
        $grouped = [];

        foreach ($permissions as $perm) {
            $category = $perm['category'] ?: 'other';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $perm;
        }

        return $grouped;
    }

    /**
     * Get permissions for a specific role
     */
    public function getRolePermissions(int $roleId): array {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.id, p.name, p.description, p.category
                FROM permissions p
                INNER JOIN role_permissions rp ON p.id = rp.permission_id
                WHERE rp.role_id = ?
                ORDER BY p.category, p.name
            ");
            $stmt->execute([$roleId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to fetch role permissions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Assign permission to role
     */
    public function assignPermissionToRole(int $roleId, int $permissionId): bool {
        try {
            $stmt = $this->pdo->prepare("
                INSERT IGNORE INTO role_permissions (role_id, permission_id)
                VALUES (?, ?)
            ");
            $result = $stmt->execute([$roleId, $permissionId]);

            if ($result) {
                $this->clearCache();
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Failed to assign permission: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Revoke permission from role
     */
    public function revokePermissionFromRole(int $roleId, int $permissionId): bool {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM role_permissions
                WHERE role_id = ? AND permission_id = ?
            ");
            $result = $stmt->execute([$roleId, $permissionId]);

            if ($result) {
                $this->clearCache();
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Failed to revoke permission: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sync all permissions for a role (replaces existing)
     */
    public function syncRolePermissions(int $roleId, array $permissionIds): bool {
        try {
            $this->pdo->beginTransaction();

            // Delete existing permissions
            $stmt = $this->pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
            $stmt->execute([$roleId]);

            // Insert new permissions
            if (!empty($permissionIds)) {
                $placeholders = implode(',', array_fill(0, count($permissionIds), '(?, ?)'));
                $stmt = $this->pdo->prepare("
                    INSERT INTO role_permissions (role_id, permission_id)
                    VALUES $placeholders
                ");

                $params = [];
                foreach ($permissionIds as $permId) {
                    $params[] = $roleId;
                    $params[] = $permId;
                }

                $stmt->execute($params);
            }

            $this->pdo->commit();
            $this->clearCache();

            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Failed to sync role permissions: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user has permission (with caching)
     */
    public function userHasPermission(int $userId, string $permissionName): bool {
        // Check if user is admin
        $stmt = $this->pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $isAdmin = (bool) $stmt->fetchColumn();

        if ($isAdmin) {
            return true; // Admin has all permissions
        }

        // Check permission through role (one-to-many: users.role_id)
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM users u
            INNER JOIN role_permissions rp ON u.role_id = rp.role_id
            INNER JOIN permissions p ON rp.permission_id = p.id
            WHERE u.id = ? AND p.name = ?
        ");
        $stmt->execute([$userId, $permissionName]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Get all roles
     */
    public function getAllRoles(): array {
        try {
            $stmt = $this->pdo->query("SELECT id, name FROM roles ORDER BY name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to fetch roles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate human-readable description from permission name
     */
    private function generateDescription(string $name): string {
        // Convert snake_case to Title Case
        $parts = explode('_', $name);
        $parts = array_map('ucfirst', $parts);
        return implode(' ', $parts);
    }

    /**
     * Clear permission cache
     */
    private function clearCache(): void {
        self::$cache = null;
        // Also clear session cache
        if (isset($_SESSION['permissions'])) {
            unset($_SESSION['permissions']);
            unset($_SESSION['permissions_last_update']);
        }
    }

    /**
     * Delete permission (with cascade to role_permissions)
     */
    public function deletePermission(int $permissionId): bool {
        try {
            $this->pdo->beginTransaction();

            // Delete from role_permissions first
            $stmt = $this->pdo->prepare("DELETE FROM role_permissions WHERE permission_id = ?");
            $stmt->execute([$permissionId]);

            // Delete permission
            $stmt = $this->pdo->prepare("DELETE FROM permissions WHERE id = ?");
            $result = $stmt->execute([$permissionId]);

            $this->pdo->commit();
            $this->clearCache();

            return $result;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Failed to delete permission: " . $e->getMessage());
            return false;
        }
    }

    // ============================================================
    // PROJECT-SCOPED PERMISSION METHODS
    // ============================================================

    /**
     * Sync role permissions with project scope
     * projectId = 0 means global (applies to all projects)
     */
    public function syncRolePermissionsWithProject(int $roleId, array $permissionIds, int $projectId = 0): bool {
        try {
            $this->pdo->beginTransaction();

            // Delete existing permissions for this role and project
            $stmt = $this->pdo->prepare("
                DELETE FROM role_permissions
                WHERE role_id = ? AND project_id = ?
            ");
            $stmt->execute([$roleId, $projectId]);

            // Insert new permissions
            if (!empty($permissionIds)) {
                $placeholders = implode(',', array_fill(0, count($permissionIds), '(?, ?, ?)'));
                $stmt = $this->pdo->prepare("
                    INSERT INTO role_permissions (role_id, permission_id, project_id)
                    VALUES $placeholders
                ");

                $params = [];
                foreach ($permissionIds as $permId) {
                    $params[] = $roleId;
                    $params[] = $permId;
                    $params[] = $projectId;
                }

                $stmt->execute($params);
            }

            $this->pdo->commit();
            $this->clearCache();

            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Failed to sync role permissions with project: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get role permissions filtered by project
     */
    public function getRolePermissionsByProject(int $roleId, ?int $projectId = null): array {
        try {
            if ($projectId === null) {
                // Get all permissions for this role (all projects)
                $stmt = $this->pdo->prepare("
                    SELECT p.*, rp.project_id
                    FROM role_permissions rp
                    INNER JOIN permissions p ON rp.permission_id = p.id
                    WHERE rp.role_id = ?
                    ORDER BY rp.project_id, p.category, p.name
                ");
                $stmt->execute([$roleId]);
            } else {
                // Get permissions for specific project or global (0)
                $stmt = $this->pdo->prepare("
                    SELECT p.*, rp.project_id
                    FROM role_permissions rp
                    INNER JOIN permissions p ON rp.permission_id = p.id
                    WHERE rp.role_id = ? AND (rp.project_id = ? OR rp.project_id = 0)
                    ORDER BY p.category, p.name
                ");
                $stmt->execute([$roleId, $projectId]);
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get role permissions by project: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Grant permission directly to a user (bypasses role)
     */
    public function grantUserPermission(
        int $userId,
        int $permissionId,
        int $projectId = 0,
        ?int $grantedBy = null,
        ?string $expiresAt = null,
        ?string $notes = null
    ): bool {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_permissions
                    (user_id, permission_id, project_id, granted_by, expires_at, notes)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    granted_by = VALUES(granted_by),
                    granted_at = CURRENT_TIMESTAMP,
                    expires_at = VALUES(expires_at),
                    is_active = TRUE,
                    notes = VALUES(notes)
            ");

            $result = $stmt->execute([
                $userId,
                $permissionId,
                $projectId,
                $grantedBy,
                $expiresAt,
                $notes
            ]);

            if ($result) {
                $this->logPermissionAudit('grant', 'user', $userId, $permissionId, $projectId, $grantedBy);
                $this->clearCache();
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Failed to grant user permission: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Revoke permission from user
     */
    public function revokeUserPermission(int $userId, int $permissionId, int $projectId = 0, ?int $revokedBy = null): bool {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM user_permissions
                WHERE user_id = ? AND permission_id = ? AND project_id = ?
            ");

            $result = $stmt->execute([$userId, $permissionId, $projectId]);

            if ($result) {
                $this->logPermissionAudit('revoke', 'user', $userId, $permissionId, $projectId, $revokedBy);
                $this->clearCache();
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Failed to revoke user permission: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all permissions for a user (both role and direct)
     */
    public function getUserEffectivePermissions(int $userId, ?int $projectId = null): array {
        try {
            $sql = "
                SELECT DISTINCT
                    p.id,
                    p.name,
                    p.description,
                    p.category,
                    p.is_project_specific,
                    p.applies_to,
                    COALESCE(up.project_id, rp.project_id, 0) AS project_id,
                    CASE
                        WHEN up.id IS NOT NULL THEN 'direct'
                        ELSE 'role'
                    END AS source
                FROM permissions p
                LEFT JOIN user_permissions up ON p.id = up.permission_id AND up.user_id = ?
                    AND up.is_active = TRUE
                    AND (up.expires_at IS NULL OR up.expires_at > NOW())
                LEFT JOIN users u ON u.id = ?
                LEFT JOIN role_permissions rp ON rp.role_id = u.role_id AND rp.permission_id = p.id
                WHERE (up.id IS NOT NULL OR rp.role_id IS NOT NULL)
            ";

            $params = [$userId, $userId];

            if ($projectId !== null) {
                $sql .= " AND (COALESCE(up.project_id, rp.project_id, 0) = ? OR COALESCE(up.project_id, rp.project_id, 0) = 0)";
                $params[] = $projectId;
            }

            $sql .= " ORDER BY p.category, p.name";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get user effective permissions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get direct user permissions (not from roles)
     */
    public function getUserDirectPermissions(int $userId, ?int $projectId = null): array {
        try {
            $sql = "
                SELECT p.*, up.project_id, up.granted_by, up.granted_at, up.expires_at, up.notes,
                       u.fname, u.lname
                FROM user_permissions up
                INNER JOIN permissions p ON up.permission_id = p.id
                LEFT JOIN users u ON up.granted_by = u.id
                WHERE up.user_id = ? AND up.is_active = TRUE
                    AND (up.expires_at IS NULL OR up.expires_at > NOW())
            ";

            $params = [$userId];

            if ($projectId !== null) {
                $sql .= " AND (up.project_id = ? OR up.project_id = 0)";
                $params[] = $projectId;
            }

            $sql .= " ORDER BY p.category, p.name";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get user direct permissions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if user has permission for specific project
     */
    public function userHasPermissionForProject(int $userId, string $permissionName, int $projectId = 0): bool {
        // Check if user is admin
        $stmt = $this->pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $isAdmin = (bool) $stmt->fetchColumn();

        if ($isAdmin) {
            return true;
        }

        // Check both direct permissions and role permissions
        // Include global permissions (project_id = 0) and project-specific permissions
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM (
                -- Direct user permissions
                SELECT 1 FROM user_permissions up
                INNER JOIN permissions p ON up.permission_id = p.id
                WHERE up.user_id = ? AND p.name = ?
                    AND (up.project_id = ? OR up.project_id = 0)
                    AND up.is_active = TRUE
                    AND (up.expires_at IS NULL OR up.expires_at > NOW())

                UNION

                -- Role permissions (one-to-many: users.role_id)
                SELECT 1 FROM users u
                INNER JOIN role_permissions rp ON u.role_id = rp.role_id
                INNER JOIN permissions p ON rp.permission_id = p.id
                WHERE u.id = ? AND p.name = ?
                    AND (rp.project_id = ? OR rp.project_id = 0)
            ) AS combined
        ");

        $stmt->execute([$userId, $permissionName, $projectId, $userId, $permissionName, $projectId]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Get all projects
     */
    public function getAllProjects(): array {
        try {
            $stmt = $this->pdo->query("
                SELECT project_id, project_code, project_name, project_type, project_status
                FROM projects
                ORDER BY project_status = 'On going' DESC, project_name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get projects: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get users list for permission assignment
     */
    public function getUsersList(): array {
        try {
            $stmt = $this->pdo->query("
                SELECT u.id, u.email, u.fname, u.lname, r.name AS role_name, u.is_admin
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.is_verified = 1
                ORDER BY u.fname, u.lname
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get users: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Log permission audit trail
     */
    private function logPermissionAudit(
        string $action,
        string $targetType,
        int $targetId,
        int $permissionId,
        ?int $projectId,
        ?int $performedBy
    ): void {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO permission_audit_log
                    (action_type, target_type, target_id, permission_id, project_id, performed_by, ip_address)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $action,
                $targetType,
                $targetId,
                $permissionId,
                $projectId,
                $performedBy ?? $_SESSION['user_id'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Failed to log permission audit: " . $e->getMessage());
        }
    }

    /**
     * Sync all permissions for a user across all projects
     */
    public function syncUserPermissions(int $userId, array $permissionData, ?int $grantedBy = null): bool {
        try {
            $this->pdo->beginTransaction();

            // Delete all existing user permissions
            $stmt = $this->pdo->prepare("DELETE FROM user_permissions WHERE user_id = ?");
            $stmt->execute([$userId]);

            // Insert new permissions
            if (!empty($permissionData)) {
                foreach ($permissionData as $data) {
                    $this->grantUserPermission(
                        $userId,
                        $data['permission_id'],
                        $data['project_id'] ?? 0,
                        $grantedBy,
                        $data['expires_at'] ?? null,
                        $data['notes'] ?? null
                    );
                }
            }

            $this->pdo->commit();
            $this->logPermissionAudit('sync', 'user', $userId, 0, null, $grantedBy);
            $this->clearCache();

            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Failed to sync user permissions: " . $e->getMessage());
            return false;
        }
    }
}
