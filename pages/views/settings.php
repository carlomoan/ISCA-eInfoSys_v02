<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . 'helpers/permission_helper.php';
require_once ROOT_PATH . 'config/db_connect.php';

// Permission check
if (!checkPermission('manage_settings') && !($_SESSION['is_admin'] ?? false)) {
    echo "<div class='no-access'><p>You do not have permission to view this page.</p></div>";
    exit;
}

// Fetch application settings
try {
    $stmt = $pdo->query("SELECT * FROM app_settings ORDER BY setting_key ASC");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $settings = [];
    error_log("Settings fetch error: " . $e->getMessage());
}
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/settings.css">

<div class="page-container">
    <div id="toast-container"></div>

    <nav class="breadcrumb">
        <i class="fas fa-home"></i>
        <a href="?page=dashboard">Dashboard</a> / <strong>Settings</strong>
    </nav>

    <h2><i class="fas fa-cog"></i> Application Settings</h2>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab-btn active" data-tab="general">General Settings</button>
        <button class="tab-btn" data-tab="clusters">Clusters</button>
        <button class="tab-btn" data-tab="menu">Menu Configuration</button>
        <button class="tab-btn" data-tab="system">System Settings</button>
    </div>

    <!-- Tab: General Settings -->
    <div class="tab-content active" id="tab-general">
        <div class="settings-section">
            <h3>General Application Settings</h3>
            <div class="settings-grid">
                <?php if (empty($settings)): ?>
                    <div class="empty-state">
                        <i class="fas fa-cog"></i>
                        <p>No settings configured. Default settings are being used.</p>
                        <button type="button" id="initializeSettingsBtn" class="btn-primary">
                            <i class="fas fa-plus"></i> Initialize Default Settings
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($settings as $setting): ?>
                        <div class="setting-item">
                            <label><?= htmlspecialchars($setting['setting_name'] ?? $setting['setting_key']) ?></label>
                            <input
                                type="text"
                                class="setting-input"
                                data-key="<?= htmlspecialchars($setting['setting_key']) ?>"
                                value="<?= htmlspecialchars($setting['setting_value']) ?>"
                            >
                            <small class="text-muted"><?= htmlspecialchars($setting['description'] ?? '') ?></small>
                        </div>
                    <?php endforeach; ?>
                    <div class="settings-actions">
                        <button type="button" id="saveSettingsBtn" class="btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                        <button type="button" id="resetSettingsBtn" class="btn-secondary">
                            <i class="fas fa-undo"></i> Reset to Defaults
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tab: Clusters -->
    <div class="tab-content" id="tab-clusters">
        <div class="settings-section">
            <h3>Cluster Management</h3>
            <div class="top-actions">
                <button type="button" id="addClusterBtn" class="btn-primary">
                    <i class="fas fa-plus"></i> Add Cluster
                </button>
            </div>
            <div class="table-wrapper">
                <table class="data-table" id="clustersTable">
                    <thead>
                        <tr>
                            <th>Cluster ID</th>
                            <th>Cluster Name</th>
                            <th>Region</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="text-center">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab: Menu Configuration -->
    <div class="tab-content" id="tab-menu">
        <div class="settings-section">
            <h3>Menu Configuration</h3>
            <div class="menu-config">
                <p>Configure menu items, visibility, and ordering</p>
                <div class="menu-items-list" id="menuItemsList">
                    <div class="empty-state">
                        <i class="fas fa-bars"></i>
                        <p>Menu configuration coming soon...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: System Settings -->
    <div class="tab-content" id="tab-system">
        <div class="settings-section">
            <h3>System Information</h3>
            <div class="system-info">
                <div class="info-item">
                    <strong>PHP Version:</strong>
                    <span><?= phpversion() ?></span>
                </div>
                <div class="info-item">
                    <strong>Database:</strong>
                    <span><?= $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) ?> <?= $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) ?></span>
                </div>
                <div class="info-item">
                    <strong>Server Time:</strong>
                    <span><?= date('Y-m-d H:i:s') ?></span>
                </div>
                <div class="info-item">
                    <strong>Application Version:</strong>
                    <span>2.0.0</span>
                </div>
            </div>

            <h4>System Actions</h4>
            <div class="system-actions">
                <button type="button" class="btn-secondary" onclick="location.reload()">
                    <i class="fas fa-sync"></i> Refresh Cache
                </button>
                <button type="button" class="btn-secondary" id="clearLogsBtn">
                    <i class="fas fa-trash"></i> Clear Old Logs
                </button>
                <button type="button" class="btn-danger" id="exportDatabaseBtn">
                    <i class="fas fa-database"></i> Export Database
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.CURRENT_USER = {
        id: <?= json_encode($_SESSION['user_id'] ?? 0) ?>,
        role: <?= json_encode($_SESSION['role_name'] ?? '') ?>,
        name: <?= json_encode($_SESSION['full_name'] ?? '') ?>
    };
</script>

<script src="<?= BASE_URL ?>/assets/js/settings.js" defer></script>
