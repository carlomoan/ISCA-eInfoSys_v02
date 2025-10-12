document.addEventListener('DOMContentLoaded', function () {
    // ===== Variables =====
    const container = document.getElementById('views-table-container');
    const searchInput = document.getElementById('filter-search');
    const roundSelect = document.getElementById('filter-round');
    const rowsSelect = document.getElementById('rowsPerPageGenerated');

    let tableData = [];
    let rowsPerPage = parseInt(rowsSelect?.value) || 10;
    let currentPage = 1;
    let selectedRow = null;
    let selectedRows = new Set(); // Track selected row indices

    // ===== Toast Notifications =====
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

    // ===== Load rounds dynamically =====
    if (roundSelect) {
        fetch(BASE_URL + '/api/dataviewsapi/get_rounds_api.php')
            .then(res => res.json())
            .then(response => {
                if (response.success && Array.isArray(response.data)) {
                    let options = '<option value="0"> Select round </option>';
                    response.data.forEach(r => options += `<option value="${r}">Round ${r}</option>`);
                    roundSelect.innerHTML = options;
                } else {
                    roundSelect.innerHTML = '<option value="0">No rounds available</option>';
                }
            })
            .catch(err => {
                roundSelect.innerHTML = '<option value="0">Error loading rounds</option>';
                console.error(err);
            });
    }

    // ===== Load Table Data =====
    function loadTable(round = 0) {
        let url = BASE_URL + '/api/dataviewsapi/get_views_api.php';
        if (round > 0) url += `?round=${round}`;

        fetch(url)
            .then(res => res.json())
            .then(response => {
                if (response.success && Array.isArray(response.data)) {
                    tableData = response.data;
                    currentPage = 1;
                    renderTable();
                    renderPagination();
                } else {
                    container.innerHTML = '<p>No data available.</p>';
                }
            })
            .catch(err => {
                container.innerHTML = `<p>Error loading data: ${err}</p>`;
            });
    }

    // ===== Highlight search text =====
    function highlightText(text, search) {
        if (!search) return text;
        const regex = new RegExp(`(${search.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return text.toString().replace(regex, '<mark>$1</mark>');
    }

    // ===== Render Table with Simplified Columns =====
    function renderTable() {
        if (!container) return;

        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        const filteredData = tableData.filter(row =>
            Object.values(row).some(val => (val??'').toString().toLowerCase().includes(searchTerm))
        );

        const start = (currentPage - 1) * rowsPerPage;
        const paginatedData = filteredData.slice(start, start + rowsPerPage);

        if (paginatedData.length === 0) {
            container.innerHTML = "<p>No matching records.</p>";
            return;
        }

        const roundText = roundSelect?.value === "0" ? "All rounds" : `Round ${roundSelect.value}`;

        // Export and action buttons
        const selectedCount = selectedRows.size;
        const exportButtonsHtml = `
            <div class="export-buttons">
                ${selectedCount > 0 ? `
                    <span class="selected-count">${selectedCount} selected</span>
                    <button class="action-btn" id="viewSelectedBtn" title="View Selected">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="action-btn" id="exportSelectedBtn" title="Export Selected">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <button class="action-btn danger" id="deleteSelectedBtn" title="Delete Selected">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <button class="action-btn" id="clearSelectionBtn" title="Clear Selection">
                        <i class="fas fa-times"></i> Clear
                    </button>
                ` : ''}
                <button class="export-btn" data-format="csv" title="Export All to CSV">
                    <i class="fas fa-file-csv"></i> CSV
                </button>
                <button class="export-btn" data-format="xlsx" title="Export All to XLSX">
                    <i class="fas fa-file-excel"></i> XLSX
                </button>
                <button class="export-btn" data-format="xml" title="Export All to XML">
                    <i class="fas fa-file-code"></i> XML
                </button>
                <button class="export-btn" data-format="spss" title="Export All to SPSS">
                    <i class="fas fa-chart-bar"></i> SPSS
                </button>
                <button class="export-btn" data-format="pdf" title="Export All to PDF">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
            </div>
        `;

        const summaryHtml = `<div class="table-summary">
            <span>Showing ${filteredData.length} row(s) — ${roundText}</span>
            ${exportButtonsHtml}
        </div>`;

        // Column arrangement as requested: Round, HH Name(Code), Cluster Name(ID), Field Recorder, Lab Sorter, Actions
        // Removed field_user_id column as it was empty
        const displayColumns = [
            { key: 'round', label: 'Round' },
            { key: 'hhname', label: 'HH Name (Code)' },
            { key: 'hhcode', label: 'HH Code', hidden: true }, // Hidden but used for display
            { key: 'clstname', label: 'Cluster Name (ID)' },
            { key: 'clstid', label: 'Cluster ID', hidden: true },
            { key: 'field_recorder', label: 'Field Recorder' },
            { key: 'lab_sorter', label: 'Lab Sorter' }
        ];

        const visibleColumns = displayColumns.filter(c => !c.hidden);

        let html = summaryHtml + `<div class="table-wrapper"><table class="data-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAllCheckbox" title="Select All"></th>
                    <th>S/N</th>
                    ${visibleColumns.map(c => `<th>${c.label}</th>`).join('')}
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                ${paginatedData.map((row, idx) => {
                    const globalIdx = start + idx;
                    const isSelected = selectedRows.has(globalIdx);
                    const hhDisplay = `${row.hhname || '-'} (${row.hhcode || '-'})`;
                    const clstDisplay = `${row.clstname || '-'} (${row.clstid || '-'})`;

                    return `<tr class="${isSelected ? 'selected' : ''}" data-index="${globalIdx}">
                        <td><input type="checkbox" class="row-checkbox" data-index="${globalIdx}" ${isSelected ? 'checked' : ''}></td>
                        <td>${globalIdx + 1}</td>
                        <td>${highlightText(row.round || '-', searchTerm)}</td>
                        <td>${highlightText(hhDisplay, searchTerm)}</td>
                        <td>${highlightText(clstDisplay, searchTerm)}</td>
                        <td>${highlightText(row.field_recorder || '-', searchTerm)}</td>
                        <td>${highlightText(row.lab_sorter || '-', searchTerm)}</td>
                        <td>
                            <button class="view-btn" data-index="${globalIdx}" title="View full details">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="download-btn" data-id="${row.hhcode}" data-filetype="excel" title="Download Excel">
                                <i class="fas fa-file-excel"></i> Excel
                            </button>
                            <button class="download-btn" data-id="${row.hhcode}" data-filetype="pdf" title="Download PDF">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                        </td>
                    </tr>`;
                }).join('')}
            </tbody>
        </table></div>`;

        container.innerHTML = html;

        // Row hover & selection
        container.querySelectorAll('tbody tr').forEach(row => {
            row.addEventListener('mouseenter', () => row.classList.add('hover'));
            row.addEventListener('mouseleave', () => row.classList.remove('hover'));
            row.addEventListener('click', (e) => {
                if (!e.target.closest('button') && !e.target.closest('input[type="checkbox"]')) {
                    if (selectedRow && selectedRow !== row) selectedRow.classList.remove('selected');
                    selectedRow = row;
                    row.classList.add('selected');
                }
            });
        });

        // Checkbox handlers
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => {
                const checkboxes = container.querySelectorAll('.row-checkbox');
                checkboxes.forEach(cb => {
                    const idx = parseInt(cb.dataset.index);
                    if (e.target.checked) {
                        selectedRows.add(idx);
                        cb.checked = true;
                        cb.closest('tr').classList.add('selected');
                    } else {
                        selectedRows.delete(idx);
                        cb.checked = false;
                        cb.closest('tr').classList.remove('selected');
                    }
                });
                renderTable();
                renderPagination();
            });
        }

        container.querySelectorAll('.row-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const idx = parseInt(e.target.dataset.index);
                if (e.target.checked) {
                    selectedRows.add(idx);
                    e.target.closest('tr').classList.add('selected');
                } else {
                    selectedRows.delete(idx);
                    e.target.closest('tr').classList.remove('selected');
                }
                renderTable();
                renderPagination();
            });
        });

        // Action button handlers
        const clearSelectionBtn = document.getElementById('clearSelectionBtn');
        if (clearSelectionBtn) {
            clearSelectionBtn.addEventListener('click', () => {
                selectedRows.clear();
                renderTable();
                renderPagination();
            });
        }

        const viewSelectedBtn = document.getElementById('viewSelectedBtn');
        if (viewSelectedBtn) {
            viewSelectedBtn.addEventListener('click', () => {
                if (selectedRows.size === 0) {
                    showToast('No rows selected', 'warning');
                    return;
                }
                showToast(`Viewing ${selectedRows.size} selected records`, 'info');
                // TODO: Implement multi-view modal
            });
        }

        const exportSelectedBtn = document.getElementById('exportSelectedBtn');
        if (exportSelectedBtn) {
            exportSelectedBtn.addEventListener('click', () => {
                if (selectedRows.size === 0) {
                    showToast('No rows selected', 'warning');
                    return;
                }
                // Create export modal with format options
                const selectedIndices = Array.from(selectedRows);
                showExportModal(selectedIndices);
            });
        }

        const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
        if (deleteSelectedBtn) {
            deleteSelectedBtn.addEventListener('click', () => {
                if (selectedRows.size === 0) {
                    showToast('No rows selected', 'warning');
                    return;
                }
                if (confirm(`Are you sure you want to delete ${selectedRows.size} selected records? This cannot be undone.`)) {
                    deleteSelectedRecords(Array.from(selectedRows));
                }
            });
        }

        attachHandlers();
    }

    // Export modal for selected rows
    function showExportModal(selectedIndices) {
        const modalHTML = `
            <div class="modal-overlay" id="exportModal">
                <div class="modal-container" style="max-width: 400px;">
                    <div class="modal-header">
                        <h2><i class="fas fa-download"></i> Export Selected (${selectedIndices.length} rows)</h2>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Select export format:</p>
                        <div class="export-format-list">
                            <button class="export-format-btn" data-format="csv">
                                <i class="fas fa-file-csv"></i> CSV (UTF-8)
                            </button>
                            <button class="export-format-btn" data-format="xlsx">
                                <i class="fas fa-file-excel"></i> Excel XLSX
                            </button>
                            <button class="export-format-btn" data-format="xml">
                                <i class="fas fa-file-code"></i> XML
                            </button>
                            <button class="export-format-btn" data-format="spss">
                                <i class="fas fa-chart-bar"></i> SPSS
                            </button>
                            <button class="export-format-btn" data-format="pdf">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        const modal = document.getElementById('exportModal');

        modal.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', () => modal.remove());
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });

        modal.querySelectorAll('.export-format-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const format = btn.dataset.format;
                exportSelectedRows(selectedIndices, format);
                modal.remove();
            });
        });
    }

    // Export selected rows
    function exportSelectedRows(indices, format) {
        const selectedData = indices.map(idx => tableData[idx]).filter(Boolean);
        const ids = selectedData.map(row => row.id || row.hhcode).filter(Boolean);

        if (ids.length === 0) {
            showToast('No valid data to export', 'error');
            return;
        }

        const url = `${BASE_URL}/controllers/download_report.php?type=generated&filetype=${format}&current_ids=${encodeURIComponent(JSON.stringify(ids))}`;
        showToast(`Exporting ${ids.length} records to ${format.toUpperCase()}...`, 'info');
        window.location.href = url;
    }

    // Delete selected records
    function deleteSelectedRecords(indices) {
        showToast('Delete functionality coming soon...', 'info');
        // TODO: Implement delete API
    }

    // ===== Modal for Viewing Full Record Details =====
    function showRecordModal(recordData) {
        const modalHTML = `
            <div class="modal-overlay" id="recordModal">
                <div class="modal-container">
                    <div class="modal-header">
                        <h2><i class="fas fa-database"></i> Merged Field & Lab Data</h2>
                        <button class="modal-close" aria-label="Close modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Basic Information -->
                        <div class="collapse-section">
                            <button class="collapse-header active">
                                <i class="fas fa-chevron-down"></i>
                                <span>Basic Information</span>
                            </button>
                            <div class="collapse-content" style="display: block;">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label>Round:</label>
                                        <span>${recordData.round || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>HH Code:</label>
                                        <span>${recordData.hhcode || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>HH Name:</label>
                                        <span>${recordData.hhname || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Cluster ID:</label>
                                        <span>${recordData.clstid || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Cluster Name:</label>
                                        <span>${recordData.clstname || '-'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Field Collection Data -->
                        <div class="collapse-section">
                            <button class="collapse-header">
                                <i class="fas fa-chevron-right"></i>
                                <span>Field Collection Data</span>
                            </button>
                            <div class="collapse-content">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label>Field Recorder:</label>
                                        <span>${recordData.field_recorder || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Collection Date:</label>
                                        <span>${recordData.field_coll_date || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Start Time:</label>
                                        <span>${recordData.field_start || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>End Time:</label>
                                        <span>${recordData.field_end || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Device ID:</label>
                                        <span>${recordData.field_deviceid || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>User ID:</label>
                                        <span>${recordData.field_user_id || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>DDRLN:</label>
                                        <span>${recordData.ddrln || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>ANINSLN:</label>
                                        <span>${recordData.aninsln || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>DDLTWRK:</label>
                                        <span>${recordData.ddltwrk || '-'}</span>
                                    </div>
                                    <div class="info-item full-width">
                                        <label>DDLTWRK Comment:</label>
                                        <span>${recordData.ddltwrk_gcomment || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Light Trap ID:</label>
                                        <span>${recordData.lighttrapid || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Collection BG ID:</label>
                                        <span>${recordData.collectionbgid || '-'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Lab Sorting Data -->
                        <div class="collapse-section">
                            <button class="collapse-header">
                                <i class="fas fa-chevron-right"></i>
                                <span>Lab Sorting Data</span>
                            </button>
                            <div class="collapse-content">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label>Lab Sorter:</label>
                                        <span>${recordData.lab_sorter || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Lab Date:</label>
                                        <span>${recordData.lab_date || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Lab Start:</label>
                                        <span>${recordData.lab_start || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Lab End:</label>
                                        <span>${recordData.lab_end || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Lab Device ID:</label>
                                        <span>${recordData.lab_deviceid || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Lab User ID:</label>
                                        <span>${recordData.lab_user_id || '-'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Anopheles gambiae Counts -->
                        <div class="collapse-section">
                            <button class="collapse-header">
                                <i class="fas fa-chevron-right"></i>
                                <span>Anopheles gambiae (AG)</span>
                            </button>
                            <div class="collapse-content">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label>Male AG:</label>
                                        <span>${recordData.male_ag || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Female AG:</label>
                                        <span>${recordData.female_ag || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Fed AG:</label>
                                        <span>${recordData.fed_ag || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Unfed AG:</label>
                                        <span>${recordData.unfed_ag || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Gravid AG:</label>
                                        <span>${recordData.gravid_ag || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Semi-Gravid AG:</label>
                                        <span>${recordData.semi_gravid_ag || '0'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Anopheles funestus Counts -->
                        <div class="collapse-section">
                            <button class="collapse-header">
                                <i class="fas fa-chevron-right"></i>
                                <span>Anopheles funestus (AF)</span>
                            </button>
                            <div class="collapse-content">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label>Male AF:</label>
                                        <span>${recordData.male_af || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Female AF:</label>
                                        <span>${recordData.female_af || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Fed AF:</label>
                                        <span>${recordData.fed_af || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Unfed AF:</label>
                                        <span>${recordData.unfed_af || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Gravid AF:</label>
                                        <span>${recordData.gravid_af || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Semi-Gravid AF:</label>
                                        <span>${recordData.semi_gravid_af || '0'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Other Anopheles Counts -->
                        <div class="collapse-section">
                            <button class="collapse-header">
                                <i class="fas fa-chevron-right"></i>
                                <span>Other Anopheles (OAN)</span>
                            </button>
                            <div class="collapse-content">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label>Male OAN:</label>
                                        <span>${recordData.male_oan || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Female OAN:</label>
                                        <span>${recordData.female_oan || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Fed OAN:</label>
                                        <span>${recordData.fed_oan || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Unfed OAN:</label>
                                        <span>${recordData.unfed_oan || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Gravid OAN:</label>
                                        <span>${recordData.gravid_oan || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Semi-Gravid OAN:</label>
                                        <span>${recordData.semi_gravid_oan || '0'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Culex & Other Species -->
                        <div class="collapse-section">
                            <button class="collapse-header">
                                <i class="fas fa-chevron-right"></i>
                                <span>Culex & Other Species</span>
                            </button>
                            <div class="collapse-content">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label>Male Culex:</label>
                                        <span>${recordData.male_culex || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Female Culex:</label>
                                        <span>${recordData.female_culex || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Fed Culex:</label>
                                        <span>${recordData.fed_culex || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Unfed Culex:</label>
                                        <span>${recordData.unfed_culex || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Gravid Culex:</label>
                                        <span>${recordData.gravid_culex || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Semi-Gravid Culex:</label>
                                        <span>${recordData.semi_gravid_culex || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Male Other Culex:</label>
                                        <span>${recordData.male_other_culex || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Female Other Culex:</label>
                                        <span>${recordData.female_other_culex || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Male Aedes:</label>
                                        <span>${recordData.male_aedes || '0'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Female Aedes:</label>
                                        <span>${recordData.female_aedes || '0'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- System Information -->
                        <div class="collapse-section">
                            <button class="collapse-header">
                                <i class="fas fa-chevron-right"></i>
                                <span>System Information</span>
                            </button>
                            <div class="collapse-content">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label>Field Instance ID:</label>
                                        <span class="text-mono">${recordData.field_instanceID || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Lab Instance ID:</label>
                                        <span class="text-mono">${recordData.lab_instanceID || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Field Created:</label>
                                        <span>${recordData.field_created_at || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Lab Created:</label>
                                        <span>${recordData.lab_created_at || '-'}</span>
                                    </div>
                                    <div class="info-item full-width">
                                        <label>Field Form Title:</label>
                                        <span>${recordData.ento_fld_frm_title || '-'}</span>
                                    </div>
                                    <div class="info-item full-width">
                                        <label>Lab Form Title:</label>
                                        <span>${recordData.ento_lab_frm_title || '-'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-secondary modal-close">
                            <i class="fas fa-times"></i> Close
                        </button>
                        <button class="btn-primary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Insert modal
        const existingModal = document.getElementById('recordModal');
        if (existingModal) existingModal.remove();

        document.body.insertAdjacentHTML('beforeend', modalHTML);

        const modal = document.getElementById('recordModal');

        // Collapse/Expand handlers
        modal.querySelectorAll('.collapse-header').forEach(header => {
            header.addEventListener('click', function() {
                const content = this.nextElementSibling;
                const icon = this.querySelector('i');
                const isActive = this.classList.contains('active');

                if (isActive) {
                    this.classList.remove('active');
                    content.style.display = 'none';
                    icon.className = 'fas fa-chevron-right';
                } else {
                    this.classList.add('active');
                    content.style.display = 'block';
                    icon.className = 'fas fa-chevron-down';
                }
            });
        });

        // Close modal handlers
        modal.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', () => modal.remove());
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });

        // ESC key to close
        document.addEventListener('keydown', function escHandler(e) {
            if (e.key === 'Escape' && modal) {
                modal.remove();
                document.removeEventListener('keydown', escHandler);
            }
        });
    }

    // ===== Modern Pagination with Prev/Next =====
    function renderPagination() {
        if (!container) return;

        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        const filteredData = tableData.filter(row =>
            Object.values(row).some(val => (val??'').toString().toLowerCase().includes(searchTerm))
        );
        const totalPages = Math.ceil(filteredData.length / rowsPerPage);

        let paginationContainer = container.querySelector('.pagination-wrapper');
        if (!paginationContainer) {
            paginationContainer = document.createElement('div');
            paginationContainer.className = 'pagination-wrapper';
            container.appendChild(paginationContainer);
        }

        if (totalPages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }

        // Modern pagination: First | Prev | 1 2 3 | Next | Last
        let paginationHtml = '';

        // First button
        paginationHtml += `<button class="pagination-btn ${currentPage === 1 ? 'disabled' : ''}" data-page="1" ${currentPage === 1 ? 'disabled' : ''}>
            <i class="fas fa-angle-double-left"></i> First
        </button>`;

        // Previous button
        paginationHtml += `<button class="pagination-btn ${currentPage === 1 ? 'disabled' : ''}" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}>
            <i class="fas fa-angle-left"></i> Prev
        </button>`;

        // Page numbers (show current page ± 2)
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, currentPage + 2);

        // Show ellipsis if needed
        if (startPage > 1) {
            paginationHtml += `<span class="pagination-ellipsis">...</span>`;
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `<button class="pagination-link ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
        }

        if (endPage < totalPages) {
            paginationHtml += `<span class="pagination-ellipsis">...</span>`;
        }

        // Next button
        paginationHtml += `<button class="pagination-btn ${currentPage === totalPages ? 'disabled' : ''}" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}>
            Next <i class="fas fa-angle-right"></i>
        </button>`;

        // Last button
        paginationHtml += `<button class="pagination-btn ${currentPage === totalPages ? 'disabled' : ''}" data-page="${totalPages}" ${currentPage === totalPages ? 'disabled' : ''}>
            Last <i class="fas fa-angle-double-right"></i>
        </button>`;

        // Page info
        paginationHtml += `<span class="pagination-info">Page ${currentPage} of ${totalPages}</span>`;

        paginationContainer.innerHTML = paginationHtml;

        paginationContainer.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', function () {
                if (!this.disabled) {
                    currentPage = parseInt(this.dataset.page);
                    renderTable();
                    renderPagination();
                }
            });
        });
    }

    // ===== Download & View Handlers =====
    function attachHandlers() {
        // Export buttons handler
        container.querySelectorAll('.export-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const format = btn.dataset.format;
                const round = roundSelect?.value || 0;
                let url = `${BASE_URL}/controllers/download_report.php?type=generated&filetype=${format}`;

                if (round > 0) {
                    url += `&id=${round}`;
                } else {
                    url += `&export=all`;
                }

                // Show loading toast
                showToast(`Exporting data to ${format.toUpperCase()}...`, 'info');
                window.location.href = url;
            });
        });

        // Individual row download buttons
        container.querySelectorAll('.download-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const filetype = btn.dataset.filetype;
                let url = `${BASE_URL}/controllers/download_report.php?id=${id}&filetype=${filetype}&type=generated`;
                window.location.href = url;
            });
        });

        // View button handler
        container.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx = parseInt(btn.dataset.index);
                const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
                const filteredData = tableData.filter(row =>
                    Object.values(row).some(val => (val??'').toString().toLowerCase().includes(searchTerm))
                );
                const record = filteredData[idx];
                if (record) {
                    showRecordModal(record);
                }
            });
        });
    }

    // ===== Events =====
    if (searchInput) searchInput.addEventListener('input', () => { currentPage=1; renderTable(); renderPagination(); });
    if (rowsSelect) rowsSelect.addEventListener('change', () => { rowsPerPage=parseInt(rowsSelect.value)||10; currentPage=1; renderTable(); renderPagination(); });
    if (roundSelect) roundSelect.addEventListener('change', () => { loadTable(parseInt(roundSelect.value)); });

    // ===== Initial Load =====
    loadTable(0);
});
