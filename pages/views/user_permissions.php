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
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/users_table.css" />

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>



<script src="<?= BASE_URL ?>/assets/js/users/users_table.js" defer></script>
<script src="<?= BASE_URL ?>/assets/js/users/users_manage_modal.js" defer></script>
<script src="<?= BASE_URL ?>/assets/js/users/users_mapping_modal.js" defer></script>

   <div class="page-container">
    <div id="toast-container"></div>

    <nav class="breadcrumb">
        <i class="fas fa-home"></i>
        <a href="?page=dashboard">Dashboard</a> / Admininstrator <strong></strong>
    </nav>
    <h3 style="margin:8px 6px;" ><i class="fas fa-cog"></i> Administrator Panel: Management and Settings </h3>

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
                <div class="form-group">
                    <p style="margin:2px 2px;" ><i class="fas fa-cog"></i><b>  Administrator:</b> User Management </p>
                </div>
            </div>
            <div class="left-actions">
                <div class="form-group">
                    <input type="text" id="filter-search" class="custom-search" placeholder="Type to search..." />
                </div>
            </div>
        </div>
            <?php include __DIR__ . '/partials/users/users_table.php'; ?>
        </div>  

     <!-- ====Tab 2: Roles View ==== -->
    <div class="tab-content " id="tab-roles">
    
        <?php if (!$canAddRoles): ?>
            <p><em>You do not have permission.</em></p>
        <?php else: ?>
        <div class="top-actions">
            <div class="left-actions">
                <div class="form-group">
                    <p style="margin:2px 2px;" ><i class="fas fa-cog"></i><b>  Administrator:</b> Roles Management </p>
                </div>
            </div>
            <div class="right-actions">
                <div class="form-group">
                    <input type="text" id="filter-search" class="custom-search" placeholder="Type to search..." />
                </div>
            </div>
        </div>
             <div id="views-roles-container"></div>
        <?php endif; ?>
    </div> 

    <!-- ====Tab 3: Permissions ==== -->
    <div class="tab-content " id="tab-permissions">
        <?php if (!$canAddPermissions): ?>
            <p><em>You do not have permission.</em></p>
        <?php else: ?>
        <div class="top-actions">
            <div class="leftt-actions">
                <div class="form-group">
                    <p style="margin:2px 2px;" ><i class="fas fa-cog"></i><b>  Administrator:</b> Permissions and User Access Management</p>
                </div>
            </div>
            <div class="right-actions">
                <div class="form-group">
                    <input type="text" id="filter-search" class="custom-search" placeholder="Type to search..." />
                </div>
            </div>
        </div>
             <div id="views-roles-container"></div>
        <?php endif; ?>
    </div> 

    <!-- ====Tab : User Clusters ==== -->
    <div class="tab-content " id="tab-clusters">
        <?php if (!$canAddClusters): ?>
            <p><em>You do not have permission.</em></p>
        <?php else: ?>
        <div class="top-actions">
            <div class="leftt-actions">
                <div class="form-group">
                    <p style="margin:2px 2px;" ><i class="fas fa-cog"></i><b>  Administrator:</b> Clusters and Users Management</p>
                </div>
            </div>
            <div class="right-actions">
                <div class="form-group">
                    <input type="text" id="filter-search" class="custom-search" placeholder="Type to search..." />
                </div>
            </div>
        </div>
             <div id="views-roles-container"></div>
        <?php endif; ?>
    </div> 


    <!-- Tab 4: Projects -->
    <div class="tab-content " id="tab-projects">
        <?php if (!$canAddProjects): ?>
            <p><em>You do not have permission.</em></p>
        <?php else: ?>
        <div class="top-actions">
            <div class="left-actions">
                <div class="form-group">
                    <p style="margin:2px 2px;" ><i class="fas fa-cog"></i><b>  Administrator:</b>  Projects and Site Management</p>
                </div>
            </div>
            <div class="right-actions">
                <div class="form-group">
                    <input type="text" id="filter-search" class="custom-search" placeholder="Type to search..." />
                </div>
            </div>
        </div>
             <div id="views-roles-container"></div>
        <?php endif; ?>
    </div> 

    <!-- Tab 5: Surveys -->
    <div class="tab-content " id="tab-surveys">
        <?php if (!$canAddSurveys): ?>
            <p><em>You do not have permission.</em></p>
        <?php else: ?>
        <div class="top-actions">
            <div class="left-actions">
                <div class="form-group">
                    <p style="margin:2px 2px;" ><i class="fas fa-cog"></i><b>  Administrator:</b> Statistics and Survey Management</p>
                </div>
            </div>
            <div class="right-actions">
                <div class="form-group">
                    <input type="text" id="filter-search" class="custom-search" placeholder="Type to search..." />
                </div>
            </div>
        </div>
             <div id="views-roles-container"></div>
        <?php endif; ?>
    </div> 

<script>
    window.CURRENT_USER = {
        id: <?= json_encode($_SESSION['user_id'] ?? 0) ?>,
        role: <?= json_encode($_SESSION['role_name'] ?? '') ?>,
        name: <?= json_encode($_SESSION['full_name'] ?? '') ?>
    };
</script>
