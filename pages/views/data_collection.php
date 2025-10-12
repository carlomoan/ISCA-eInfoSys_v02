<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . 'helpers/permission_helper.php';
require_once ROOT_PATH . 'config/db_connect.php';

// page permissions
if (!checkPermission('data_collection')) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

// Permissions
$canMergedDesk           = checkPermission('verify_merged_data')   || ($_SESSION['is_admin'] ?? false);
$canCompare              = checkPermission('add_field_data')   || ($_SESSION['is_admin'] ?? false);
$canCompareLab           = checkPermission('add_lab_data')   || ($_SESSION['is_admin'] ?? false);
$canViewOwnDataField     = checkPermission('view_field_data') || ($_SESSION['is_admin'] ?? false);
$canViewOwnReport        = checkPermission('view_report')     || ($_SESSION['is_admin'] ?? false);
$canVerifyODKMergedData  = checkPermission('data_collection')     || ($_SESSION['is_admin'] ?? false);
$canAppendAllMergedData  = checkPermission('data_collection')     || ($_SESSION['is_admin'] ?? false);
?>

<!-- Styles -->
 

<script src="<?= BASE_URL ?>/assets/js/deskfielddata/get_all_desk_field_data.js" defer></script>
<script src="<?= BASE_URL ?>/assets/js/desklabdata/get_all_desk_lab_data.js" defer></script>
<script src="<?= BASE_URL ?>/assets/js/upload_merge.js" defer></script>
<script src="<?= BASE_URL ?>/assets/js/desk_merge.js" defer></script>
<script src="<?= BASE_URL ?>/assets/js/data_collection_tab.js" defer></script>
<script src="<?= BASE_URL ?>/assets/js/data_tab.js" defer></script>

<div class="page-container">
    <div id="toast-container"></div>

    <nav class="breadcrumb">
        <i class="fas fa-home"></i>
        <a href="?page=dashboard">Dashboard</a> / <strong>Field and Laboratory Data</strong>
    </nav>
    <h2>Field Collection and Laboratory Sorting</h2>

    <!-- Tabs navigation -->
    <div class="tabs">
        <button class="tab-btn active" data-tab="generated">Data</button>
        <button class="tab-btn" data-tab="desk_compare" <?= $canCompare ? '' : 'disabled' ?>>Internal Field Data</button>
        <button class="tab-btn" data-tab="desk_compare_lab" <?= $canCompareLab ? '' : 'disabled' ?>>Internal Laboratory Data</button>
        <button class="tab-btn" data-tab="desk_compare_merge" <?= $canMergedDesk ? '' : 'disabled' ?> >Verify and Merge Internal Data</button>
        <button class="tab-btn" data-tab="verify_ODK_merged_data" <?= $canVerifyODKMergedData ? '' : 'disabled' ?> >View Merged ODK Data</button>
        <button class="tab-btn" data-tab="append_all_merged_data" <?= $canAppendAllMergedData ? '' : 'disabled' ?> >Append and Finalize All Data</button>
    </div>

    <!-- ==== Tab Contents ==== -->

    <!-- ==== Tab 1: General Data View ==== -->
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
                        <option value="10" selected>10 rows</option>
                        <option value="27">27 rows</option>
                        <option value="54">54 rows</option>
                    </select>
                </div>
            </div>
            <div class="right-actions">
                <div class="form-group">
                    <input type="text" class="custom-search" data-tab="generated" placeholder="Type to search..." />
                </div>
            </div>
        </div>
        <div id="views-table-container"></div>
    </div>

    <!-- ==== Internal Field Data ==== -->
    <div class="tab-content" id="tab-desk_compare">
        <div class="top-actions">
            <div class="left-actions">
                <input type="text" class="custom-search-desk-field" data-tab="desk_compare" placeholder="Type to search..." />
            </div>
        </div>
        <?php if ($canCompare): ?>
            <div id="views-desk-field-table-container"></div>
        <?php else: ?>
            <p class="text-muted">You do not have permission to view field data.</p>
        <?php endif; ?>
    </div>

    <!-- ==== Internal Laboratory Data ==== -->
    <div class="tab-content" id="tab-desk_compare_lab">
        <div class="top-actions">
            <div class="left-actions">
                <input type="text" class="custom-search-desk-lab" data-tab="desk_compare_lab" placeholder="Type to search..." />
            </div>
        </div>
        <?php if ($canCompareLab): ?>
            <div id="views-desk-lab-table-container"></div>
        <?php else: ?>
            <p class="text-muted">You do not have permission to view lab data.</p>
        <?php endif; ?>
    </div>




<!-- ==== Verify and Merge Data ==== -->
<div class="tab-content" id="tab-desk_compare_merge">
    <h3>Field and Laboratory Data Verification and Merge</h3>
    <?php if (!$canMergedDesk): ?>
        <p><em>You do not have permission to verify or merge data.</em></p>
    <?php else: ?>
        <div class="top-actions">
            <input type="text" class="custom-search-desk-merge" 
                   data-tab="desk_compare_merge" placeholder="Search HH code or cluster..." />
        </div>

        <!-- Feedback summary -->
        <div id="merge-summary" class="merge-summary"></div>

        <!-- Preview table -->
        <div id="merge-preview"></div>

        <!-- Mismatched Field Only / Lab Only -->
        <div id="merge-mismatches"></div>

        <!-- Action Buttons -->
<div id="merge-actions">
</div>


        <!-- Toast container -->
        <div id="toast-container"></div>
    <?php endif; ?>
</div>


    <!-- ==== View Merged ODK Data ==== -->
    <div class="tab-content" id="tab-verify_ODK_merged_data">
        <div class="top-actions">
            <div class="left-actions">
                <input type="text" class="custom-search-verify-odk" data-tab="verify_ODK_merged_data" placeholder="Type to search..." />
            </div>
        </div>
        <div id="views-verify-odk-table-container"></div>
    </div>

    <!-- ==== Append and Finalize All Data ==== -->
    <div class="tab-content" id="tab-append_all_merged_data">
        <div class="top-actions">
            <div class="left-actions">
                <input type="text" class="custom-search-append" data-tab="append_all_merged_data" placeholder="Type to search..." />
            </div>
        </div>
        <div id="views-append-all-table-container"></div>
    </div>
</div>

<!-- Modal -->
<div id="modalGeneric" class="modal" aria-hidden="true" role="dialog" aria-labelledby="modalTitle" aria-describedby="modalBody" tabindex="-1">
    <div class="modal-content" style="max-width:90%;overflow:auto;">
        <button class="close" aria-label="Close modal">&times;</button>
        <div id="modalBody">Loading...</div>
    </div>
</div>

