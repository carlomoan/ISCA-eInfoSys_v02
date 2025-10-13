/**
 * Modern Reports JavaScript
 * Handles report views, analytics, and file uploads
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize
    loadHeaderStats();
    loadGeneratedReports();
    initializeTabs();
    initializeFileUpload();

    // Tab switching
    function initializeTabs() {
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetTab = btn.dataset.tab;

                // Update active tab button
                tabButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                // Update active tab content
                tabContents.forEach(content => content.classList.remove('active'));
                const targetContent = document.getElementById(`tab-${targetTab}`);
                if (targetContent) {
                    targetContent.classList.add('active');

                    // Load content based on tab
                    if (targetTab === 'analytics') {
                        loadAnalytics();
                    } else if (targetTab === 'submitted') {
                        loadSubmittedReports();
                    } else if (targetTab === 'add') {
                        loadRecentUploads();
                    }
                }
            });
        });
    }

    // Load header statistics
    async function loadHeaderStats() {
        try {
            const response = await fetch(`${BASE_URL}/api/reports/get_stats.php`);
            const result = await response.json();

            if (result.success) {
                document.getElementById('totalRecords').textContent = result.stats.total_records || 0;
                document.getElementById('totalReports').textContent = result.stats.total_reports || 0;
                document.getElementById('latestRound').textContent = result.stats.latest_round || '-';
            }
        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }

    // Load generated reports
    function loadGeneratedReports() {
        const viewSelect = document.getElementById('generatedViewSelect');
        const roundSelect = document.getElementById('generatedRoundSelect');
        const rowsSelect = document.getElementById('rowsPerPageGenerated');
        const searchInput = document.getElementById('searchInputGenerated');
        const container = document.getElementById('generated-table-container');

        // Load rounds
        fetch(`${BASE_URL}/api/dataviewsapi/get_rounds_api.php`)
            .then(res => res.json())
            .then(response => {
                if (response.success && Array.isArray(response.data)) {
                    response.data.forEach(r => {
                        const option = document.createElement('option');
                        option.value = r;
                        option.textContent = `Round ${r}`;
                        roundSelect.appendChild(option);
                    });
                }
            });

        // Fetch and render data
        function fetchData() {
            const view = viewSelect.value;
            const round = roundSelect.value;
            const limit = rowsSelect.value;
            const search = searchInput.value;

            let url = `${BASE_URL}/api/reports/get_generated.php?view=${view}&limit=${limit}`;
            if (round !== '0') url += `&round=${round}`;
            if (search) url += `&search=${encodeURIComponent(search)}`;

            container.innerHTML = '<div class="loading">Loading...</div>';

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (data.success && Array.isArray(data.data)) {
                        renderTable(data.data);
                    } else {
                        container.innerHTML = '<p class="text-center">No data available</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading generated reports:', error);
                    container.innerHTML = '<p class="text-center text-danger">Error loading data</p>';
                });
        }

        function renderTable(data) {
            if (!data.length) {
                container.innerHTML = '<p class="text-center">No records found</p>';
                return;
            }

            const columns = Object.keys(data[0]);

            let html = `
                <table class="data-table">
                    <thead>
                        <tr>
                            ${columns.map(col => `<th>${col.replace(/_/g, ' ').toUpperCase()}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        ${data.map(row => `
                            <tr>
                                ${columns.map(col => `<td>${row[col] ?? '-'}</td>`).join('')}
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;

            container.innerHTML = html;
        }

        // Event listeners
        viewSelect.addEventListener('change', fetchData);
        roundSelect.addEventListener('change', fetchData);
        rowsSelect.addEventListener('change', fetchData);
        searchInput.addEventListener('input', debounce(fetchData, 500));

        // Quick actions
        document.getElementById('refreshDataBtn')?.addEventListener('click', fetchData);
        document.getElementById('exportAllBtn')?.addEventListener('click', () => {
            const view = viewSelect.value;
            window.location.href = `${BASE_URL}/controllers/download_report.php?type=generated&view=${view}&filetype=xlsx&export=all`;
        });

        fetchData();
    }

    // Load analytics
    async function loadAnalytics() {
        try {
            const response = await fetch(`${BASE_URL}/api/reports/get_analytics.php`);
            const result = await response.json();

            if (result.success) {
                // Create charts
                createRoundsChart(result.charts.by_round || []);
                createSpeciesChart(result.charts.by_species || []);
            }
        } catch (error) {
            console.error('Error loading analytics:', error);
        }
    }

    // Create charts
    function createRoundsChart(data) {
        const ctx = document.getElementById('roundsChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => `Round ${d.round}`),
                datasets: [{
                    label: 'Records',
                    data: data.map(d => d.count),
                    backgroundColor: 'rgba(0, 172, 237, 0.7)',
                    borderColor: 'rgba(0, 172, 237, 1)',
                    borderWidth: 2,
                    borderRadius: 6,
                    maxBarThickness: 60
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        cornerRadius: 6,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            font: { size: 11 }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    }

    function createSpeciesChart(data) {
        const ctx = document.getElementById('speciesChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(d => d.species),
                datasets: [{
                    data: data.map(d => d.count),
                    backgroundColor: [
                        'rgba(0, 172, 237, 0.85)',
                        'rgba(40, 167, 69, 0.85)',
                        'rgba(255, 193, 7, 0.85)',
                        'rgba(220, 53, 69, 0.85)',
                        'rgba(111, 66, 193, 0.85)',
                        'rgba(255, 140, 0, 0.85)'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 12,
                            font: { size: 11 },
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        cornerRadius: 6,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }

    // Load submitted reports
    function loadSubmittedReports() {
        const container = document.getElementById('submitted-table-container');

        container.innerHTML = '<div class="loading">Loading reports...</div>';

        fetch(`${BASE_URL}/api/reports/get_uploaded.php`)
            .then(res => res.json())
            .then(data => {
                if (data.success && Array.isArray(data.reports)) {
                    renderReportsTable(data.reports);
                } else {
                    container.innerHTML = '<p class="text-center">No reports found</p>';
                }
            })
            .catch(error => {
                console.error('Error loading submitted reports:', error);
                container.innerHTML = '<p class="text-center text-danger">Error loading reports</p>';
            });

        function renderReportsTable(reports) {
            if (!reports.length) {
                container.innerHTML = '<p class="text-center">No reports uploaded</p>';
                return;
            }

            let html = `
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Type</th>
                            <th>Round</th>
                            <th>Cluster</th>
                            <th>Uploaded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${reports.map(report => `
                            <tr>
                                <td>${report.file_name}</td>
                                <td>${report.report_type || 'General'}</td>
                                <td>Round ${report.round}</td>
                                <td>${report.cluster_name || 'All'}</td>
                                <td>${new Date(report.uploaded_at).toLocaleDateString()}</td>
                                <td>
                                    <button class="btn-icon" onclick="downloadReport(${report.id})" title="Download">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;

            container.innerHTML = html;
        }
    }

    // File upload
    function initializeFileUpload() {
        const form = document.getElementById('uploadReportForm');
        const fileInput = document.getElementById('report_file');
        const uploadArea = document.getElementById('fileUploadArea');
        const previewContainer = document.getElementById('preview-container');

        if (!form) return;

        // Drag and drop
        uploadArea?.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#00aced';
        });

        uploadArea?.addEventListener('dragleave', () => {
            uploadArea.style.borderColor = '#ddd';
        });

        uploadArea?.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#ddd';
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                showFilePreview(e.dataTransfer.files[0]);
            }
        });

        fileInput?.addEventListener('change', (e) => {
            if (e.target.files.length) {
                showFilePreview(e.target.files[0]);
            }
        });

        function showFilePreview(file) {
            previewContainer.innerHTML = `
                <div class="file-preview">
                    <i class="fas fa-file"></i>
                    <span>${file.name}</span>
                    <small>${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                </div>
            `;
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

            try {
                const response = await fetch(`${BASE_URL}/controllers/upload_report.php`, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Report uploaded successfully!', 'success');
                    form.reset();
                    previewContainer.innerHTML = '';
                    loadRecentUploads();
                } else {
                    showToast(result.message || 'Upload failed', 'error');
                }
            } catch (error) {
                showToast('An error occurred during upload', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Report';
            }
        });
    }

    // Load recent uploads
    function loadRecentUploads() {
        const container = document.getElementById('recent-uploads-container');
        if (!container) return;

        container.innerHTML = '<div class="loading">Loading recent uploads...</div>';

        fetch(`${BASE_URL}/api/reports/get_uploaded.php?limit=5`)
            .then(res => res.json())
            .then(data => {
                if (data.success && Array.isArray(data.reports) && data.reports.length > 0) {
                    let html = `
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>File Name</th>
                                    <th>Type</th>
                                    <th>Round</th>
                                    <th>Cluster</th>
                                    <th>Uploaded</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.reports.map(report => `
                                    <tr>
                                        <td>${report.file_name}</td>
                                        <td>${report.report_type || 'General'}</td>
                                        <td>Round ${report.round}</td>
                                        <td>${report.cluster_name || 'All'}</td>
                                        <td>${new Date(report.uploaded_at).toLocaleDateString()}</td>
                                        <td>
                                            <button class="btn-icon" onclick="downloadReport(${report.id})" title="Download">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p class="text-center">No recent uploads</p>';
                }
            })
            .catch(error => {
                console.error('Error loading recent uploads:', error);
                container.innerHTML = '<p class="text-center text-danger">Error loading recent uploads</p>';
            });
    }

    // Utility functions
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
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

// Global function for downloading reports
function downloadReport(reportId) {
    window.location.href = `${BASE_URL}/controllers/download_report.php?type=uploaded&id=${reportId}`;
}

// Global function for exporting charts
function exportChart(chartId) {
    const canvas = document.getElementById(chartId);
    if (canvas) {
        const url = canvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.download = `${chartId}_${Date.now()}.png`;
        link.href = url;
        link.click();
    }
}
