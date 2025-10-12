<?php
/**
 * ================================================================
 * Modern Permission Management Page
 * ================================================================
 * Comprehensive permission management with project scoping
 * Supports both role and user-level permissions
 * Only accessible by superadmin
 * ================================================================
 */

require_once ROOT_PATH . 'helpers/permission_helper.php';
require_once ROOT_PATH . 'classes/Permission.php';

// ===== Authorization Check =====
$isSuperAdmin = ($_SESSION['is_admin'] ?? false) && ($_SESSION['role_name'] ?? '') === 'Superuser';

if (!$isSuperAdmin) {
    http_response_code(403);
    echo '<div class="access-denied-message">
            <i class="fas fa-lock"></i>
            <h2>Access Denied</h2>
            <p>Only superadmin can access permission management.</p>
            <a href="?page=dashboard" class="btn-primary">Go to Dashboard</a>
          </div>';
    exit;
}

$permissionManager = new Permission($pdo);

// Get data
$roles = $permissionManager->getAllRoles();
$users = $permissionManager->getUsersList();
$projects = $permissionManager->getAllProjects();
$permissionsByCategory = $permissionManager->getPermissionsByCategory();
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/manage_permissions.css?v=<?= time() ?>">

<div class="page-container">
    <div id="toast-container"></div>

    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <i class="fas fa-home"></i>
        <a href="?page=dashboard">Dashboard</a> /
        <a href="?page=user_permissions">User Permissions</a> /
        <strong>Manage Permissions</strong>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h2>
                <i class="fas fa-shield-alt"></i>
                Permission Management System
            </h2>
            <p class="subtitle">Manage role and user permissions with project-level granularity</p>
        </div>
        <div class="header-actions">
            <button type="button" id="syncPermissionsBtn" class="btn-secondary">
                <i class="fas fa-sync-alt"></i> Sync Permissions
            </button>
            <button type="button" id="viewAuditLogBtn" class="btn-secondary">
                <i class="fas fa-history"></i> Audit Log
            </button>
        </div>
    </div>

    <!-- Main Tabs -->
    <div class="main-tabs">
        <button class="main-tab active" data-tab="roles">
            <i class="fas fa-user-tag"></i> Role Permissions
        </button>
        <button class="main-tab" data-tab="users">
            <i class="fas fa-user-shield"></i> User Permissions
        </button>
    </div>

    <!-- ============================================================ -->
    <!-- TAB 1: ROLE PERMISSIONS -->
    <!-- ============================================================ -->
    <div class="tab-content active" id="tab-roles">
        <div class="section-card">
            <div class="card-header-section">
                <h3><i class="fas fa-user-tag"></i> Role-Based Permissions</h3>
                <p>Assign permissions to roles. Users inherit permissions from their assigned roles.</p>
            </div>

            <!-- Project Filter -->
            <div class="filter-section">
                <div class="filter-group">
                    <label for="roleProjectFilter">
                        <i class="fas fa-project-diagram"></i> Filter by Project
                    </label>
                    <select id="roleProjectFilter" class="form-control">
                        <option value="0">Global Permissions (All Projects)</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['project_id'] ?>">
                                <?= htmlspecialchars($project['project_name']) ?>
                                (<?= htmlspecialchars($project['project_code']) ?>)
                                <?php if ($project['project_status'] !== 'On going'): ?>
                                    - <?= $project['project_status'] ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-info">
                    <i class="fas fa-info-circle"></i>
                    <span id="roleProjectInfo">Showing global permissions that apply to all projects</span>
                </div>
            </div>

            <!-- Role Tabs -->
            <div class="role-tabs">
                <?php foreach ($roles as $index => $role): ?>
                    <button class="role-tab <?= $index === 0 ? 'active' : '' ?>"
                            data-role-id="<?= $role['id'] ?>"
                            data-role-name="<?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fas fa-users"></i>
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
                        <input type="text" id="rolePermissionSearch" class="search-input"
                               placeholder="Search permissions...">
                        <button type="button" id="roleSelectAllBtn" class="btn-secondary">
                            <i class="fas fa-check-double"></i> Select All
                        </button>
                        <button type="button" id="roleDeselectAllBtn" class="btn-secondary">
                            <i class="fas fa-times"></i> Deselect All
                        </button>
                        <button type="button" id="roleSavePermissionsBtn" class="btn-primary">
                            <i class="fas fa-save"></i> Save Permissions
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <div id="rolePermissionsContainer" class="permissions-grid">
                        <!-- Loaded via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- TAB 2: USER PERMISSIONS -->
    <!-- ============================================================ -->
    <div class="tab-content" id="tab-users">
        <div class="section-card">
            <div class="card-header-section">
                <h3><i class="fas fa-user-shield"></i> User-Level Permissions</h3>
                <p>Grant specific permissions directly to users, overriding or extending their role permissions.</p>
            </div>

            <!-- User Selector -->
            <div class="user-selector-section">
                <div class="selector-group">
                    <label for="userSelector">
                        <i class="fas fa-user"></i> Select User
                    </label>
                    <select id="userSelector" class="form-control">
                        <option value="">-- Select a user --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>"
                                    data-email="<?= htmlspecialchars($user['email']) ?>"
                                    data-role="<?= htmlspecialchars($user['role_name'] ?? 'No Role') ?>">
                                <?= htmlspecialchars($user['fname'] . ' ' . $user['lname']) ?>
                                (<?= htmlspecialchars($user['email']) ?>)
                                <?php if ($user['is_admin']): ?>
                                    <span class="badge-admin">ADMIN</span>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- User Info Card (Hidden until user selected) -->
            <div id="userInfoCard" class="user-info-card" style="display: none;">
                <div class="user-info-header">
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="user-details">
                        <h3 id="selectedUserName">-</h3>
                        <p id="selectedUserEmail">-</p>
                        <span class="user-role-badge" id="selectedUserRole">-</span>
                    </div>
                </div>

                <!-- Project Filter for User -->
                <div class="filter-section">
                    <div class="filter-group">
                        <label for="userProjectFilter">
                            <i class="fas fa-project-diagram"></i> Project Scope
                        </label>
                        <select id="userProjectFilter" class="form-control">
                            <option value="0">Global (All Projects)</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?= $project['project_id'] ?>">
                                    <?= htmlspecialchars($project['project_name']) ?>
                                    (<?= htmlspecialchars($project['project_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-info">
                        <i class="fas fa-info-circle"></i>
                        <span>Grant permissions for specific projects or globally</span>
                    </div>
                </div>

                <!-- Permission Tabs -->
                <div class="permission-tabs">
                    <button class="perm-tab active" data-tab="effective">
                        <i class="fas fa-shield-check"></i> Effective Permissions
                        <span class="tab-count" id="effectiveCount">0</span>
                    </button>
                    <button class="perm-tab" data-tab="direct">
                        <i class="fas fa-user-check"></i> Direct Permissions
                        <span class="tab-count" id="directCount">0</span>
                    </button>
                    <button class="perm-tab" data-tab="grant">
                        <i class="fas fa-plus-circle"></i> Grant New Permission
                    </button>
                </div>

                <!-- Tab Contents -->
                <div class="perm-tab-content active" id="perm-tab-effective">
                    <div class="tab-description">
                        <i class="fas fa-info-circle"></i>
                        All permissions this user has (from roles + direct grants)
                    </div>
                    <div id="effectivePermissionsContainer" class="permissions-list">
                        <!-- Loaded via JavaScript -->
                    </div>
                </div>

                <div class="perm-tab-content" id="perm-tab-direct">
                    <div class="tab-description">
                        <i class="fas fa-info-circle"></i>
                        Permissions granted directly to this user (not from roles)
                    </div>
                    <div id="directPermissionsContainer" class="permissions-list">
                        <!-- Loaded via JavaScript -->
                    </div>
                </div>

                <div class="perm-tab-content" id="perm-tab-grant">
                    <div class="grant-permission-form">
                        <h4><i class="fas fa-plus-circle"></i> Grant New Permission</h4>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Permission</label>
                                <select id="grantPermissionSelect" class="form-control">
                                    <option value="">-- Select Permission --</option>
                                    <?php foreach ($permissionsByCategory as $category => $perms): ?>
                                        <optgroup label="<?= htmlspecialchars(ucfirst($category)) ?>">
                                            <?php foreach ($perms as $perm): ?>
                                                <option value="<?= $perm['id'] ?>">
                                                    <?= htmlspecialchars($perm['description'] ?: $perm['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Expiration Date (Optional)</label>
                                <input type="datetime-local" id="grantExpiresAt" class="form-control">
                                <small class="form-hint">Leave empty for permanent access</small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Notes (Optional)</label>
                                <textarea id="grantNotes" class="form-control" rows="3"
                                          placeholder="Reason for granting this permission..."></textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" id="grantPermissionBtn" class="btn-primary">
                                <i class="fas fa-check"></i> Grant Permission
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Audit Log Modal -->
<div id="auditLogModal" class="modal">
    <div class="modal-dialog modal-large">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-history"></i> Permission Audit Log</h3>
                <button type="button" class="modal-close" onclick="closeAuditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="auditLogContainer">
                    <!-- Loaded via JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables for permission management
const PERMISSIONS_BY_CATEGORY = <?= json_encode($permissionsByCategory, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
const PROJECTS = <?= json_encode($projects, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
const CURRENT_USER_ID = <?= $_SESSION['user_id'] ?>;

// Debug output
console.log('Permission System Initialized:');
console.log('- Categories loaded:', Object.keys(PERMISSIONS_BY_CATEGORY).length);
console.log('- Projects loaded:', PROJECTS.length);
console.log('- Current User ID:', CURRENT_USER_ID);
</script>
<script src="<?= BASE_URL ?>/assets/js/manage_permissions.js?v=<?= time() ?>"></script>
