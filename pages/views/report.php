<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once ROOT_PATH . 'helpers/permission_helper.php';
require_once ROOT_PATH . 'config/db_connect.php';

// Permissions
if (!checkPermission('view_report')) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

$canUpload   = ($_SESSION['is_admin'] ?? false) || checkPermission('upload_report');
$canDownload = ($_SESSION['is_admin'] ?? false) || checkPermission('download_report');
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/report_modern.css">

<div class="page-container">
    <div id="toast-container"></div>

    <nav class="breadcrumb">
        <i class="fas fa-home"></i>
        <a href="?page=dashboard">Dashboard</a> / <strong>Reports</strong>
    </nav>

    <!-- Report Header with Stats -->
    <div class="report-header">
        <div class="header-content">
            <h2><i class="fas fa-chart-bar"></i> Reports & Analytics</h2>
            <p class="subtitle">Manage and analyze data collections and principal reports</p>
        </div>
        <div class="header-stats">
            <div class="stat-card">
                <i class="fas fa-database"></i>
                <div class="stat-info">
                    <span class="stat-value" id="totalRecords">0</span>
                    <span class="stat-label">Total Records</span>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-file-alt"></i>
                <div class="stat-info">
                    <span class="stat-value" id="totalReports">0</span>
                    <span class="stat-label">Uploaded Reports</span>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar"></i>
                <div class="stat-info">
                    <span class="stat-value" id="latestRound">-</span>
                    <span class="stat-label">Latest Round</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs modern-tabs">
        <button class="tab-btn active" data-tab="generated">
            <i class="fas fa-table"></i> Data Views
        </button>
        <button class="tab-btn" data-tab="analytics">
            <i class="fas fa-chart-line"></i> Analytics
        </button>
        <button class="tab-btn" data-tab="submitted">
            <i class="fas fa-file-upload"></i> Principal Reports
        </button>
        <?php if($canUpload): ?>
        <button class="tab-btn" data-tab="add">
            <i class="fas fa-plus-circle"></i> Upload Report
        </button>
        <?php endif; ?>
    </div>

    <!-- Tab: Data Views -->
    <div class="tab-content active" id="tab-generated">
        <div class="content-card">
            <div class="card-header">
                <h3><i class="fas fa-filter"></i> Filters & Options</h3>
                <div class="quick-actions">
                    <button class="action-btn" id="refreshDataBtn" title="Refresh Data">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <button class="action-btn" id="exportAllBtn" title="Export All Data">
                        <i class="fas fa-download"></i> Export All
                    </button>
                </div>
            </div>

            <div class="filter-panel">
                <div class="filter-group">
                    <label><i class="fas fa-eye"></i> View Type</label>
                    <select id="generatedViewSelect" class="modern-select">
                        <option value="vw_merged_field_lab_data" selected>Merged Field & Lab Data</option>
                        <option value="vw_reports_summary">Reports Summary</option>
                        <option value="vw_lab_reports">Laboratory Reports</option>
                        <option value="vw_field_data_reports">Field Data Reports</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-layer-group"></i> Round</label>
                    <select id="generatedRoundSelect" class="modern-select">
                        <option value="0">All Rounds</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-list"></i> Rows Per Page</label>
                    <select id="rowsPerPageGenerated" class="modern-select">
                        <option value="10" selected>10 Rows</option>
                        <option value="27">27 Rows</option>
                        <option value="54">54 Rows</option>
                        <option value="100">100 Rows</option>
                    </select>
                </div>

                <div class="filter-group search-group">
                    <label><i class="fas fa-search"></i> Search</label>
                    <input type="text" id="searchInputGenerated" class="modern-search" placeholder="Search records...">
                </div>
            </div>

            <div id="generated-table-container" class="table-container"></div>
        </div>
    </div>

    <!-- Tab: Analytics -->
    <div class="tab-content" id="tab-analytics">
        <div class="content-card">
            <h3><i class="fas fa-chart-line"></i> Data Analytics & Visualization</h3>

            <div class="analytics-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h4>Records by Round</h4>
                        <button class="chart-action" onclick="exportChart('roundsChart')">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                    <canvas id="roundsChart"></canvas>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h4>Species Distribution</h4>
                        <button class="chart-action" onclick="exportChart('speciesChart')">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                    <canvas id="speciesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Submitted Reports -->
    <div class="tab-content" id="tab-submitted">
        <div class="content-card">
            <div class="card-header">
                <h3><i class="fas fa-file-alt"></i> Principal Reports</h3>
            </div>

            <div class="filter-panel">
                <div class="filter-group">
                    <label><i class="fas fa-list"></i> Rows Per Page</label>
                    <select id="rowsPerPageSubmitted" class="modern-select">
                        <option value="10" selected>10 Rows</option>
                        <option value="27">27 Rows</option>
                        <option value="54">54 Rows</option>
                    </select>
                </div>

                <div class="filter-group search-group">
                    <label><i class="fas fa-search"></i> Search</label>
                    <input type="text" id="searchInputSubmitted" class="modern-search" placeholder="Search reports...">
                </div>
            </div>

            <div id="submitted-table-container" class="table-container"></div>
        </div>
    </div>

    <!-- Tab: Upload Report -->
    <?php if($canUpload): ?>
    <div class="tab-content" id="tab-add">
        <div class="content-card upload-card">
            <h3><i class="fas fa-cloud-upload-alt"></i> Upload New Report</h3>

            <form id="uploadReportForm" enctype="multipart/form-data" class="modern-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="round"><i class="fas fa-layer-group"></i> Round <span class="required">*</span></label>
                        <input type="number" name="round" id="round" min="1" required placeholder="Enter round number">
                    </div>

                    <div class="form-group">
                        <label for="cluster_name"><i class="fas fa-map-marker-alt"></i> Cluster</label>
                        <input type="text" name="cluster_name" id="cluster_name" value="all" placeholder="Cluster name or 'all'">
                    </div>
                </div>

                <div class="form-group">
                    <label for="report_file"><i class="fas fa-file-upload"></i> Select File <span class="required">*</span></label>
                    <div class="file-upload-area" id="fileUploadArea">
                        <input type="file" name="report_file" id="report_file" required accept=".pdf,.doc,.docx,.xls,.xlsx">
                        <div class="upload-placeholder">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click to browse or drag and drop file here</p>
                            <small>Supported formats: PDF, DOC, DOCX, XLS, XLSX (Max 10MB)</small>
                        </div>
                    </div>
                </div>

                <div id="preview-container" class="upload-preview"></div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="this.form.reset()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-upload"></i> Upload Report
                    </button>
                </div>
            </form>
        </div>

        <!-- Recent Uploaded Reports -->
        <div class="content-card" style="margin-top: 20px;">
            <div class="card-header">
                <h3><i class="fas fa-history"></i> Recent Uploads (Last 5)</h3>
            </div>
            <div id="recent-uploads-container" class="table-container"></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= BASE_URL ?>/assets/js/reports_modern.js" defer></script>
