document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('compare-merge-form');
    const resultDiv = document.getElementById('result-compare');
    const toast = createToast();

    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        resultDiv.innerHTML = '';
        showToast('⏳ Merging data, please wait...', false);

        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin'
            });

            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

            const data = await response.json();

            if(data.success){
                showToast(`✅ ${data.message}`, false);
                resultDiv.innerHTML = renderPreviewTable(data.preview, data.mismatched_count, data.matched_count);
            } else {
                showToast(`❌ ${data.message}`, true);
                resultDiv.innerHTML = `<p style="color:red;">${data.message}</p>`;
            }

        } catch(err){
            console.error('Error merging data:', err);
            showToast('❌ Merge failed. Check console.', true);
            resultDiv.innerHTML = '<p style="color:red;">Merge failed. Please try again.</p>';
        } finally {
            submitBtn.disabled = false;
        }
    });

    function createToast() {
        let toastElem = document.getElementById('toast');
        if (!toastElem) {
            toastElem = document.createElement('div');
            toastElem.id = 'toast';
            Object.assign(toastElem.style, {
                position: 'fixed',
                top: '20px',
                right: '20px',
                padding: '10px 20px',
                backgroundColor: '#333',
                color: '#fff',
                borderRadius: '4px',
                opacity: '0',
                transition: 'opacity 0.5s ease',
                zIndex: '9999',
            });
            document.body.appendChild(toastElem);
        }
        return toastElem;
    }

    function showToast(message, isError=false){
        toast.textContent = message;
        toast.style.backgroundColor = isError ? '#dc3545' : '#28a745';
        toast.style.opacity = '1';
        setTimeout(()=>{ toast.style.opacity='0'; },4000);
    }

    function renderPreviewTable(data=[], mismatchedCount=0, matchedCount=0){
        if(!data.length) return '<p>No data to display.</p>';

        const total = data.length;
        const matchedPercent = total ? Math.round((matchedCount/total)*100) : 0;
        const mismatchedPercent = total ? Math.round((mismatchedCount/total)*100) : 0;

        let html = `
            <div style="border:1px solid #ccc; padding:10px; border-radius:6px; margin-bottom:10px;">
                <h4>Summary</h4>
                <table style="width:100%; margin-bottom:10px;">
                    <tr><td><strong>Total HH:</strong></td><td>${total}</td></tr>
                    <tr><td><strong>Matched:</strong></td><td>${matchedCount} (${matchedPercent}%)</td></tr>
                    <tr><td><strong>Mismatched:</strong></td><td>${mismatchedCount} (${mismatchedPercent}%)</td></tr>
                </table>
                <div style="display:flex; height:20px; background:#eee; border-radius:4px; overflow:hidden;">
                    <div style="width:${matchedPercent}%; background:#28a745;"></div>
                    <div style="width:${mismatchedPercent}%; background:#dc3545;"></div>
                </div>
            </div>
        `;

        html += '<table style="width:100%; border-collapse:collapse;">';
        html += '<thead><tr>';
        Object.keys(data[0]).forEach(k=>{
            html += `<th style="border:1px solid #ccc; padding:4px; background:#007bff; color:#fff;">${k}</th>`;
        });
        html += '</tr></thead><tbody>';

        data.forEach(row=>{
            const isMismatch = row.status && row.status.toLowerCase() === 'mismatched';
            html += `<tr style="${isMismatch ? 'background-color:#f8d7da;' : ''}">`;
            Object.values(row).forEach(v=>{
                html += `<td style="border:1px solid #ccc; padding:4px;">${v!==null?v:''}</td>`;
            });
            html += '</tr>';
        });

        html += '</tbody></table>';
        return html;
    }
});

const pdfBtn = document.getElementById('pdf-btn');
if (pdfBtn) {
    pdfBtn.addEventListener('click', () => {
        const doc = new jsPDF();
        const tableEl = document.getElementById('result-compare');
        if(!tableEl) return alert('No data to print');

        // Use jsPDF autoTable plugin if available
        doc.autoTable({ html: tableEl });
        doc.save('merge-result.pdf');
    });
}

