/**
 * Data Tab with Merged View Support
 * Displays data from merged_data_view with status/source badges and approval workflow
 */

document.addEventListener('DOMContentLoaded', function () {
    // ===== Variables =====
    const container = document.getElementById('views-table-container');
    const searchInput = document.getElementById('filter-search');
    const roundSelect = document.getElementById('filter-round');
    const rowsSelect = document.getElementById('rowsPerPageGenerated');

    // Check if user has approval permissions
    const canApprove = window.userPermissions?.approve_data || window.isAdmin || false;

    let tableData = [];
    let rowsPerPage = parseInt(rowsSelect?.value) || 10;
    let currentPage = 1;
    let selectedRow = null;
    let selectedRows = new Set();
    let currentFilters = {
        status: 'all', // all, permanent, pending
        source: 'all', // all, ODK, Internal Form
        dataType: 'all' // all, field, lab
    };

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
        toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i> ${message}`;
        toastContainer.appendChild(toast);

        setTimeout(() => toast.remove(), 4000);
    }

    // ===== Load rounds dynamically =====
    if (roundSelect) {
        fetch(BASE_URL + '/api/dataviewsapi/get_rounds_api.php')
            .then(res => res.json())
            .then(response => {
                if (response.success && Array.isArray(response.data)) {
                    let options = '<option value="0"> All Rounds </option>';
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

    // ===== Load Table Data from Merged View =====
    function loadTable(round = 0) {
        let url = BASE_URL + '/api/data/get_merged_data.php';
        const params = new URLSearchParams();

        if (round > 0) params.append('round', round);
        if (currentFilters.status !== 'all') params.append('status', currentFilters.status);
        if (currentFilters.source !== 'all') params.append('source', currentFilters.source);
        if (currentFilters.dataType !== 'all') params.append('data_type', currentFilters.dataType);

        if (params.toString()) url += '?' + params.toString();

        fetch(url)
            .then(res => res.json())
            .then(response => {
                if (response.success && Array.isArray(response.data)) {
                    tableData = response.data;
                    currentPage = 1;
                    renderFilters(response.summary);
                    renderTable();
                    renderPagination();
                } else {
                    container.innerHTML = `<p>${response.message || 'No data available.'}</p>`;
                }
            })
            .catch(err => {
                container.innerHTML = `<p>Error loading data: ${err.message}</p>`;
                console.error('Load error:', err);
            });
    }

    // ===== Render Filter Controls =====
    function renderFilters(summary) {
        const filtersHtml = `
            <div class="data-filters">
                <div class="filter-section">
                    <label><i class="fas fa-filter"></i> Status:</label>
                    <select id="filter-status" class="filter-select">
                        <option value="all">All (${summary.total})</option>
                        <option value="permanent">Permanent (${summary.permanent})</option>
                        <option value="pending">Pending (${summary.pending})</option>
                    </select>
                </div>
                <div class="filter-section">
                    <label><i class="fas fa-database"></i> Source:</label>
                    <select id="filter-source" class="filter-select">
                        <option value="all">All</option>
                        <option value="ODK">ODK (${summary.odk})</option>
                        <option value="Internal Form">Internal Form (${summary.internal})</option>
                    </select>
                </div>
                <div class="filter-section">
                    <label><i class="fas fa-table"></i> Type:</label>
                    <select id="filter-data-type" class="filter-select">
                        <option value="all">All</option>
                        <option value="field">Field (${summary.field})</option>
                        <option value="lab">Lab (${summary.lab})</option>
                    </select>
                </div>
            </div>
        `;

        // Insert before table container
        let existingFilters = document.querySelector('.data-filters');
        if (existingFilters) {
            existingFilters.outerHTML = filtersHtml;
        } else {
            container.insertAdjacentHTML('beforebegin', filtersHtml);
        }

        // Attach filter listeners
        document.getElementById('filter-status')?.addEventListener('change', (e) => {
            currentFilters.status = e.target.value;
            loadTable(parseInt(roundSelect?.value || 0));
        });

        document.getElementById('filter-source')?.addEventListener('change', (e) => {
            currentFilters.source = e.target.value;
            loadTable(parseInt(roundSelect?.value || 0));
        });

        document.getElementById('filter-data-type')?.addEventListener('change', (e) => {
            currentFilters.dataType = e.target.value;
            loadTable(parseInt(roundSelect?.value || 0));
        });

        // Set current values
        if (document.getElementById('filter-status')) {
            document.getElementById('filter-status').value = currentFilters.status;
        }
        if (document.getElementById('filter-source')) {
            document.getElementById('filter-source').value = currentFilters.source;
        }
        if (document.getElementById('filter-data-type')) {
            document.getElementById('filter-data-type').value = currentFilters.dataType;
        }
    }

    // ===== Create Status Badge =====
    function createStatusBadge(status) {
        if (status === 'permanent') {
            return '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Permanent</span>';
        } else if (status === 'pending') {
            return '<span class="badge badge-warning"><i class="fas fa-clock"></i> Pending</span>';
        }
        return '<span class="badge badge-secondary">Unknown</span>';
    }

    // ===== Create Source Badge =====
    function createSourceBadge(source) {
        if (source === 'ODK') {
            return '<span class="badge badge-primary"><i class="fas fa-mobile-alt"></i> ODK</span>';
        } else if (source === 'Internal Form') {
            return '<span class="badge badge-info"><i class="fas fa-keyboard"></i> Internal</span>';
        }
        return '<span class="badge badge-secondary">Unknown</span>';
    }

    // ===== Create Type Badge =====
    function createTypeBadge(type) {
        if (type === 'field') {
            return '<span class="badge badge-field"><i class="fas fa-map-marker-alt"></i> Field</span>';
        } else if (type === 'lab') {
            return '<span class="badge badge-lab"><i class="fas fa-flask"></i> Lab</span>';
        }
        return '';
    }

    // ===== Highlight search text =====
    function highlightText(text, search) {
        if (!search) return text;
        const regex = new RegExp(`(${search.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return text.toString().replace(regex, '<mark>$1</mark>');
    }

    // ===== Render Table =====
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
                    <button class="action-btn" id="clearSelectionBtn" title="Clear Selection">
                        <i class="fas fa-times"></i> Clear
                    </button>
                ` : ''}
                <button class="export-btn" data-format="csv" title="Export to CSV">
                    <i class="fas fa-file-csv"></i> CSV
                </button>
                <button class="export-btn" data-format="xlsx" title="Export to Excel">
                    <i class="fas fa-file-excel"></i> XLSX
                </button>
            </div>
        `;

        const summaryHtml = `<div class="table-summary">
            <span>Showing ${filteredData.length} row(s) — ${roundText}</span>
            ${exportButtonsHtml}
        </div>`;

        // Column definition with status and source
        const displayColumns = [
            { key: 'round', label: 'Round' },
            { key: 'data_type', label: 'Type' },
            { key: 'data_status', label: 'Status' },
            { key: 'data_source', label: 'Source' },
            { key: 'hhname', label: 'HH Name (Code)' },
            { key: 'clstname', label: 'Cluster Name (ID)' },
            { key: 'field_recorder', label: 'Field Recorder' },
            { key: 'lab_sorter', label: 'Lab Sorter' }
        ];

        let html = summaryHtml + `<div class="table-wrapper"><table class="data-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAllCheckbox" title="Select All"></th>
                    <th>S/N</th>
                    ${displayColumns.map(c => `<th>${c.label}</th>`).join('')}
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                ${paginatedData.map((row, idx) => {
                    const globalIdx = start + idx;
                    const isSelected = selectedRows.has(globalIdx);
                    const isPending = row.data_status === 'pending';
                    const hhDisplay = `${row.hhname || '-'} <small>(${row.hhcode || '-'})</small>`;
                    const clstDisplay = `${row.clstname || '-'} <small>(${row.clstid || '-'})</small>`;

                    return `<tr class="${isSelected ? 'selected' : ''} ${isPending ? 'row-pending' : 'row-permanent'}" data-index="${globalIdx}">
                        <td><input type="checkbox" class="row-checkbox" data-index="${globalIdx}" ${isSelected ? 'checked' : ''}></td>
                        <td>${globalIdx + 1}</td>
                        <td>${highlightText(row.round || '-', searchTerm)}</td>
                        <td>${createTypeBadge(row.data_type)}</td>
                        <td>${createStatusBadge(row.data_status)}</td>
                        <td>${createSourceBadge(row.data_source)}</td>
                        <td>${highlightText(hhDisplay, searchTerm)}</td>
                        <td>${highlightText(clstDisplay, searchTerm)}</td>
                        <td>${highlightText(row.field_recorder || '-', searchTerm)}</td>
                        <td>${highlightText(row.lab_sorter || '-', searchTerm)}</td>
                        <td class="actions-cell">
                            <button class="view-btn btn-small" data-index="${globalIdx}" title="View details">
                                <i class="fas fa-eye"></i>
                            </button>
                            ${isPending && canApprove ? `
                                <button class="approve-btn btn-small btn-success" data-index="${globalIdx}" title="Approve">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="reject-btn btn-small btn-danger" data-index="${globalIdx}" title="Reject">
                                    <i class="fas fa-times"></i>
                                </button>
                            ` : ''}
                        </td>
                    </tr>`;
                }).join('')}
            </tbody>
        </table></div>`;

        container.innerHTML = html;

        // Attach handlers
        attachCheckboxHandlers();
        attachActionHandlers();
    }

    // ===== Checkbox Handlers =====
    function attachCheckboxHandlers() {
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

        // Clear selection button
        const clearBtn = document.getElementById('clearSelectionBtn');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                selectedRows.clear();
                renderTable();
                renderPagination();
            });
        }
    }

    // ===== Action Handlers =====
    function attachActionHandlers() {
        // View button
        container.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx = parseInt(btn.dataset.index);
                const record = tableData[idx];
                if (record) {
                    showRecordModal(record);
                }
            });
        });

        // Approve button
        container.querySelectorAll('.approve-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx = parseInt(btn.dataset.index);
                const record = tableData[idx];
                if (record && confirm(`Approve this ${record.data_type} record for ${record.hhname}?`)) {
                    approveRecord(record);
                }
            });
        });

        // Reject button
        container.querySelectorAll('.reject-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx = parseInt(btn.dataset.index);
                const record = tableData[idx];
                if (record) {
                    showRejectModal(record);
                }
            });
        });

        // Export buttons
        container.querySelectorAll('.export-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const format = btn.dataset.format;
                exportData(format);
            });
        });
    }

    // ===== Approve Record =====
    function approveRecord(record) {
        fetch(BASE_URL + '/api/data/approve_desk_data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                data_type: record.data_type,
                hhcode: record.hhcode,
                round: record.round
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast(`${record.data_type} data approved successfully!`, 'success');
                loadTable(parseInt(roundSelect?.value || 0));
            } else {
                showToast(`Approval failed: ${data.message}`, 'error');
            }
        })
        .catch(err => {
            showToast(`Error: ${err.message}`, 'error');
        });
    }

    // ===== Show Reject Modal =====
    function showRejectModal(record) {
        const modalHtml = `
            <div class="modal-overlay" id="rejectModal">
                <div class="modal-container" style="max-width: 500px;">
                    <div class="modal-header">
                        <h3><i class="fas fa-times-circle"></i> Reject ${record.data_type} Data</h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Household:</strong> ${record.hhname} (${record.hhcode})</p>
                        <p><strong>Round:</strong> ${record.round}</p>
                        <div class="form-group">
                            <label for="reject-reason">Rejection Reason <span style="color:red">*</span></label>
                            <textarea id="reject-reason" rows="4" placeholder="Enter reason for rejection (minimum 5 characters)..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-secondary modal-close">Cancel</button>
                        <button class="btn-danger" id="confirmRejectBtn">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = document.getElementById('rejectModal');

        // Close handlers
        modal.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', () => modal.remove());
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });

        // Confirm reject
        document.getElementById('confirmRejectBtn').addEventListener('click', () => {
            const reason = document.getElementById('reject-reason').value.trim();
            if (reason.length < 5) {
                alert('Please enter a reason (minimum 5 characters)');
                return;
            }

            fetch(BASE_URL + '/api/data/reject_desk_data.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    data_type: record.data_type,
                    hhcode: record.hhcode,
                    round: record.round,
                    reason: reason
                })
            })
            .then(res => res.json())
            .then(data => {
                modal.remove();
                if (data.success) {
                    showToast(`${record.data_type} data rejected successfully`, 'success');
                    loadTable(parseInt(roundSelect?.value || 0));
                } else {
                    showToast(`Rejection failed: ${data.message}`, 'error');
                }
            })
            .catch(err => {
                modal.remove();
                showToast(`Error: ${err.message}`, 'error');
            });
        });
    }

    // ===== Show Record Modal (simplified) =====
    function showRecordModal(record) {
        const modalHtml = `
            <div class="modal-overlay" id="recordModal">
                <div class="modal-container">
                    <div class="modal-header">
                        <h3><i class="fas fa-database"></i> ${record.data_type} Data - ${record.hhname}</h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="info-grid">
                            <div class="info-item"><label>Type:</label><span>${createTypeBadge(record.data_type)}</span></div>
                            <div class="info-item"><label>Status:</label><span>${createStatusBadge(record.data_status)}</span></div>
                            <div class="info-item"><label>Source:</label><span>${createSourceBadge(record.data_source)}</span></div>
                            <div class="info-item"><label>Round:</label><span>${record.round}</span></div>
                            <div class="info-item"><label>HH Code:</label><span>${record.hhcode}</span></div>
                            <div class="info-item"><label>HH Name:</label><span>${record.hhname}</span></div>
                            <div class="info-item"><label>Cluster ID:</label><span>${record.clstid}</span></div>
                            <div class="info-item"><label>Cluster Name:</label><span>${record.clstname}</span></div>
                            <div class="info-item"><label>Field Recorder:</label><span>${record.field_recorder || '-'}</span></div>
                            <div class="info-item"><label>Lab Sorter:</label><span>${record.lab_sorter || '-'}</span></div>
                            <div class="info-item"><label>Field Date:</label><span>${record.field_coll_date || '-'}</span></div>
                            <div class="info-item"><label>Lab Date:</label><span>${record.lab_date || '-'}</span></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-secondary modal-close">Close</button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = document.getElementById('recordModal');

        modal.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', () => modal.remove());
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });
    }

    // ===== Export Data =====
    function exportData(format) {
        const round = parseInt(roundSelect?.value || 0);
        let url = `${BASE_URL}/controllers/download_report.php?type=generated&filetype=${format}`;

        if (round > 0) {
            url += `&id=${round}`;
        } else {
            url += `&export=all`;
        }

        showToast(`Exporting to ${format.toUpperCase()}...`, 'info');
        window.location.href = url;
    }

    // ===== Pagination =====
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

        let paginationHtml = '';

        paginationHtml += `<button class="pagination-btn ${currentPage === 1 ? 'disabled' : ''}" data-page="1" ${currentPage === 1 ? 'disabled' : ''}>
            <i class="fas fa-angle-double-left"></i> First
        </button>`;

        paginationHtml += `<button class="pagination-btn ${currentPage === 1 ? 'disabled' : ''}" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}>
            <i class="fas fa-angle-left"></i> Prev
        </button>`;

        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, currentPage + 2);

        if (startPage > 1) {
            paginationHtml += `<span class="pagination-ellipsis">...</span>`;
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `<button class="pagination-link ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
        }

        if (endPage < totalPages) {
            paginationHtml += `<span class="pagination-ellipsis">...</span>`;
        }

        paginationHtml += `<button class="pagination-btn ${currentPage === totalPages ? 'disabled' : ''}" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}>
            Next <i class="fas fa-angle-right"></i>
        </button>`;

        paginationHtml += `<button class="pagination-btn ${currentPage === totalPages ? 'disabled' : ''}" data-page="${totalPages}" ${currentPage === totalPages ? 'disabled' : ''}>
            Last <i class="fas fa-angle-double-right"></i>
        </button>`;

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

    // ===== Event Listeners =====
    if (searchInput) searchInput.addEventListener('input', () => { currentPage=1; renderTable(); renderPagination(); });
    if (rowsSelect) rowsSelect.addEventListener('change', () => { rowsPerPage=parseInt(rowsSelect.value)||10; currentPage=1; renderTable(); renderPagination(); });
    if (roundSelect) roundSelect.addEventListener('change', () => { loadTable(parseInt(roundSelect.value)); });

    // ===== Initial Load =====
    loadTable(0);
});
