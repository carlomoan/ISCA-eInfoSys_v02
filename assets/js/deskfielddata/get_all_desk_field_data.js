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

        fetch(BASE_URL + '/api/deskfieldapi/get_all_desk_field_data_api.php?' + params.toString())
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
        const columns = Object.keys(tableData[0]);

        let html = summaryHtml + `<div class="table-wrapper"><table class="data-table">
            <thead>
                <tr>
                    <th>S/N</th>
                    ${columns.map(c => `<th>${c.replace(/_/g,' ').toUpperCase()}</th>`).join('')}
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                ${tableData.map((row, idx) => `<tr>
                    <td>${start + idx + 1}</td>
                    ${columns.map(col => `<td>${highlightText(row[col] ?? "", searchTerm)}</td>`).join('')}
                    <td>
                        <button class="view-btn" data-id="${row.hhcode}">View</button>
                        <button class="download-btn" data-id="${row.hhcode}" data-filetype="pdf">PDF</button>
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

    // ===== Pagination =====
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

        let paginationHtml = '';
        for (let i = 1; i <= totalPages; i++) {
            paginationHtml += `<a href="javascript:void(0);" class="pagination-link ${i===currentPage?'active':''}" data-page="${i}">${i}</a>`;
        }
        paginationContainer.innerHTML = paginationHtml;

        paginationContainer.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function () {
                currentPage = parseInt(this.dataset.page);
                loadTable(parseInt(roundSelect.value), searchInput.value, currentPage);
            });
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
                const id = btn.dataset.id;
                showToast(`Viewing details for ID ${id}`, 'info');
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
            if (roundSelect && searchInput) {
                loadTable(parseInt(roundSelect.value), searchInput.value, currentPage);
            }
        }, refreshInterval);
    }

    // ===== Initial Load =====
    if (roundSelect && searchInput) {
        loadTable(0, '', 1);
        startAutoRefresh();
    }
});
