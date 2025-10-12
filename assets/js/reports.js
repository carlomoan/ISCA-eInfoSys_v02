document.addEventListener('DOMContentLoaded', function () {

    // ===== TOAST NOTIFICATIONS =====
    let toastContainer = document.getElementById('toast-container');
    window.showToast = (message, type = 'info') => {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        toastContainer.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    };

    // ===== DataTable Class =====
    class DataTable {
        constructor({ containerId, searchInputId, rowsSelectId, apiUrl, type, viewSelectId, roundSelectId }) {
            this.container = document.querySelector(containerId);
            this.searchInput = document.querySelector(searchInputId);
            this.rowsSelect = document.querySelector(rowsSelectId);
            this.viewSelect = document.querySelector(viewSelectId);
            this.roundSelect = document.querySelector(roundSelectId);
            this.apiUrl = apiUrl;
            this.type = type;

            this.data = [];
            this.filteredData = [];
            this.currentPage = 1;
            this.rowsPerPage = parseInt(this.rowsSelect?.value) || 10;
            this.selectedRow = null;

            this.attachEventListeners();
            if(this.viewSelect && this.roundSelect) this.loadRounds();
            this.load();
        }

        attachEventListeners() {
            if (this.searchInput) {
                this.searchInput.addEventListener('input', () => { this.currentPage = 1; this.renderBody(); this.renderPagination(); });
            }
            if (this.rowsSelect) {
                this.rowsSelect.addEventListener('change', () => { this.rowsPerPage = parseInt(this.rowsSelect.value); this.currentPage = 1; this.renderBody(); this.renderPagination(); });
            }
            if (this.viewSelect) this.viewSelect.addEventListener('change', () => this.load());
            if (this.roundSelect) this.roundSelect.addEventListener('change', () => this.load());

            this.container.addEventListener('click', (e) => {
                if(e.target.matches('.download-btn')) this.downloadReport(e.target.dataset.id, e.target.dataset.filetype);
                if(e.target.matches('.view-btn')) this.openModal(e.target.dataset.id);
            });
        }

        async fetchJSON(url) {
            try { const res = await fetch(url); return await res.json(); } 
            catch { return { success: false, data: [] }; }
        }

        async loadRounds() {
            if(!this.roundSelect || !this.viewSelect) return;
            const url = `${this.apiUrl}?view=${this.viewSelect.value}&action=rounds`;
            try {
                const json = await this.fetchJSON(url);
                if(json.success && Array.isArray(json.rounds)) {
                    let options = '<option value="0">All Rounds</option>';
                    json.rounds.forEach(r => options += `<option value="${r}">Round ${r}</option>`);
                    this.roundSelect.innerHTML = options;
                } else this.roundSelect.innerHTML = '<option value="0">No Rounds</option>';
            } catch { this.roundSelect.innerHTML = '<option value="0">Error loading rounds</option>'; }
        }

        async load() {
            if(this.viewSelect) this.loadRounds();
            let url = `${this.apiUrl}?view=${this.viewSelect?.value || ''}`;
            if(this.roundSelect && this.roundSelect.value !== '0') url += `&round=${this.roundSelect.value}`;
            const json = await this.fetchJSON(url);
            if(json.success && Array.isArray(json.data)) { this.data = json.data; this.currentPage = 1; this.render(); } 
            else { this.data = []; this.container.innerHTML = '<p>No data available.</p>'; }
        }

        render() {
            this.renderTable(); this.renderBody(); this.renderPagination(); this.renderSummary();
        }

        renderTable() {
            if(!this.data.length) { this.container.innerHTML = '<p>No records found.</p>'; return; }
            const headers = Object.keys(this.data[0]);
            let html = `<div class="table-wrapper"><table class="data-table"><thead><tr>
                <th>S/N</th>${headers.map(h=>`<th>${h.replace(/_/g,' ').toUpperCase()}</th>`).join('')}
                <th>Actions</th></tr></thead><tbody></tbody></table></div>`;
            this.container.innerHTML = html;
        }

        renderBody() {
            if(!this.data.length) return;
            const searchTerm = this.searchInput?.value.toLowerCase() || '';
            this.filteredData = this.data.filter(r => Object.values(r).some(v => (v??'').toString().toLowerCase().includes(searchTerm)));
            const start = (this.currentPage-1)*this.rowsPerPage;
            const pageData = this.filteredData.slice(start, start+this.rowsPerPage);
            const tbody = this.container.querySelector('tbody');
            if(!tbody) return;

            if(!pageData.length) { tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;">No matching records</td></tr>'; this.renderPagination(); this.renderSummary(); return; }

            const headers = Object.keys(pageData[0]);
            tbody.innerHTML = pageData.map((row, idx)=>`<tr>
                <td>${start+idx+1}</td>${headers.map(h=>`<td>${row[h]??''}</td>`).join('')}
                <td>
                </td>
            </tr>`).join('');
            this.attachRowSelection(); this.attachDownloadHandlers(); this.renderSummary();
        }

        attachRowSelection() {
            this.container.querySelectorAll('tbody tr').forEach(row => {
                row.addEventListener('mouseenter',()=>row.classList.add('hover'));
                row.addEventListener('mouseleave',()=>row.classList.remove('hover'));
                row.addEventListener('click',()=>{ if(this.selectedRow && this.selectedRow!==row) this.selectedRow.classList.remove('selected'); this.selectedRow=row; row.classList.add('selected'); });
            });
        }

        renderPagination() {
            const totalPages = Math.ceil(this.filteredData.length/this.rowsPerPage)||1;
            let wrapper = this.container.querySelector('.pagination-wrapper');
            if(!wrapper) { wrapper=document.createElement('div'); wrapper.className='pagination-wrapper'; this.container.appendChild(wrapper); }
            if(totalPages<=1){ wrapper.innerHTML=''; return; }

            let html=''; for(let i=1;i<=totalPages;i++) html+=`<a href="javascript:void(0);" class="pagination-link ${i===this.currentPage?'active':''}" data-page="${i}">${i}</a>`;
            wrapper.innerHTML=html;
            wrapper.querySelectorAll('a').forEach(link=>link.addEventListener('click',()=>{ this.currentPage=parseInt(link.dataset.page); this.renderBody(); this.renderPagination(); }));
        }

        renderSummary() {
            let summary = this.container.querySelector('.table-summary');
            if(!summary) { summary=document.createElement('div'); summary.className='table-summary'; this.container.prepend(summary); }
            const totalRows=this.filteredData.length;
            const start=(this.currentPage-1)*this.rowsPerPage+1;
            const end=Math.min(this.currentPage*this.rowsPerPage,totalRows);
            summary.textContent=`Showing ${start} to ${end} of ${totalRows} row(s)` + (this.roundSelect?.value && this.roundSelect.value!=='0'?` — Round: ${this.roundSelect.value}`:'');
        }

        attachDownloadHandlers() {
            this.container.querySelectorAll('.download-btn').forEach(btn=>{
                btn.addEventListener('click',()=>this.downloadReport(btn.dataset.id, btn.dataset.filetype));
            });
        }

        downloadReport(id,filetype='excel') {
            const url=`${BASE_URL}/controllers/download_report.php?type=${this.type}&id=${id}&filetype=${filetype}`; 
            window.location.href=url;
        }

        openModal(id) {
            const modal=document.getElementById('reportModal');
            const modalBody=document.getElementById('modalBody');
            if(!modal||!modalBody) return;
            modal.style.display='block'; modalBody.innerHTML='<p>Loading...</p>';
            fetch(`${BASE_URL}/controllers/view_report.php?type=${this.type}&id=${id}`)
                .then(r=>r.text()).then(html=>modalBody.innerHTML=html)
                .catch(()=>modalBody.innerHTML='<p style="color:red;">Failed to load</p>');
        }
    }

    // ===== Instantiate Tables =====
    const generatedTable = new DataTable({
        containerId:'#generated-table-container',
        searchInputId:'#searchInputGenerated',
        rowsSelectId:'#rowsPerPageGenerated',
        apiUrl:BASE_URL + '/api/report/generated_reports_api.php',
        type:'generated',
        viewSelectId:'#generatedViewSelect',
        roundSelectId:'#generatedRoundSelect'
    });

    const submittedTable = new DataTable({
        containerId:'#submitted-table-container',
        searchInputId:'#searchInputSubmitted',
        rowsSelectId:'#rowsPerPageSubmitted',
        apiUrl:BASE_URL + '/api/report/submitted_reports_api.php',
        type:'submitted'
    });

    // ===== Modal Close =====
    const modal=document.getElementById('reportModal');
    const closeBtn=modal?.querySelector('.close-btn');
    closeBtn?.addEventListener('click',()=>modal.style.display='none');
    window.addEventListener('click',e=>{if(e.target===modal) modal.style.display='none';});

});
