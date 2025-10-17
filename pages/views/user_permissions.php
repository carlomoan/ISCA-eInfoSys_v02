<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . 'helpers/permission_helper.php';
require_once ROOT_PATH . 'config/db_connect.php';

if (!checkPermission('add_field_data') && !checkPermission('add_lab_data')) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

// Permissions
$canAddRoles           = checkPermission('aassign_user_roles') || ($_SESSION['is_admin'] ?? false);
$canAddPermissions     = checkPermission('grant_user_access')   || ($_SESSION['is_admin'] ?? false);
$canAddClusters        = checkPermission('revoke_user_cluster')   || ($_SESSION['is_admin'] ?? false);
$canAddProjects        = checkPermission('add_projects')   || ($_SESSION['is_admin'] ?? false);
$canAddSurveys         = checkPermission('add_surveys') || ($_SESSION['is_admin'] ?? false);

// Load roles data server-side (avoids session issues with AJAX)
$roles = [];
$projects = [];
$clusters = [];
try {
    // Get all roles
    $stmt = $pdo->prepare("SELECT id, name FROM roles ORDER BY name ASC");
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all projects
    $stmt = $pdo->prepare("SELECT project_id as id, project_name as name, project_code FROM projects ORDER BY project_name ASC");
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all clusters
    $stmt = $pdo->prepare("SELECT cluster_id as id, cluster_name as name FROM clusters ORDER BY cluster_name ASC");
    $stmt->execute();
    $clusters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error loading dropdown data: " . $e->getMessage());
}
?>

<!-- Enhanced Styles -->
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/user_permissions_enhanced.css" />

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script src="<?= BASE_URL ?>/assets/js/users/users_table.js" defer></script>
<script src="<?= BASE_URL ?>/assets/js/users/users_table_enhanced.js" defer></script>
<script src="<?= BASE_URL ?>/assets/js/users/users_mapping_modal.js" defer></script>

