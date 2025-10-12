<?php
// Load config FIRST
require_once ROOT_PATH . 'helpers/permission_helper.php';
require_once ROOT_PATH . 'classes/Permission.php';

// Check if user has permission to manage roles
if (!checkPermission('manage_roles') && !($_SESSION['is_admin'] ?? false)) {
    http_response_code(403);
    echo "Access denied. You need 'manage_roles' permission.";
    exit;
}

$permissionManager = new Permission($pdo);

// Get all roles and permissions
$roles = $permissionManager->getAllRoles();
$permissionsByCategory = $permissionManager->getPermissionsByCategory();
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/role_permissions.css?v=<?= time() ?>">

<div class="page-container">
    <div id="toast-container"></div>

    <nav class="breadcrumb">
        <i class="fas fa-home"></i>
        <a href="?page=dashboard">Dashboard</a> / <a href="?page=user_permissions">User Permissions</a> / <strong>Role Permissions</strong>
    </nav>

    <h2><i class="fas fa-shield-alt"></i> Role & Permission Management</h2>

    <!-- Role Tabs -->
    <div class="role-tabs">
        <?php foreach ($roles as $index => $role): ?>
            <button class="role-tab <?= $index === 0 ? 'active' : '' ?>"
                    data-role-id="<?= $role['id'] ?>"
                    data-role-name="<?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-user-tag"></i>
                <?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Permission Manager Card -->
    <div class="permission-card">
        <div class="card-header">
            <div class="header-left">
                <h3 id="currentRoleName">Loading...</h3>
                <p id="currentRoleStats">Select permissions for this role</p>
            </div>
            <div class="header-actions">
                <button type="button" id="selectAllBtn" class="btn-secondary">
                    <i class="fas fa-check-double"></i> Select All
                </button>
                <button type="button" id="deselectAllBtn" class="btn-secondary">
                    <i class="fas fa-times"></i> Deselect All
                </button>
                <button type="button" id="savePermissionsBtn" class="btn-primary">
                    <i class="fas fa-save"></i> Save Permissions
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="permissionSearch" placeholder="Search permissions...">
            </div>

            <div id="permissionsContainer" class="permissions-grid">
                <!-- Permissions will be loaded via JavaScript -->
            </div>
        </div>
    </div>

    <!-- Sync Permissions Button -->
    <div class="sync-section">
        <button type="button" id="syncPermissionsBtn" class="btn-secondary">
            <i class="fas fa-sync-alt"></i> Sync Page Permissions
        </button>
        <p class="text-muted">Run this after adding new pages to auto-discover and create permissions</p>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const PERMISSIONS_BY_CATEGORY = <?= json_encode($permissionsByCategory) ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/role_permissions.js?v=<?= time() ?>"></script>
