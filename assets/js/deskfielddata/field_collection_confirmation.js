document.addEventListener('DOMContentLoaded', function () {

    const wizardForm = document.getElementById('fieldWizardForm');
    const confirmationBody = document.getElementById('confirmationBody');
    const submitBtn = document.getElementById('submitBtn');
    const printBtn = document.getElementById('printBtn');
    const exportPdfBtn = document.getElementById('export-pdf');

    // ===== Toast System =====
    let toastContainer = document.getElementById("toast-container");
    if (!toastContainer) {
        toastContainer = document.createElement("div");
        toastContainer.id = "toast-container";
        document.body.appendChild(toastContainer);
    }

    function showToast(message, type = "info", duration = 4000) {
        const toast = document.createElement("div");
        toast.className = `toast ${type}`;
        toast.innerText = message;
        toastContainer.appendChild(toast);

        setTimeout(() => toast.classList.add("visible"), 50);
        setTimeout(() => {
            toast.classList.remove("visible");
            setTimeout(() => toast.remove(), 500);
        }, duration);
    }

    // ===== Helper for N/A =====
    function naValue(val) {
        if (val === null || val === undefined) return `<span class="na-value">N/A</span>`;
        val = val.toString().trim();
        if (val === '' || val === '0') {
            return `<span class="na-value">N/A</span>`;
        }
        return val;
    }

    // ===== Fill confirmation table dynamically =====
    window.fillConfirmation = function (dataObj) {
        let html = '';

        console.log("Confirmation Data:", dataObj);

        // ===== Household Info =====
        html += ` <div class="form-header">
                      <h2>FIELD COLLECTION AND LABORATORY DATA</h2>
                      <h3>Entomology Field and Laboratory Sorting Data - ISCA - PHASE - III, 2025</h3>
                      <div class="meta">
                        <span>Date: ${naValue(dataObj['field_coll_date'])}</span> |
                        <span>Round: ${naValue(dataObj['round'])}</span>
                      </div>
                  </div>`;

        html += `<table class="info-table">
                    <tr><th colspan="4">Household Information</th></tr>
                    <tr><td>Household Name</td><td>${naValue(dataObj['hhname'])}</td>
                        <td>Household Code</td><td>${naValue(dataObj['hhcode'])}</td></tr>
                    <tr><td>Cluster ID</td><td>${naValue(dataObj['clstid'])}</td>
                        <td>Cluster Name</td><td>${naValue(dataObj['clstname'])}</td></tr>
                    <tr><td>Collection Date</td><td>${naValue(dataObj['field_coll_date'])}</td>
                        <td>Form Title</td><td>${naValue(dataObj['ento_fld_frm_title'])}</td></tr>
                    <tr><td>Device ID</td><td>${naValue(dataObj['deviceid'])}</td>
                        <td>Recorded by</td><td>${naValue(dataObj['fldrecname'])}</td></tr>
                 </table>`;

        // ===== Field Collection - Anopheles gambiae =====
        html += `<table class="info-table">
                    <tr><th colspan="6">Field Collection - Anopheles gambiae</th></tr>
                    <tr><td>Male</td><td>${naValue(dataObj['male_ag'])}</td>
                        <td>Female</td><td>${naValue(dataObj['female_ag'])}</td>
                        <td>Fed</td><td>${naValue(dataObj['fed_ag'])}</td></tr>
                    <tr><td>Unfed</td><td>${naValue(dataObj['unfed_ag'])}</td>
                        <td>Gravid</td><td>${naValue(dataObj['gravid_ag'])}</td>
                        <td>Semi-Gravid</td><td>${naValue(dataObj['semi_gravid_ag'])}</td></tr>
                 </table>`;

        // ===== Laboratory Results - Anopheles funestus =====
        html += `<table class="info-table">
                    <tr><th colspan="6">Laboratory Results - Anopheles funestus</th></tr>
                    <tr><td>Male</td><td>${naValue(dataObj['male_af'])}</td>
                        <td>Female</td><td>${naValue(dataObj['female_af'])}</td>
                        <td>Fed</td><td>${naValue(dataObj['fed_af'])}</td></tr>
                    <tr><td>Unfed</td><td>${naValue(dataObj['unfed_af'])}</td>
                        <td>Gravid</td><td>${naValue(dataObj['gravid_af'])}</td>
                        <td>Semi-Gravid</td><td>${naValue(dataObj['semi_gravid_af'])}</td></tr>
                 </table>`;

        // ===== Other Species - OAN =====
        html += `<table class="info-table">
                    <tr><th colspan="6">Other Species - OAN</th></tr>
                    <tr><td>Male</td><td>${naValue(dataObj['male_oan'])}</td>
                        <td>Female</td><td>${naValue(dataObj['female_oan'])}</td>
                        <td>Fed</td><td>${naValue(dataObj['fed_oan'])}</td></tr>
                    <tr><td>Unfed</td><td>${naValue(dataObj['unfed_oan'])}</td>
                        <td>Gravid</td><td>${naValue(dataObj['gravid_oan'])}</td>
                        <td>Semi-Gravid</td><td>${naValue(dataObj['semi_gravid_oan'])}</td></tr>
                 </table>`;

        // ===== Other Species - Culex =====
        html += `<table class="info-table">
                    <tr><th colspan="6">Other Species - Culex</th></tr>
                    <tr><td>Male</td><td>${naValue(dataObj['male_culex'])}</td>
                        <td>Female</td><td>${naValue(dataObj['female_culex'])}</td>
                        <td>Fed</td><td>${naValue(dataObj['fed_culex'])}</td></tr>
                    <tr><td>Unfed</td><td>${naValue(dataObj['unfed_culex'])}</td>
                        <td>Gravid</td><td>${naValue(dataObj['gravid_culex'])}</td>
                        <td>Semi-Gravid</td><td>${naValue(dataObj['semi_gravid_culex'])}</td></tr>
                 </table>`;

        // ===== Other Culex =====
        html += `<table class="info-table">
                    <tr><th colspan="4">Other Culex</th></tr>
                    <tr><td>Male</td><td>${naValue(dataObj['male_other_culex'])}</td>
                        <td>Female</td><td>${naValue(dataObj['female_other_culex'])}</td></tr>
                 </table>`;

        // ===== Aedes =====
        html += `<table class="info-table">
                    <tr><th colspan="4">Aedes</th></tr>
                    <tr><td>Male</td><td>${naValue(dataObj['male_aedes'])}</td>
                        <td>Female</td><td>${naValue(dataObj['female_aedes'])}</td></tr>
                 </table>`;

        // ===== Metadata =====
        html += `<table class="info-table">
                    <tr><th colspan="4">Metadata</th></tr>
                    <tr><td>Start</td><td>${naValue(dataObj['start'])}</td>
                        <td>End</td><td>${naValue(dataObj['end'])}</td></tr>
                    <tr><td>User Role</td><td>${naValue(dataObj['user_role'])}</td>
                        <td>Instance ID</td><td>${naValue(dataObj['instanceID'])}</td></tr>
                 </table>`;

        confirmationBody.innerHTML = html;
    }

    // ===== Submit confirmation =====
submitBtn?.addEventListener('click', async () => {
    if (!wizardForm) return;
    const formData = new FormData(wizardForm);
    const dataObj = {};
    formData.forEach((v, k) => dataObj[k] = v);

    try {
        const res = await fetch(BASE_URL + '/api/deskfieldapi/add_field_data_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dataObj)
        });
        const resp = await res.json();
        if (resp.success) {
            showToast("Data submitted successfully!", "success");
            wizardForm.reset();

            // ===== Switch to desk_compare tab =====
            const targetTab = document.querySelector('[data-tab="desk_compare"]');
            if (targetTab) {
                targetTab.click();

                // ===== Auto-refresh desk_compare data =====
                if (typeof loadDeskCompareData === "function") {
                    loadDeskCompareData();
                } else {
                    // fallback
                    const container = document.getElementById('views-desk_field_table-container');
                    if (container) {
                        container.innerHTML = "<p class='loading'>Refreshing data...</p>";
                    }
                }
            }
        } else {
            showToast(resp.message || "Error submitting data.", "error");
        }
    } catch (err) {
        console.error(err);
        showToast("Server error while submitting data.", "error");
    }
});


// ===== Print confirmation =====
printBtn?.addEventListener('click', () => {
    if (!confirmationBody) return;
    const w = window.open('');
    w.document.write('<html><head><title>Confirmation</title></head><body>');
    w.document.write(confirmationBody.innerHTML);
    w.document.write('</body></html>');
    w.print();
    w.close();
});

// ===== Export PDF =====
async function getBase64Image(url) {
    return new Promise((resolve) => {
        const img = new Image();
        img.crossOrigin = 'Anonymous';
        img.src = url;
        img.onload = function () {
            const canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;
            canvas.getContext('2d').drawImage(img, 0, 0);
            resolve(canvas.toDataURL('image/png'));
        };
    });
}

exportPdfBtn?.addEventListener('click', async (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (!confirmationBody) return;

    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF('landscape', 'pt', 'a4');
    const pageWidth = pdf.internal.pageSize.getWidth();
    const pageHeight = pdf.internal.pageSize.getHeight();
    let yOffset = 60;

    // ==== Logos ====
    const leftIcon = await getBase64Image(BASE_URL + '/assets/images/nimr_logo_gen.png');
    const rightIcon = await getBase64Image(BASE_URL + '/assets/images/Survey_img_1_-Upd.png');
    pdf.addImage(leftIcon, 'PNG', 40, 15, 24, 24);
    pdf.addImage(rightIcon, 'PNG', pageWidth - 64, 15, 24, 24);

    // ==== Title ====
    pdf.setFontSize(14);
    pdf.text('NATIONAL INSTITUTE FOR MEDICAL RESEARCH', pageWidth / 2, 30, { align: 'center' });

    // ==== Capture Table ====
    const tableCanvas = await html2canvas(confirmationBody, { scale: 1.5 });
    const imgData = tableCanvas.toDataURL('image/png');
    const imgWidth = pageWidth - 80;
    const imgHeight = (tableCanvas.height * imgWidth) / tableCanvas.width;

    let heightLeft = imgHeight;
    let position = yOffset;

    // Page breaking
    pdf.addImage(imgData, 'PNG', 40, position, imgWidth, imgHeight);
    heightLeft -= pageHeight - yOffset - 40;

    while (heightLeft > 0) {
        pdf.addPage();
        position = 40;
        pdf.addImage(imgData, 'PNG', 40, position - (imgHeight - heightLeft), imgWidth, imgHeight);
        heightLeft -= pageHeight - 80;
    }

    // ==== Footer ====
     
        // Footer
        const fullName = document.querySelector('input[name="fldrecname"]')?.value || 'Unknown';
        const dateStr = new Date().toLocaleString();
        pdf.setFontSize(10);
        pdf.text(`Prapared by: ${fullName}`, 40, pdf.internal.pageSize.getHeight() - 40);
        pdf.text(`Signature: ______________________`, pageWidth / 2, pdf.internal.pageSize.getHeight() - 40, { align: 'center' });
        pdf.text(`Date: ${dateStr}`, pageWidth - 40, pdf.internal.pageSize.getHeight() - 40, { align: 'right' });

        pdf.save(`field_confirmation_${Date.now()}.pdf`);
});



});