<div class="page-container">
    <div id="toast-container"></div>

    <nav class="breadcrumb">
        <i class="fas fa-home"></i>
        <a href="?page=dashboard">Dashboard</a> / Administrator <strong></strong>
    </nav>
    <h3><i class="fas fa-cog"></i> Administrator Panel: Management and Settings</h3>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab-btn active" data-tab="user">User</button>
        <button class="tab-btn" data-tab="roles" <?= $canAddRoles ? '' : 'disabled' ?>>Roles</button>
        <button class="tab-btn" data-tab="permissions" <?= $canAddPermissions ? '' : 'disabled' ?>>Permissions</button>
        <button class="tab-btn" data-tab="clusters" <?= $canAddClusters ? '' : 'disabled' ?>>Clusters</button>
        <button class="tab-btn" data-tab="projects" <?= $canAddProjects ? '' : 'disabled' ?>>Projects</button>
        <button class="tab-btn" data-tab="surveys" <?= $canAddSurveys ? '' : 'disabled' ?>>Surveys</button>
    </div>

    <!-- Tab contents -->

    <!-- ====Tab 1: User View ==== -->
    <div class="tab-content active" id="tab-user">
        <div class="top-actions">
            <div class="left-actions">
                <p><i class="fas fa-users-cog"></i> User Management</p>
            </div>
            <div class="right-actions">
                <input type="text" id="filter-search" class="custom-search" placeholder="Search users..." />
            </div>
        </div>
        <?php include __DIR__ . '/partials/users/users_table_enhanced.php'; ?>
    </div>  

     <!-- ====Tab 2: Roles View ==== -->
    <div class="tab-content" id="tab-roles">
        <?php if (!$canAddRoles): ?>
            <div class="empty-state" style="padding: 60px 20px; text-align: center; background: white; border-radius: 12px;">
                <i class="fas fa-lock" style="font-size: 64px; color: #d1d5db; margin-bottom: 16px;"></i>
                <p style="color: #6b7280; font-size: 16px;">You do not have permission to access this section.</p>
            </div>
        <?php else: ?>
        <div class="top-actions">
            <div class="left-actions">
                <p><i class="fas fa-user-tag"></i> Roles Management</p>
            </div>
            <div class="right-actions">
                <input type="text" id="filter-search-roles" class="custom-search" placeholder="Search roles..." />
            </div>
        </div>
        <div id="views-roles-container" style="background: white; border-radius: 0 0 12px 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);"></div>
        <?php endif; ?>
    </div> 

    <!-- ====Tab 3: Permissions ==== -->
    <div class="tab-content" id="tab-permissions">
        <?php if (!$canAddPermissions): ?>
            <div class="empty-state" style="padding: 60px 20px; text-align: center; background: white; border-radius: 12px;">
                <i class="fas fa-lock" style="font-size: 64px; color: #d1d5db; margin-bottom: 16px;"></i>
                <p style="color: #6b7280; font-size: 16px;">You do not have permission to access this section.</p>
            </div>
        <?php else: ?>
        <div class="top-actions">
            <div class="left-actions">
                <p><i class="fas fa-key"></i> Permissions & Access Management</p>
            </div>
            <div class="right-actions">
                <input type="text" id="filter-search-permissions" class="custom-search" placeholder="Search permissions..." />
            </div>
        </div>
        <div id="views-permissions-container" style="background: white; border-radius: 0 0 12px 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);"></div>
        <?php endif; ?>
    </div> 

    <!-- ====Tab: User Clusters ==== -->
    <div class="tab-content" id="tab-clusters">
        <?php if (!$canAddClusters): ?>
            <div class="empty-state" style="padding: 60px 20px; text-align: center; background: white; border-radius: 12px;">
                <i class="fas fa-lock" style="font-size: 64px; color: #d1d5db; margin-bottom: 16px;"></i>
                <p style="color: #6b7280; font-size: 16px;">You do not have permission to access this section.</p>
            </div>
        <?php else: ?>
        <div class="top-actions">
            <div class="left-actions">
                <p><i class="fas fa-map-marked-alt"></i> Clusters & Users Management</p>
            </div>
            <div class="right-actions">
                <input type="text" id="filter-search-clusters" class="custom-search" placeholder="Search clusters..." />
            </div>
        </div>
        <div id="views-clusters-container" style="background: white; border-radius: 0 0 12px 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);"></div>
        <?php endif; ?>
    </div>

    <!-- Tab 4: Projects -->
    <div class="tab-content" id="tab-projects">
        <?php if (!$canAddProjects): ?>
            <div class="empty-state" style="padding: 60px 20px; text-align: center; background: white; border-radius: 12px;">
                <i class="fas fa-lock" style="font-size: 64px; color: #d1d5db; margin-bottom: 16px;"></i>
                <p style="color: #6b7280; font-size: 16px;">You do not have permission to access this section.</p>
            </div>
        <?php else: ?>
        <div class="top-actions">
            <div class="left-actions">
                <p><i class="fas fa-project-diagram"></i> Projects & Site Management</p>
            </div>
            <div class="right-actions">
                <input type="text" id="filter-search-projects" class="custom-search" placeholder="Search projects..." />
            </div>
        </div>
        <div id="views-projects-container" style="background: white; border-radius: 0 0 12px 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);"></div>
        <?php endif; ?>
    </div>

    <!-- Tab 5: Surveys -->
    <div class="tab-content" id="tab-surveys">
        <?php if (!$canAddSurveys): ?>
            <div class="empty-state" style="padding: 60px 20px; text-align: center; background: white; border-radius: 12px;">
                <i class="fas fa-lock" style="font-size: 64px; color: #d1d5db; margin-bottom: 16px;"></i>
                <p style="color: #6b7280; font-size: 16px;">You do not have permission to access this section.</p>
            </div>
        <?php else: ?>
        <div class="top-actions">
            <div class="left-actions">
                <p><i class="fas fa-chart-bar"></i> Statistics & Survey Management</p>
            </div>
            <div class="right-actions">
                <input type="text" id="filter-search-surveys" class="custom-search" placeholder="Search surveys..." />
            </div>
        </div>
        <div id="views-surveys-container" style="background: white; border-radius: 0 0 12px 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);"></div>
        <?php endif; ?>
    </div>
</div> 

<script>
    // Current user info
    window.CURRENT_USER = {
        id: <?= json_encode($_SESSION['user_id'] ?? 0) ?>,
        role: <?= json_encode($_SESSION['role_name'] ?? '') ?>,
        name: <?= json_encode($_SESSION['full_name'] ?? '') ?>
    };

    // Pre-loaded dropdown data (avoids AJAX session issues)
    window.PRELOADED_DATA = {
        roles: <?= json_encode($roles) ?>,
        projects: <?= json_encode($projects) ?>,
        clusters: <?= json_encode($clusters) ?>
    };

    console.log('✅ Pre-loaded data available:', window.PRELOADED_DATA);
</script>
