// ===== global-enhanced.js =====
document.addEventListener('DOMContentLoaded', () => {

  // ===== Toast Notification =====
  function showToast(message, type='info', duration=3000) {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.style.cssText = `
      position: fixed; bottom: 30px; right: 30px;
      background: ${type==='success'?'#4caf50':type==='error'?'#f44336':'#2196f3'};
      color: white; padding:12px 20px; border-radius:4px;
      font-family:Calibri,sans-serif;font-size:10px;
      box-shadow:0 2px 8px rgba(0,0,0,0.2);
      opacity:0; transition:opacity 0.3s ease; z-index:9999;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    requestAnimationFrame(()=>toast.style.opacity='1');
    setTimeout(()=> {
      toast.style.opacity='0';
      toast.addEventListener('transitionend', ()=>toast.remove());
    }, duration);
  }

  // ===== Sidebar Toggle (Collapse/Expand) =====
  const menuToggle = document.getElementById('menu-toggle');
  const sidebar = document.querySelector('.sidebar');
  const sidebarOverlay = document.querySelector('.sidebar-overlay');

  if (menuToggle && sidebar) {
    // Check if we're on desktop or mobile
    const isDesktop = () => window.innerWidth > 1024;

    // Load saved state for desktop
    let collapsed = localStorage.getItem('sidebarCollapsed');
    collapsed = collapsed === 'true';
    if (collapsed && isDesktop()) {
      sidebar.classList.add('collapsed');
    }

    menuToggle.addEventListener('click', () => {
      if (isDesktop()) {
        // Desktop: collapse/expand sidebar
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
      } else {
        // Mobile: show/hide sidebar as overlay
        sidebar.classList.toggle('open');
        sidebarOverlay?.classList.toggle('show');
      }
    });

    // Close sidebar when clicking overlay (mobile)
    if (sidebarOverlay) {
      sidebarOverlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        sidebarOverlay.classList.remove('show');
      });
    }
  }

  // ===== Dark Mode Toggle =====
  const darkToggle = document.getElementById('dark-toggle');
  if (darkToggle) {
    darkToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      localStorage.setItem('darkMode', document.body.classList.contains('dark') ? '1' : '0');
    });
    if (localStorage.getItem('darkMode') === '1') document.body.classList.add('dark');
  }

  // ===== Profile Dropdown =====
  const profileBtn = document.getElementById('profile-btn');
  const dropdownMenu = profileBtn?.nextElementSibling;
  if (profileBtn && dropdownMenu) {
    profileBtn.addEventListener('click', e => {
      e.stopPropagation();
      const isOpen = dropdownMenu.classList.toggle('show');
      profileBtn.setAttribute('aria-expanded', isOpen);
    });
    document.addEventListener('click', () => {
      dropdownMenu.classList.remove('show');
      profileBtn.setAttribute('aria-expanded', 'false');
    });
    // Keyboard nav
    const items = Array.from(dropdownMenu.querySelectorAll('a'));
    let focusIndex = -1;
    dropdownMenu.addEventListener('keydown', e => {
      if (e.key === 'ArrowDown') { e.preventDefault(); focusIndex = (focusIndex + 1) % items.length; items[focusIndex].focus(); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); focusIndex = (focusIndex - 1 + items.length) % items.length; items[focusIndex].focus(); }
      else if (e.key === 'Escape') { dropdownMenu.classList.remove('show'); profileBtn.focus(); profileBtn.setAttribute('aria-expanded', 'false'); }
    });
  }

  // ===== Tab Switching =====
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = btn.dataset.tab;
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById(`tab-${target}`)?.classList.add('active');
    });
  });

  // ===== Table: Search, Pagination, Export =====
  function setupTable(selector, rowsPerPage=10) {
    const table = document.querySelector(selector);
    if (!table) return;
    const tbody = table.querySelector('tbody'); if (!tbody) return;
    const rows = Array.from(tbody.querySelectorAll('tr'));
    let filteredRows = [...rows];
    let currentPage = 1;

    // Create search input
    const searchBox = document.createElement('input');
    searchBox.type = 'text';
    searchBox.placeholder = 'Search...';
    searchBox.className = 'custom-search';
    table.parentElement.insertBefore(searchBox, table);

    // Pagination container
    const pagination = document.createElement('div');
    pagination.className = 'custom-pagination';
    table.parentElement.appendChild(pagination);

    function render() {
      const start = (currentPage - 1) * rowsPerPage;
      const end = start + rowsPerPage;
      rows.forEach(r => r.style.display = 'none');
      filteredRows.slice(start, end).forEach(r => r.style.display = '');
      renderPagination();
    }

    function renderPagination() {
      pagination.innerHTML = '';
      const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
      if (totalPages <= 1) return;
      for (let i = 1; i <= totalPages; i++) {
        const a = document.createElement('a');
        a.href = '#'; a.textContent = i; a.className = i === currentPage ? 'active' : '';
        a.addEventListener('click', e => { e.preventDefault(); currentPage = i; render(); });
        pagination.appendChild(a);
      }
    }

    searchBox.addEventListener('input', function () {
      const q = this.value.toLowerCase();
      filteredRows = rows.filter(r => r.textContent.toLowerCase().includes(q));
      currentPage = 1; render();
    });
    render();

    // Export buttons
    const exportContainer = document.createElement('div');
    exportContainer.className = 'vjs-export-container';
    const copyBtn = document.createElement('button'); copyBtn.textContent = 'Copy';
    const csvBtn = document.createElement('button'); csvBtn.textContent = 'CSV';
    const tsvBtn = document.createElement('button'); tsvBtn.textContent = 'TSV';
    const xlsxBtn = document.createElement('button'); xlsxBtn.textContent = 'Excel';
    const pdfBtn = document.createElement('button'); pdfBtn.textContent = 'PDF';
    [copyBtn, csvBtn, tsvBtn, xlsxBtn, pdfBtn].forEach(b => exportContainer.appendChild(b));
    table.parentElement.insertBefore(exportContainer, table);

    copyBtn.addEventListener('click', () => {
      const data = filteredRows.map(r => Array.from(r.children).map(c => c.textContent).join('\t')).join('\n');
      navigator.clipboard.writeText(data).then(() => showToast('Copied to clipboard', 'success'));
    });

    function exportText(delim, fname) {
      const header = Array.from(table.querySelectorAll('thead th')).map(th => `"${th.textContent}"`).join(delim);
      const body = filteredRows.map(r => Array.from(r.children).map(c => `"${c.textContent}"`).join(delim)).join('\n');
      const blob = new Blob([header + '\n' + body], { type: 'text/plain' });
      const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = fname; a.click(); URL.revokeObjectURL(a.href);
    }
    csvBtn.addEventListener('click', () => exportText(',', 'table_export.csv'));
    tsvBtn.addEventListener('click', () => exportText('\t', 'table_export.tsv'));

    xlsxBtn.addEventListener('click', () => {
      const ws = XLSX.utils.json_to_sheet(filteredRows.map(r => {
        const obj = {};
        table.querySelectorAll('thead th').forEach((th, i) => { obj[th.textContent] = r.children[i]?.textContent; });
        return obj;
      }));
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
      XLSX.writeFile(wb, 'table_export.xlsx');
    });

    pdfBtn.addEventListener('click', () => {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF('l', 'pt', 'a4');
      const data = [Array.from(table.querySelectorAll('thead th')).map(th => th.textContent)];
      filteredRows.forEach(r => data.push(Array.from(r.children).map(c => c.textContent)));
      doc.autoTable({ head: [data[0]], body: data.slice(1), theme: 'grid', startY: 20 });
      doc.save('table_export.pdf');
    });
  }

  // Initialize tables
  setupTable('#generatedReportsTable', 10);
  setupTable('#uploadedReportsTable', 10);

  // ===== AJAX Form Submission =====
  function ajaxForm(formSelector, url) {
    const form = document.querySelector(formSelector);
    if (!form) return;
    form.addEventListener('submit', e => {
      e.preventDefault();
      const btn = form.querySelector("button[type='submit']");
      const origText = btn.textContent; btn.disabled = true; btn.textContent = 'Submitting...';
      const formData = new FormData(form);
      fetch(url, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => showToast(data.message, data.success ? 'success' : 'error'))
        .catch(() => showToast('Request failed', 'error'))
        .finally(() => { btn.disabled = false; btn.textContent = origText; });
    });
  }

  ajaxForm('#fieldDataForm', 'ajax/add_field_data.php');
  ajaxForm('#labDataForm', 'ajax/add_lab_data.php');
  ajaxForm('#uploadReportForm', 'controllers/upload_report.php');

  // ===== Modal View =====
  const modal = document.getElementById('reportModal');
  const modalBody = document.getElementById('modalBody');
  document.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      modal.style.display = 'block';
      modalBody.innerHTML = '<p style="padding:10px;">Loading...</p>';
      const id = btn.dataset.id;
      const type = btn.dataset.type;
      fetch(`/controllers/view_report.php?type=${type}&id=${id}`)
        .then(res => res.text())
        .then(html => modalBody.innerHTML = html)
        .catch(() => modalBody.innerHTML = '<p style="color:red;">Failed to load report details.</p>');
    });
  });
  modal?.querySelector('.close')?.addEventListener('click', () => modal.style.display = 'none');
  window.addEventListener('click', e => { if (e.target === modal) modal.style.display = 'none'; });

});
