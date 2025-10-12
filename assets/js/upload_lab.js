document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('upload-lab-form');
  const feedback = document.getElementById('lab-upload-feedback');
  const summary = document.getElementById('lab-upload-summary');
  const preview = document.getElementById('lab-upload-preview');
  const missingContainer = document.getElementById('lab-missing-households-container');

  // Toast setup
  let toast = document.getElementById('toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'toast';
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.padding = '10px 20px';
    toast.style.backgroundColor = '#333';
    toast.style.color = '#fff';
    toast.style.borderRadius = '4px';
    toast.style.opacity = '0';
    toast.style.transition = 'opacity 0.5s ease';
    toast.style.zIndex = '9999';
    document.body.appendChild(toast);
  }

  function showToast(message, isError = false) {
    toast.textContent = message;
    toast.style.backgroundColor = isError ? '#dc3545' : '#28a745';
    toast.style.opacity = '1';
    setTimeout(() => {
      toast.style.opacity = '0';
    }, 4000);
  }

  function renderPreviewTable(data) {
    if (!data || !data.length) return '';

    let html = '<h3>📄 Preview of Collected Lab Data</h3>';
    html += '<table style="border-collapse: collapse; width: 100%;"><thead><tr>';

    Object.keys(data[0]).forEach(key => {
      html += `<th style="border:1px solid #ccc; padding:6px;">${key}</th>`;
    });

    html += '</tr></thead><tbody>';

    data.slice(0, 60).forEach(row => {
      html += '<tr>';
      Object.values(row).forEach(val => {
        html += `<td style="border:1px solid #ccc; padding:6px;">${val}</td>`;
      });
      html += '</tr>';
    });

    html += '</tbody></table>';
    return html;
  }

  function renderMissingTable(missing) {
    if (!missing || !missing.length) return '';

    let html = `
      <h3 style="color:red;">🚫 Missing Households</h3>
      <table style="border-collapse: collapse; width: 100%;">
        <thead>
          <tr>
            <th style="border:1px solid #ccc; padding:6px;">HH Code</th>
            <th style="border:1px solid #ccc; padding:6px;">Cluster name</th>
            <th style="border:1px solid #ccc; padding:6px;">Lab Sorter</th>
            <th style="border:1px solid #ccc; padding:6px;">Mobile Number</th>
          </tr>
        </thead>
        <tbody>
    `;

    missing.forEach(hh => {
      html += `
        <tr>
          <td style="border:1px solid #ccc; padding:6px;">${hh.hhcode || ''}</td>
          <td style="border:1px solid #ccc; padding:6px;">${hh.cluster_name || ''}</td>
          <td style="border:1px solid #ccc; padding:6px;">${hh.field_recorder || ''}</td>
          <td style="border:1px solid #ccc; padding:6px;">${hh.phone || ''}</td>
        </tr>
      `;
    });

    html += `</tbody></table>`;

    return html;
  }

  function updateUploadUI(summaryHTML, missingHTML, previewHTML, hasMissing) {
    summary.innerHTML = summaryHTML;

    if (hasMissing && missingHTML.trim() !== '') {
      missingContainer.innerHTML = missingHTML;
    } else {
      missingContainer.innerHTML = `
    <div style="margin-top:20px; text-align:right;">
      <button id="next-to-merge-btn" style="
        background-color: #007bff;
        color: white;
        border: none;
        padding: 8px 14px;
        border-radius: 4px;
        cursor: pointer;
      ">
        Next to Merge →
      </button>
    </div>
  `;

  const nextBtn = document.getElementById('next-to-merge-btn');
  nextBtn.addEventListener('click', () => {
    // Badilisha hapa kulingana na logic yako ya ku-switch tab au page
    // Mfano: ku-switch tab kwenda merge tab
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    const mergeTabBtn = document.querySelector('.tab-btn[data-tab="merge"]');
    if (mergeTabBtn) mergeTabBtn.classList.add('active');

    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    const mergeTabContent = document.querySelector('.tab-content[data-tab="merge"]');
    if (mergeTabContent) mergeTabContent.classList.add('active');
  });
}

    preview.innerHTML = previewHTML;
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();

    feedback.textContent = '⏳ Uploading and validating...';
    summary.innerHTML = '';
    preview.innerHTML = '';
    missingContainer.innerHTML = '';

    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;

    const formData = new FormData(form);

    fetch(form.action, {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        console.log('Upload response:', data); // For debugging

        if (data.success) {
          const uploaded_count = data.uploaded_count || 0;
          const expected_count = data.expected_count || 0;
          const missing_households = data.missing_households || [];
          const previewData = data.preview || [];
          const missing_count = expected_count - uploaded_count;
          const percentage = expected_count > 0 ? Math.round((uploaded_count / expected_count) * 100) : 0;

          const summaryHTML = `
            <div class="upload-summary" style="border:1px solid #ccc; padding:10px; border-radius:6px;">
              <h3>Upload Summary</h3>
              <table style="width: 100%; border-collapse: collapse;">
                <tr><td><strong>Expected Forms:</strong></td><td>${expected_count}</td></tr>
                <tr><td><strong>Received Forms:</strong></td><td>${uploaded_count} (${percentage}%)</td></tr>
                <tr><td><strong>Forms with Issues:</strong></td><td>${missing_count}</td></tr>
              </table>
              <div style="margin-top:10px; background:#eee; border-radius:4px; overflow:hidden;">
                <div style="width: ${percentage}%; background:#28a745; height: 20px;"></div>
              </div>
              <button id="print-summary-btn" class="print-btn" style="margin-top:10px;">Print Summary</button>
            </div>
          `;

          const missingHTML = renderMissingTable(missing_households);

          const previewHTML = renderPreviewTable(previewData);

          updateUploadUI(summaryHTML, missingHTML, previewHTML, missing_households.length > 0);

          showToast(`✅ ${data.message}`, false);
          feedback.textContent = `✅ ${data.message}`;
        } else {
          showToast(`❌ ${data.message}`, true);
          feedback.textContent = `❌ ${data.message}`;
        }
      })
      .catch(err => {
        showToast('❌ Upload failed. Please try again.', true);
        feedback.textContent = '❌ Upload failed. Please try again.';
        console.error('Upload error:', err);
      })
      .finally(() => {
        submitBtn.disabled = false;
      });
  });

  // Print buttons listener
  document.addEventListener('click', (e) => {
    if (e.target && (e.target.id === 'print-summary-btn' || e.target.id === 'print-missing-btn')) {
      e.preventDefault();

      let contentToPrint = '';
      if (e.target.id === 'print-summary-btn') {
        contentToPrint = document.querySelector('.upload-summary').outerHTML;
      } else if (e.target.id === 'print-missing-btn') {
        contentToPrint = document.querySelector('#lab-missing-households-container').innerHTML;
      }

      if (!contentToPrint) {
        alert('Hakuna kitu cha kuchapisha!');
        return;
      }

      const printWindow = window.open('', '', 'height=600,width=800');
      printWindow.document.write('<html><head><title>Print Preview</title>');
      printWindow.document.write('<style>');
      printWindow.document.write(`
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        h3 { margin-bottom: 10px; }
      `);
      printWindow.document.write('</style>');
      printWindow.document.write('</head><body>');
      printWindow.document.write(contentToPrint);
      printWindow.document.write('</body></html>');
      printWindow.document.close();
      printWindow.focus();
      printWindow.print();
      printWindow.close();
    }
  });
});
