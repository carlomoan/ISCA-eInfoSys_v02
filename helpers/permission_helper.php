<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db_connect.php';

/**
 * Load all permissions from centralized file
 * Cached statically on first load to avoid repeated DB calls
 */
function getAllPermissions(): array {
    static $permissions = null;
    if ($permissions === null) {
        $permissions = require __DIR__ . '/permissions.php';
    }
    return $permissions;
}

/**
 * Refresh and cache user permissions from DB (cache for 10 minutes)
 */
function getPermissions(): array {
    if (
        !isset($_SESSION['permissions']) ||
        !isset($_SESSION['permissions_last_update']) ||
        $_SESSION['permissions_last_update'] < (time() - 600)
    ) {
        global $pdo;

        if (!($_SESSION['is_admin'] ?? false)) {
            $stmt = $pdo->prepare("
                SELECT p.name
                FROM role_permissions rp
                INNER JOIN permissions p ON rp.permission_id = p.id
                WHERE rp.role_id = ?
            ");
            $stmt->execute([$_SESSION['role_id']]);
            $_SESSION['permissions'] = $stmt->fetchAll(PDO::FETCH_COLUMN) ?? [];
        } else {
            // Admin has all permissions
            $_SESSION['permissions'] = array_values(getAllPermissions());
        }

        $_SESSION['permissions_last_update'] = time();
    }
    return $_SESSION['permissions'];
}

/**
 * Check if user has a specific permission
 */
function checkPermission(string $permission): bool {
    if ($_SESSION['is_admin'] ?? false) {
        return true;
    }
    return in_array($permission, getPermissions(), true);
}

/**
 * Filter menu items based on permission names
 * Only menu items with allowed permission or no permission requirement are included
 */
function filterMenuByPermission(array $menuItems): array {
    $isAdmin     = $_SESSION['is_admin'] ?? false;
    $permissions = getPermissions();

    return array_filter($menuItems, function ($item) use ($permissions, $isAdmin) {
        return $isAdmin || empty($item['perm']) || in_array($item['perm'], $permissions, true);
    });
}

/**
 * Sanitize page string (allow only alphanumeric characters and underscore)
 */
function sanitizePage(string $page): string {
    return preg_replace('/[^a-zA-Z0-9_]/', '', $page);
}

/**
 * Get first allowed page from menu or fallback to 'dashboard'
 */
function getDefaultPage(array $menuItems): string {
    foreach ($menuItems as $item) {
        if (empty($item['perm']) || checkPermission($item['perm'])) {
            parse_str(parse_url($item['link'], PHP_URL_QUERY), $params);
            if (!empty($params['page'])) {
                return sanitizePage($params['page']);
            }
        }
    }
    return 'dashboard';
}

/**
 * Check if user should be sent to awaiting approval page
 */
function shouldAwaitApproval(): bool {
    $roleId = $_SESSION['role_id'] ?? null;
    $isVerified = $_SESSION['is_verified'] ?? false;
    return empty($roleId) || !$isVerified;
}
