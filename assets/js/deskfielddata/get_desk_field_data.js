document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('views-desk-field-table-container');
    const searchInput = document.getElementById('filter-search');
    const roundSelect = document.getElementById('filter-round');
    const rowsSelect = document.getElementById('rowsPerPageGenerated');

    let tableData = [];
    let rowsPerPage = parseInt(rowsSelect?.value) || 10;
    let currentPage = 1;
    let selectedRow = null;

    const refreshInterval = 15000; // 15 seconds
    let refreshTimer;
    let searchPauseTimer;

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
    function loadTable(round = 0, search = '', page = 1) {
        const params = new URLSearchParams({ page, rowsPerPage });
        if (round > 0) params.append('round', round);
        if (search) params.append('search', search);

        fetch(BASE_URL + '/api/deskfieldapi/get_desk_field_data_api.php?' + params.toString())
            .then(res => res.json())
            .then(response => {
                if (response.success && Array.isArray(response.data)) {
                    tableData = response.data;
                    currentPage = response.currentPage || 1;
                    rowsPerPage = response.rowsPerPage || rowsPerPage;
                    renderTable();
                    renderPagination(response.totalRecords);
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

    // ===== Render Table =====
    function renderTable() {
        if (!container) return;

        const searchTerm = searchInput.value.toLowerCase();
        const start = (currentPage - 1) * rowsPerPage;

        if (tableData.length === 0) {
            container.innerHTML = "<p>No matching records.</p>";
            return;
        }

        const roundText = roundSelect?.value === "0" ? "All rounds" : `Round ${roundSelect.value}`;
        const summaryHtml = `<div class="table-summary">Showing ${tableData.length} row(s) — ${roundText}</div>`;

        // Simplified columns for better UX
        const displayColumns = [
            { key: 'round', label: 'Round' },
            { key: 'hhname', label: 'HH Name' },
            { key: 'hhcode', label: 'HH Code' },
            { key: 'clstname', label: 'Cluster Name' },
            { key: 'clstid', label: 'Cluster ID' },
            { key: 'fldrecname', label: 'Field Recorder' },
            { key: 'user_id', label: 'User ID' }
        ];

        let html = summaryHtml + `<div class="table-wrapper"><table class="data-table">
            <thead>
                <tr>
                    <th>S/N</th>
                    ${displayColumns.map(c => `<th>${c.label}</th>`).join('')}
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                ${tableData.map((row, idx) => `<tr>
                    <td>${start + idx + 1}</td>
                    ${displayColumns.map(col => `<td>${highlightText(row[col.key] ?? "-", searchTerm)}</td>`).join('')}
                    <td>
                        <button class="view-btn" data-index="${idx}" title="View full details">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button class="download-btn" data-id="${row.hhcode}" data-filetype="pdf" title="Download PDF">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                    </td>
                </tr>`).join('')}
            </tbody>
        </table></div>`;

        container.innerHTML = html;

        container.querySelectorAll('tbody tr').forEach(row => {
            row.addEventListener('mouseenter', () => row.classList.add('hover'));
            row.addEventListener('mouseleave', () => row.classList.remove('hover'));
            row.addEventListener('click', () => {
                if (selectedRow && selectedRow !== row) selectedRow.classList.remove('selected');
                selectedRow = row;
                row.classList.add('selected');
            });
        });

        attachDownloadHandlers();
    }

    // ===== Modern Pagination with Prev/Next =====
    function renderPagination(totalRecords) {
        if (!container) return;

        const totalPages = Math.ceil(totalRecords / rowsPerPage);

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
                    loadTable(parseInt(roundSelect.value), searchInput.value, currentPage);
                }
            });
        });
    }

    // ===== Modal for Viewing Record Details =====
    function showRecordModal(recordData) {
        // Create modal HTML with collapsible sections
        const modalHTML = `
            <div class="modal-overlay" id="recordModal">
                <div class="modal-container">
                    <div class="modal-header">
                        <h2><i class="fas fa-file-alt"></i> Field Collection Details</h2>
                        <button class="modal-close" aria-label="Close modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Basic Information Section -->
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
                                    <div class="info-item">
                                        <label>Cluster Type:</label>
                                        <span>${recordData.clsttype_lst || '-'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Field Collection Details -->
                        <div class="collapse-section">
                            <button class="collapse-header">
                                <i class="fas fa-chevron-right"></i>
                                <span>Field Collection Details</span>
                            </button>
                            <div class="collapse-content">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label>Field Recorder:</label>
                                        <span>${recordData.fldrecname || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Collection Date:</label>
                                        <span>${recordData.field_coll_date || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Start Time:</label>
                                        <span>${recordData.start || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>End Time:</label>
                                        <span>${recordData.end || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Device ID:</label>
                                        <span>${recordData.deviceid || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>User ID:</label>
                                        <span>${recordData.user_id || '-'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Collection Data -->
                        <div class="collapse-section">
                            <button class="collapse-header">
                                <i class="fas fa-chevron-right"></i>
                                <span>Collection Data</span>
                            </button>
                            <div class="collapse-content">
                                <div class="info-grid">
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

                        <!-- System Information -->
                        <div class="collapse-section">
                            <button class="collapse-header">
                                <i class="fas fa-chevron-right"></i>
                                <span>System Information</span>
                            </button>
                            <div class="collapse-content">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label>Instance ID:</label>
                                        <span class="text-mono">${recordData.instanceID || '-'}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Created At:</label>
                                        <span>${recordData.created_at || '-'}</span>
                                    </div>
                                    <div class="info-item full-width">
                                        <label>Form Title:</label>
                                        <span>${recordData.ento_fld_frm_title || '-'}</span>
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

        // Insert modal into DOM
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

    // ===== Download / View Handlers =====
    function attachDownloadHandlers() {
        container.querySelectorAll('.download-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const type = btn.dataset.filetype;
                showToast(`Downloading ${type.toUpperCase()} for ID ${id}`, 'info');
            });
        });
        container.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx = parseInt(btn.dataset.index);
                const record = tableData[idx];
                if (record) {
                    showRecordModal(record);
                }
            });
        });
    }

    // ===== Event Listeners =====
    searchInput?.addEventListener('input', () => {
        currentPage = 1;
        loadTable(parseInt(roundSelect.value), searchInput.value, currentPage);

        if (refreshTimer) clearInterval(refreshTimer);
        if (searchPauseTimer) clearTimeout(searchPauseTimer);

        searchPauseTimer = setTimeout(() => startAutoRefresh(), 2000);
    });

    rowsSelect?.addEventListener('change', () => {
        rowsPerPage = parseInt(rowsSelect.value) || 10;
        currentPage = 1;
        loadTable(parseInt(roundSelect.value), searchInput.value, currentPage);
    });

    roundSelect?.addEventListener('change', () => {
        currentPage = 1;
        loadTable(parseInt(roundSelect.value), searchInput.value, currentPage);
    });

    // ===== Auto-refresh =====
    function startAutoRefresh() {
        if (refreshTimer) clearInterval(refreshTimer);
        refreshTimer = setInterval(() => {
            loadTable(parseInt(roundSelect.value), searchInput.value, currentPage);
        }, refreshInterval);
    }

    // ===== Initial Load =====
    loadTable(0, '', 1);
    startAutoRefresh();
});
