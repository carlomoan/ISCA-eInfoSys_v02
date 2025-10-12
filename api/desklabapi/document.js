document.addEventListener('DOMContentLoaded', function () {

    const wizardForm = document.getElementById('fieldWizardForm');
    const confirmationBody = document.getElementById('confirmationBody');
    const submitBtn = document.getElementById('submitBtn');
    const printBtn = document.getElementById('printBtn');
    const exportPdfBtn = document.getElementById('export-pdf'); // Add this button in your HTML

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

    // ===== Fill confirmation table dynamically =====
    window.fillConfirmation = function (dataObj) {
        let html = '';

        function getValue(key) {
            // Ensure key exists, fallback = 0 (numeric fields) or NOT YET (strings)
            if (dataObj.hasOwnProperty(key) && dataObj[key] !== null && dataObj[key] !== undefined) {
                return dataObj[key].toString().trim() !== '' ? dataObj[key] : '0';
            }
            return '0';
        }

        console.log("Confirmation Data:", dataObj); // debug

        // Header
        html += `<div class="form-header">
                    <h2>ISCA PHASE - III, 2025</h2>
                    <h3>Entomology Field & Lab Data Confirmation</h3>
                    <div class="meta">
                        <span>Date: ${getValue('field_coll_date')}</span> |
                        <span>Round: ${getValue('round')}</span>
                    </div>
                 </div>`;

        // Household Information
        html += `<table class="info-table">
                    <tr><th colspan="4">Household Information</th></tr>
                    <tr><td>Household Name</td><td>${getValue('hhname')}</td>
                        <td>Household Code</td><td>${getValue('hhcode')}</td></tr>
                    <tr><td>Cluster ID</td><td>${getValue('clstid')}</td>
                        <td>Cluster Name</td><td>${getValue('clstname')}</td></tr>
                    <tr><td>Collection Date</td><td>${getValue('field_coll_date')}</td>
                        <td>Form Title</td><td>${getValue('ento_fld_frm_title')}</td></tr>
                    <tr><td>Device ID</td><td>${getValue('deviceid')}</td>
                        <td>Recorded by</td><td>${getValue('fldrecname')}</td></tr>
                 </table>`;

        // Field and Lab Data Sections
        const sections = [
            {
                title: 'Field Collection - Anopheles gambiae',
                keys: ['male_ag', 'female_ag', 'fed_ag', 'unfed_ag', 'gravid_ag', 'semi_gravid_ag']
            },
            {
                title: 'Laboratory Results - Anopheles funestus',
                keys: ['male_af', 'female_af', 'fed_af', 'unfed_af', 'gravid_af', 'semi_gravid_af']
            },
            {
                title: 'Other Species - OAN',
                keys: ['male_oan', 'female_oan', 'fed_oan', 'unfed_oan', 'gravid_oan', 'semi_gravid_oan']
            },
            {
                title: 'Other Species - Culex',
                keys: ['male_culex', 'female_culex', 'fed_culex', 'unfed_culex', 'gravid_culex', 'semi_gravid_culex']
            },
            { title: 'Other Culex', keys: ['male_other_culex', 'female_other_culex'] },
            { title: 'Aedes', keys: ['male_aedes', 'female_aedes'] }
        ];

        sections.forEach(sec => {
            html += `<table class="info-table"><tr><th colspan="${sec.keys.length}">${sec.title}</th></tr><tr>`;
            sec.keys.forEach(k => {
                html += `<td>${getValue(k)}</td>`;
            });
            html += `</tr></table>`;
        });

        // Metadata
        html += `<table class="info-table">
                    <tr><th colspan="4">Metadata</th></tr>
                    <tr><td>Start</td><td>${getValue('start')}</td>
                        <td>End</td><td>${getValue('end')}</td></tr>
                    <tr><td>User Role</td><td>${getValue('user_role')}</td>
                        <td>Instance ID</td><td>${getValue('instanceID')}</td></tr>
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
            } else {
                showToast(resp.message || "Error submitting data.", "error");
            }
        } catch (err) {
            console.error(err);
            showToast("Server error while submitting data.", "error");
        }
    });

    // ===== Print confirmation (simple) =====
    printBtn?.addEventListener('click', () => {
        if (!confirmationBody) return;
        const w = window.open('');
        w.document.write('<html><head><title>Confirmation</title></head><body>');
        w.document.write(confirmationBody.innerHTML);
        w.document.write('</body></html>');
        w.print();
        w.close();
    });

    // ===== Export PDF with icons and footer =====
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
        const pdf = new jsPDF('l', 'pt', 'a4');
        let yOffset = 60;
        const pageWidth = pdf.internal.pageSize.getWidth();

        // Icons
        const leftIcon = await getBase64Image(BASE_URL + '/assets/images/nimr_logo_gen.png');
        const rightIcon = await getBase64Image(BASE_URL + '/assets/images/Survey_img_1_-Upd.png');
        pdf.addImage(leftIcon, 'PNG', 40, 15, 24, 24);
        pdf.addImage(rightIcon, 'PNG', pageWidth - 64, 15, 24, 24);

        // Header text
        pdf.setFontSize(14);
        pdf.text('ISCA Phase III - Field Confirmation', pageWidth / 2, 30, { align: 'center' });

        // Table
        const tableCanvas = await html2canvas(confirmationBody, { scale: 2 });
        const imgData = tableCanvas.toDataURL('image/png');
        const imgWidth = pageWidth - 80;
        const imgHeight = tableCanvas.height * imgWidth / tableCanvas.width;
        pdf.addImage(imgData, 'PNG', 40, yOffset, imgWidth, imgHeight);
        yOffset += imgHeight + 30;

        // Footer
        const fullName = document.querySelector('input[name="fldrecname"]')?.value || 'Unknown';
        const date = new Date();
        const ts = `${date.getFullYear()}-${date.getMonth() + 1}-${date.getDate()} ${date.getHours()}:${date.getMinutes()}`;
        pdf.setFontSize(10);
        pdf.text(`Printed by: ${fullName}`, 40, pdf.internal.pageSize.getHeight() - 30);
        pdf.text(`Date: ${ts}`, pageWidth - 40, pdf.internal.pageSize.getHeight() - 30, { align: 'right' });

        pdf.save(`field_confirmation_${ts}.pdf`);
    });

});
