/**
 * Settings Page JavaScript
 * Handles application settings, clusters, and menu configuration
 */

document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetTab = btn.dataset.tab;

            // Update active tab button
            tabButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Update active tab content
            tabContents.forEach(content => {
                content.classList.remove('active');
            });

            const targetContent = document.getElementById(`tab-${targetTab}`);
            if (targetContent) {
                targetContent.classList.add('active');
            }

            // Load data for specific tabs
            if (targetTab === 'clusters') {
                loadClusters();
            }
        });
    });

    // Save settings button
    const saveSettingsBtn = document.getElementById('saveSettingsBtn');
    if (saveSettingsBtn) {
        saveSettingsBtn.addEventListener('click', saveSettings);
    }

    // Initialize settings button
    const initializeSettingsBtn = document.getElementById('initializeSettingsBtn');
    if (initializeSettingsBtn) {
        initializeSettingsBtn.addEventListener('click', initializeSettings);
    }

    // Reset settings button
    const resetSettingsBtn = document.getElementById('resetSettingsBtn');
    if (resetSettingsBtn) {
        resetSettingsBtn.addEventListener('click', () => {
            if (confirm('Are you sure you want to reset all settings to defaults? This cannot be undone.')) {
                resetSettings();
            }
        });
    }

    // Clear logs button
    const clearLogsBtn = document.getElementById('clearLogsBtn');
    if (clearLogsBtn) {
        clearLogsBtn.addEventListener('click', () => {
            if (confirm('Clear all logs older than 30 days?')) {
                clearOldLogs();
            }
        });
    }

    // Export database button
    const exportDatabaseBtn = document.getElementById('exportDatabaseBtn');
    if (exportDatabaseBtn) {
        exportDatabaseBtn.addEventListener('click', exportDatabase);
    }

    // Functions
    function saveSettings() {
        const settings = [];
        document.querySelectorAll('.setting-input').forEach(input => {
            settings.push({
                key: input.dataset.key,
                value: input.value
            });
        });

        fetch(`${BASE_URL}/api/settings/update_settings.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({settings})
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('Settings saved successfully', 'success');
            } else {
                showToast(data.message || 'Failed to save settings', 'error');
            }
        })
        .catch(err => {
            console.error('Error saving settings:', err);
            showToast('An error occurred while saving settings', 'error');
        });
    }

    function initializeSettings() {
        fetch(`${BASE_URL}/api/settings/initialize_settings.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'}
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('Default settings initialized successfully', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message || 'Failed to initialize settings', 'error');
            }
        })
        .catch(err => {
            console.error('Error initializing settings:', err);
            showToast('An error occurred', 'error');
        });
    }

    function resetSettings() {
        fetch(`${BASE_URL}/api/settings/reset_settings.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'}
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('Settings reset to defaults', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message || 'Failed to reset settings', 'error');
            }
        })
        .catch(err => {
            console.error('Error resetting settings:', err);
            showToast('An error occurred', 'error');
        });
    }

    function loadClusters() {
        const tbody = document.querySelector('#clustersTable tbody');
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Loading clusters...</td></tr>';

        fetch(`${BASE_URL}/api/clusters/get_clusters.php`)
            .then(res => res.json())
            .then(data => {
                if (data.success && Array.isArray(data.clusters)) {
                    if (data.clusters.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No clusters found</td></tr>';
                    } else {
                        tbody.innerHTML = data.clusters.map(cluster => `
                            <tr>
                                <td>${cluster.clstid || '-'}</td>
                                <td>${cluster.clstname || '-'}</td>
                                <td>${cluster.region || '-'}</td>
                                <td>
                                    <span class="status-badge ${cluster.is_active ? 'active' : 'inactive'}">
                                        ${cluster.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-icon" onclick="editCluster(${cluster.id})" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-icon" onclick="deleteCluster(${cluster.id})" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `).join('');
                    }
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load clusters</td></tr>';
                }
            })
            .catch(err => {
                console.error('Error loading clusters:', err);
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading clusters</td></tr>';
            });
    }

    function clearOldLogs() {
        fetch(`${BASE_URL}/api/settings/clear_logs.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({days: 30})
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast(`Cleared ${data.count || 0} old log entries`, 'success');
            } else {
                showToast(data.message || 'Failed to clear logs', 'error');
            }
        })
        .catch(err => {
            console.error('Error clearing logs:', err);
            showToast('An error occurred', 'error');
        });
    }

    function exportDatabase() {
        showToast('Preparing database export...', 'info');
        window.location.href = `${BASE_URL}/api/settings/export_database.php`;
    }

    function showToast(message, type = 'info') {
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        toastContainer.appendChild(toast);

        setTimeout(() => toast.remove(), 4000);
    }
});
