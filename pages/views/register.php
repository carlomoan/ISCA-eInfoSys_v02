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
$canAddField           = checkPermission('add_field_data') || ($_SESSION['is_admin'] ?? false);
$canAddLab             = checkPermission('add_lab_data')   || ($_SESSION['is_admin'] ?? false);
$canCompare            = checkPermission('add_lab_data')   || ($_SESSION['is_admin'] ?? false);
$canViewOwnDataField   = checkPermission('view_own_field_data') || ($_SESSION['is_admin'] ?? false);
$canViewOwnReport      = checkPermission('view_own_report')     || ($_SESSION['is_admin'] ?? false);
?>

<!-- PDF dependencies -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>


<script src="<?= BASE_URL ?>/assets/js/add_lab_data_form.js" defer></script>
<!--<script src="<?= BASE_URL ?>/assets/js/add_lab_data.js" defer></script> -->
<script src="<?= BASE_URL ?>/assets/js/desklabdata/get_desk_lab_data.js" defer></script>
<script src="<?= BASE_URL ?>/assets/js/data_tab.js" defer></script>

<div class="page-container">
    <div id="toast-container"></div>

    <nav class="breadcrumb">
        <i class="fas fa-home"></i>
        <a href="?page=dashboard">Dashboard</a> / <strong>Laboratory Sorting Data </strong>
    </nav>
    <h2>Desk - Laboratory Sorting and Test Result Data</h2>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab-btn active" data-tab="generated">Data</button>
        <button class="tab-btn" data-tab="desk_add_lab" <?= $canAddLab ? '' : 'disabled' ?>>Add Lab Data</button>
        <button class="tab-btn" data-tab="desk_compare" <?= $canCompare ? '' : 'disabled' ?>>My Raws Data</button>
    </div>

    <!-- Tab contents -->

    <!-- ====Tab 1: Data View ==== -->
    <div class="tab-content active" id="tab-generated">
        <div class="top-actions">
            <div class="left-actions">
                <div class="form-group">
                    <select id="filter-round">
                        <option value="0">Select round</option>
                    </select>
                </div>
                <div class="form-group">
                    <select id="rowsPerPageGenerated">
                        <option value="10" selected>Rows</option>
                        <option value="27">27</option>
                        <option value="54">54</option>
                    </select>
                </div>
            </div>
            <div class="right-actions">
                <div class="form-group">
                    <input type="text" id="filter-search" class="custom-search" placeholder="Type to search..." />
                </div>
            </div>
        </div>
        <div id="views-table-container"></div>
    </div>

    <!-- ====Tab 2: Add Field Data ==== -->
<div class="tab-content" id="tab-desk_add_field">
    <?php if ($canAddField): ?>
        <div class="tab-pane fade show active" id="desk_add_field_content">
            <?php include __DIR__ . '/partials/fieldtab/get_add_field_tab.php'; ?>
        </div>
    <?php else: ?>
        <p class="text-muted">You do not have permission to add field data.</p>
    <?php endif; ?>
</div>


    <!-- Tab 3: Add Lab Data -->
<div class="tab-content" id="tab-desk_add_lab">
    <?php if ($canAddLab): ?>
        <div class="tab-pane fade show active" id="desk_add_lab_content">
            <?php include __DIR__ . '/partials/labtab/get_add_lab_tab.php'; ?>
        </div>
    <?php else: ?>
        <p class="text-muted">You do not have permission to add lab data.</p>
    <?php endif; ?>
</div>


    <!-- Tab 3: Desk Compare -->
<div class="tab-content" id="tab-desk_compare">
            <div class="top-actions">
            <div class="right-actions">
                <div class="form-group">
                    <input type="text" id="filter-search" class="custom-search" placeholder="Type to search..." />
                </div>
            </div>
        </div>
     <?php if ($canCompare): ?>
        <div id="views-desk-lab-table-container">
        </div>
    <?php else: ?>
        <p class="text-muted">You do not have permission to add lab data.</p>
    <?php endif; ?>
</div>

<script>
    window.CURRENT_USER = {
        id: <?= json_encode($_SESSION['user_id'] ?? 0) ?>,
        role: <?= json_encode($_SESSION['role_name'] ?? '') ?>,
        name: <?= json_encode($_SESSION['full_name'] ?? '') ?>
    };

   window.sessionUserFullName = "<?php echo htmlspecialchars($_SESSION['user_fullname']); ?>";
</script>

