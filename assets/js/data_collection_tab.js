document.addEventListener('DOMContentLoaded', function () {

    // ===== Configuration =====
    const API_BASE = BASE_URL + '/api'; // adjust if needed

    const tabs = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    const toastContainer = document.getElementById('toast-container');

    let tableDataMap = {}; // { tabId: [rows] }
    let currentPageMap = {}; // { tabId: currentPage }
    let rowsPerPageMap = {}; // { tabId: rowsPerPage }

    // ===== Toast =====
    function showToast(message, type = 'info') {
        if(!toastContainer) return;
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        toastContainer.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    // ===== Tab switching =====
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            if(tab.disabled) return;

            tabs.forEach(t=>t.classList.remove('active'));
            tabContents.forEach(c=>c.classList.remove('active'));

            tab.classList.add('active');
            const tabId = 'tab-' + tab.dataset.tab;
            const container = document.getElementById(tabId);
            if(container) container.classList.add('active');

            if(!tableDataMap[tab.dataset.tab]) loadTable(tab.dataset.tab);
        });
    });

    // ===== API paths =====
    function getApiUrl(tabId){
        switch(tabId){
            case 'generated': return `${API_BASE}/dataviewsapi/get_views_api.php`;
            case 'desk_compare': return `${API_BASE}/deskfieldapi/get_desk_field_data_api.php`;
            case 'desk_compare_lab': return `${API_BASE}/desklabapi/get_all_desk_lab_data_api.php`;
            case 'desk_compare_merge': return `${API_BASE}/deskmergeapi/desk_merge_api.php`;
            case 'verify_ODK_merged_data': return `${API_BASE}/deskmergeapi/get_verify_odk_data.php`;
            case 'append_all_merged_data': return `${API_BASE}/deskmergeapi/get_append_all_data.php`;
            default: return '';
        }
    }

    // ===== Container selectors =====
    function getContainer(tabId){
        switch(tabId){
            case 'generated': return document.getElementById('views-table-container');
            case 'desk_compare': return document.getElementById('views-desk-field-table-container');
            case 'desk_compare_lab': return document.getElementById('views-desk-lab-table-container');
            case 'desk_compare_merge': return document.getElementById('views-desk_compare_merge-table-container');
            case 'verify_ODK_merged_data': return document.getElementById('views-verify-odk-table-container');
            case 'append_all_merged_data': return document.getElementById('views-append-all-table-container');
            default: return null;
        }
    }

    function getSearchInput(tabId){
        return document.querySelector(`input[data-tab="${tabId}"]`);
    }

    // ===== Load Table =====
    function loadTable(tabId){
        const url = getApiUrl(tabId);
        if(!url){
            showToast(`API for tab ${tabId} not configured`, 'error');
            return;
        }

        fetch(url)
        .then(res => res.text()) // first as text
        .then(text => {
            let json;
            try{
                json = JSON.parse(text);
            }catch(e){
                console.error("Invalid JSON response for tab", tabId, text);
                showToast(`Failed to load data for ${tabId}`, 'error');
                return;
            }

            if(json.success && Array.isArray(json.data)){
                tableDataMap[tabId] = json.data;
                currentPageMap[tabId] = 1;
                rowsPerPageMap[tabId] = 10;
                renderTable(tabId);
                renderPagination(tabId);
            }else{
                const container = getContainer(tabId);
                if(container) container.innerHTML = '<p>No data available.</p>';
            }
        })
        .catch(err=>{
            console.error("Fetch error:", err);
            showToast(`Error fetching data for ${tabId}`, 'error');
        });
    }

    // ===== Render Table =====
    function renderTable(tabId){
        const container = getContainer(tabId);
        const data = tableDataMap[tabId] || [];
        const currentPage = currentPageMap[tabId] || 1;
        const rowsPerPage = rowsPerPageMap[tabId] || 10;
        const searchInput = getSearchInput(tabId);

        if(!container) return;

        const searchTerm = (searchInput && searchInput.value) ? searchInput.value.toLowerCase() : '';
        const filteredData = data.filter(row =>
            Object.values(row).some(val=>(val??'').toString().toLowerCase().includes(searchTerm))
        );

        const start = (currentPage-1)*rowsPerPage;
        const paginatedData = filteredData.slice(start, start+rowsPerPage);

        if(paginatedData.length === 0){
            container.innerHTML = "<p>No matching records.</p>";
            return;
        }

        const columns = Object.keys(paginatedData[0]);
        let html = `<div class="table-summary">Showing ${filteredData.length} row(s)</div>
                    <div class="table-wrapper"><table class="data-table">
                        <thead><tr>
                        <th>S/N</th>
                        ${columns.map(c=>`<th>${c.replace(/_/g,' ').toUpperCase()}</th>`).join('')}
                        <th>Actions</th>
                        </tr></thead>
                        <tbody>
                        ${paginatedData.map((row,idx)=>`
                        <tr>
                            <td>${start+idx+1}</td>
                            ${columns.map(col=>`<td>${row[col]??''}</td>`).join('')}
                            <td>
                                <button class="view-btn" data-id="${row[columns[0]]}" data-tab="${tabId}">View</button>
                                <button class="download-btn" data-id="${row[columns[0]]}" data-filetype="excel">Excel</button>
                                <button class="download-btn" data-id="${row[columns[0]]}" data-filetype="pdf">PDF</button>
                            </td>
                        </tr>
                        `).join('')}
                        </tbody>
                    </table></div>`;

        container.innerHTML = html;

        attachDownloadHandlers(container, tabId);
    }

    // ===== Pagination =====
    function renderPagination(tabId){
        const container = getContainer(tabId);
        const data = tableDataMap[tabId] || [];
        const currentPage = currentPageMap[tabId] || 1;
        const rowsPerPage = rowsPerPageMap[tabId] || 10;
        const searchInput = getSearchInput(tabId);
        if(!container) return;

        const searchTerm = (searchInput && searchInput.value) ? searchInput.value.toLowerCase() : '';
        const filteredData = data.filter(row =>
            Object.values(row).some(val=>(val??'').toString().toLowerCase().includes(searchTerm))
        );

        const totalPages = Math.ceil(filteredData.length / rowsPerPage);
        let paginationContainer = container.querySelector('.pagination-wrapper');
        if(!paginationContainer){
            paginationContainer = document.createElement('div');
            paginationContainer.className = 'pagination-wrapper';
            container.appendChild(paginationContainer);
        }

        if(totalPages<=1){
            paginationContainer.innerHTML = '';
            return;
        }

        paginationContainer.innerHTML = '';
        for(let i=1;i<=totalPages;i++){
            const link = document.createElement('a');
            link.href="javascript:void(0);";
            link.className = `pagination-link ${i===currentPage?'active':''}`;
            link.dataset.page = i;
            link.textContent = i;
            link.addEventListener('click', ()=>{
                currentPageMap[tabId] = i;
                renderTable(tabId);
                renderPagination(tabId);
            });
            paginationContainer.appendChild(link);
        }
    }

    // ===== Search inputs =====
    document.querySelectorAll('input[data-tab]').forEach(input=>{
        input.addEventListener('input', function(){
            const tabId = this.dataset.tab;
            currentPageMap[tabId] = 1;
            renderTable(tabId);
            renderPagination(tabId);
        });
    });

    // ===== Rows per page for generated tab =====
    const rowsSelect = document.getElementById('rowsPerPageGenerated');
    if(rowsSelect){
        rowsSelect.addEventListener('change', ()=>{
            rowsPerPageMap['generated'] = parseInt(rowsSelect.value) || 10;
            currentPageMap['generated'] = 1;
            renderTable('generated');
            renderPagination('generated');
        });
    }

    // ===== Download buttons =====
    function attachDownloadHandlers(container, tabId){
        container.querySelectorAll('.download-btn').forEach(btn=>{
            btn.addEventListener('click', ()=>{
                const id = btn.dataset.id;
                const filetype = btn.dataset.filetype;
                let typeParam = tabId; // optionally map tabId -> type
                let url = `${BASE_URL}/controllers/download_report.php?id=${id}&filetype=${filetype}&type=${typeParam}`;
                window.location.href = url;
            });
        });
    }

    // ===== Initial load =====
    const activeTab = document.querySelector('.tab-btn.active')?.dataset.tab;
    if(activeTab) loadTable(activeTab);

});
