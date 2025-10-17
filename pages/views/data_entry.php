<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . 'helpers/permission_helper.php';
require_once ROOT_PATH . 'config/db_connect.php';

// page permissions
if (!checkPermission('data_entry')) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

// Permissions for upload and download buttons
$canUpload = ($_SESSION['is_admin'] ?? false) || checkPermission('data_entry');
$canDownload = ($_SESSION['is_admin'] ?? false) || checkPermission('download_report');

?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/data_entry_modern.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/data_tab_badges.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script src="<?= BASE_URL ?>/assets/js/upload_field.js" defer></script>
<script src="<?= BASE_URL ?>/assets/js/upload_lab.js" defer></script>
<script src="<?= BASE_URL ?>/assets/js/upload_merge.js" defer></script>
<script src="<?= BASE_URL ?>/assets/js/data_tab_merged.js" defer></script>

<script>
// Pass permissions to JavaScript
window.isAdmin = <?= json_encode($_SESSION['is_admin'] ?? false) ?>;
window.userPermissions = {
    approve_data: <?= json_encode(checkPermission('approve_data')) ?>
};
</script>

<div class="page-container">
   <div id="toast-container"></div>

    <nav class="breadcrumb">
        <i class="fas fa-home"></i>
        <a href="?page=dashboard">Dashboard</a> / <strong>Field and Laboratory Data Entry</strong>
    </nav>
    <h2><i class="fas fa-database"></i> Field Collection and Laboratory Data Entry</h2>

    <!-- Tabs navigation -->
    <div class="tabs">
        <button class="tab-btn active" data-tab="generated"><i class="fas fa-table"></i> Data</button>
        <button class="tab-btn" data-tab="field" <?= $canUpload ? '' : 'disabled' ?>><i class="fas fa-upload"></i> Upload ODK Field Data</button>
        <button class="tab-btn" data-tab="lab" <?= $canUpload ? '' : 'disabled' ?>><i class="fas fa-flask"></i> Upload ODK Lab Data</button>
        <button class="tab-btn" data-tab="compare" <?= $canUpload ? '' : 'disabled' ?>><i class="fas fa-code-branch"></i> Verify and Merge ODK Data</button>
    </div>

    <!-- Tab contents -->

    <!-- ==== Data Tab and Table -->
    <div class="tab-content active" id="tab-generated">

        <div class="top-actions">
            <div class="left-actions">
                <div class="form-group">
                    <select id="filter-round">
                        <option value="0"> Select round </option>
                        <!-- Rounds will load dynamically -->
                    </select>
                </div>
                <div class="form-group">
                    <select id="rowsPerPageGenerated">
                        <option value="10" selected>10 rows</option>
                        <option value="27">27 rows</option>
                        <option value="54">54 rows</option>
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

    <!-- ==== Upload Field Data Tab ==== -->
    <div class="tab-content" id="tab-field">
        <div class="upload-form-card">
            <h3><i class="fas fa-file-excel"></i> Upload Field Data</h3>
            <?php if (!$canUpload): ?>
                <p><em>You do not have permission to upload data.</em></p>
            <?php else: ?>
                <form id="upload-field-form" enctype="multipart/form-data" method="POST" action="<?= BASE_URL ?>/api/datacheck/uploaddataapi/upload_field_api.php">
                    <div class="file-upload-area">
                        <div class="file-upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="file-upload-text">
                            <strong>Click to browse</strong>
                            <span>Select your field data file</span>
                        </div>
                        <div class="file-upload-hint">Supported: CSV, XLS, XLSX (Max: 10MB)</div>
                        <input type="file" name="field_file" id="field_file" accept=".csv,.xls,.xlsx" required>
                    </div>
                    <button type="submit" class="btn-upload"><i class="fas fa-upload"></i> Upload Field Data</button>
                </form>

                <div id="field-upload-section" style="margin-top: 1.5rem;">
                    <div id="upload-summary" class="upload-summary"></div>
                    <div id="field-upload-feedback" class="feedback-section"></div>
                    <div id="missing-households-container" class="missing-households-section"></div>
                    <div id="field-upload-preview"></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ==== Upload Lab Data Tab ==== -->
    <div class="tab-content" id="tab-lab">
        <div class="upload-form-card">
            <h3><i class="fas fa-vial"></i> Upload Laboratory Data</h3>
            <?php if (!$canUpload): ?>
                <p><em>You do not have permission to upload data.</em></p>
            <?php else: ?>
                <form id="upload-lab-form" enctype="multipart/form-data" method="POST" action="<?= BASE_URL ?>/api/datacheck/uploaddataapi/upload_lab_api.php">
                    <div class="file-upload-area">
                        <div class="file-upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="file-upload-text">
                            <strong>Click to browse</strong>
                            <span>Select your laboratory data file</span>
                        </div>
                        <div class="file-upload-hint">Supported: CSV, XLS, XLSX (Max: 10MB)</div>
                        <input type="file" name="lab_file" id="lab_file" accept=".csv,.xls,.xlsx" required>
                    </div>
                    <button type="submit" class="btn-upload"><i class="fas fa-upload"></i> Upload Lab Data</button>
                </form>

                <div id="lab-upload-section" style="margin-top: 1.5rem;">
                    <div id="lab-upload-feedback" class="feedback-section"></div>
                    <div id="lab-upload-summary" class="upload-summary"></div>
                    <div id="lab-missing-households-container" class="missing-households-section"></div>
                    <div id="lab-upload-preview"></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ==== Verify and Merge Tab ==== -->
    <div class="tab-content" id="tab-compare">
        <div class="upload-form-card">
            <h3><i class="fas fa-project-diagram"></i> Field and Laboratory Verification and Merging</h3>
            <?php if (!$canUpload): ?>
                <p><em>You do not have permission to verify or merge data.</em></p>
            <?php else: ?>
                <form id="compare-merge-form" class="merge-form"
                      action="<?= BASE_URL ?>/api/datacheck/uploaddataapi/upload_merge_api.php"
                      method="POST" enctype="multipart/form-data">
                    <p class="info-note">
                        <i class="fas fa-info-circle"></i>
                        This tab will compare only successfully uploaded <strong>Field</strong> and <strong>Lab</strong> data.
                    </p>
                    <button type="submit" class="btn-compare"><i class="fas fa-code-branch"></i> Compare & Merge</button>
                    <div class="upload-result" id="result-compare" style="margin-top: 1rem;"></div>
                </form>
            <?php endif; ?>

            <div id="merge-feedback"></div>
        </div>
    </div>

</div>

<!-- Modal -->
<div id="modalGeneric" class="modal" aria-hidden="true" role="dialog" aria-labelledby="modalTitle" aria-describedby="modalBody" tabindex="-1">
    <div class="modal-content" style="max-width:90%;overflow:auto;">
        <button class="close" aria-label="Close modal">&times;</button>
        <div id="modalBody">Loading...</div>
    </div>
</div>
