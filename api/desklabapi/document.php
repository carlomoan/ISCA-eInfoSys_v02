document.addEventListener('DOMContentLoaded', function () {

    const wizardForm = document.getElementById('fieldWizardForm');
    const confirmationBody = document.getElementById('confirmationBody');
    const submitBtn = document.getElementById('submitBtn');
    const printBtn = document.getElementById('printBtn');

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

    // Generate confirmation table dynamically
    window.fillConfirmation = function(dataObj){
        let html = '';

        function getValue(key){
            if (!dataObj.hasOwnProperty(key) || dataObj[key] === null || dataObj[key] === undefined) {
                return '0'; // default kwa numeric fields
            }
            const val = dataObj[key].toString().trim();
            return val === '' ? '0' : val;
        }

        // ===== Household Information =====
        html += ` <div class="form-header">
                      <h2>ISCA PHASE - III, 2025</h2>
                      <h3>Entomology Field & Lab Data Confirmation</h3>
                      <div class="meta">
                        <span>Date: ${getValue('field_coll_date')}</span> |
                        <span>Round: ${getValue('round')}</span>
                      </div>
                  </div>`;

        html += `<table class="info-table">
                    <tr><th colspan="4">Household Information</th></tr>
                    <tr><td>Household Name</td><td>${getValue('hhname') || 'NOT YET'}</td>
                        <td>Household Code</td><td>${getValue('hhcode') || 'NOT YET'}</td></tr>
                    <tr><td>Cluster ID</td><td>${getValue('clstid') || 'NOT YET'}</td>
                        <td>Cluster Name</td><td>${getValue('clstname') || 'NOT YET'}</td></tr>
                    <tr><td>Collection Date</td><td>${getValue('field_coll_date') || 'NOT YET'}</td>
                        <td>Form Title</td><td>${getValue('ento_fld_frm_title') || 'NOT YET'}</td></tr>
                    <tr><td>Device ID</td><td>${getValue('deviceid') || 'NOT YET'}</td>
                        <td>Recorded by</td><td>${getValue('fldrecname') || 'NOT YET'}</td></tr>
                 </table>`;

        // ===== Field Collection - Anopheles gambiae =====
        html += `<table class="info-table">
                    <tr><th colspan="6">Field Collection - Anopheles gambiae</th></tr>
                    <tr><td>Male</td><td>${getValue('male_ag')}</td>
                        <td>Female</td><td>${getValue('female_ag')}</td>
                        <td>Fed</td><td>${getValue('fed_ag')}</td></tr>
                    <tr><td>Unfed</td><td>${getValue('unfed_ag')}</td>
                        <td>Gravid</td><td>${getValue('gravid_ag')}</td>
                        <td>Semi-Gravid</td><td>${getValue('semi_gravid_ag')}</td></tr>
                 </table>`;

        // ===== Lab Results - Anopheles funestus =====
        html += `<table class="info-table">
                    <tr><th colspan="6">Laboratory Results - Anopheles funestus</th></tr>
                    <tr><td>Male</td><td>${getValue('male_af')}</td>
                        <td>Female</td><td>${getValue('female_af')}</td>
                        <td>Fed</td><td>${getValue('fed_af')}</td></tr>
                    <tr><td>Unfed</td><td>${getValue('unfed_af')}</td>
                        <td>Gravid</td><td>${getValue('gravid_af')}</td>
                        <td>Semi-Gravid</td><td>${getValue('semi_gravid_af')}</td></tr>
                 </table>`;

        // ===== Other Species - OAN =====
        html += `<table class="info-table">
                    <tr><th colspan="6">Other Species - OAN</th></tr>
                    <tr><td>Male</td><td>${getValue('male_oan')}</td>
                        <td>Female</td><td>${getValue('female_oan')}</td>
                        <td>Fed</td><td>${getValue('fed_oan')}</td></tr>
                    <tr><td>Unfed</td><td>${getValue('unfed_oan')}</td>
                        <td>Gravid</td><td>${getValue('gravid_oan')}</td>
                        <td>Semi-Gravid</td><td>${getValue('semi_gravid_oan')}</td></tr>
                 </table>`;

        // ===== Other Species - Culex =====
        html += `<table class="info-table">
                    <tr><th colspan="6">Other Species - Culex</th></tr>
                    <tr><td>Male</td><td>${getValue('male_culex')}</td>
                        <td>Female</td><td>${getValue('female_culex')}</td>
                        <td>Fed</td><td>${getValue('fed_culex')}</td></tr>
                    <tr><td>Unfed</td><td>${getValue('unfed_culex')}</td>
                        <td>Gravid</td><td>${getValue('gravid_culex')}</td>
                        <td>Semi-Gravid</td><td>${getValue('semi_gravid_culex')}</td></tr>
                 </table>`;

        // ===== Other Culex =====
        html += `<table class="info-table">
                    <tr><th colspan="4">Other Culex</th></tr>
                    <tr><td>Male</td><td>${getValue('male_other_culex')}</td>
                        <td>Female</td><td>${getValue('female_other_culex')}</td></tr>
                 </table>`;

        // ===== Aedes =====
        html += `<table class="info-table">
                    <tr><th colspan="4">Aedes</th></tr>
                    <tr><td>Male</td><td>${getValue('male_aedes')}</td>
                        <td>Female</td><td>${getValue('female_aedes')}</td></tr>
                 </table>`;

        // ===== Metadata =====
        html += `<table class="info-table">
                    <tr><th colspan="4">Metadata</th></tr>
                    <tr><td>Start</td><td>${getValue('start') || 'NOT YET'}</td>
                        <td>End</td><td>${getValue('end') || 'NOT YET'}</td></tr>
                    <tr><td>User Role</td><td>${getValue('user_role') || 'NOT YET'}</td>
                        <td>Instance ID</td><td>${getValue('instanceID') || 'NOT YET'}</td></tr>
                 </table>`;

        confirmationBody.innerHTML = html;

        // Highlight NOT YET
        confirmationBody.querySelectorAll('td').forEach(td=>{
            if(td.textContent==='NOT YET' || td.textContent==='0'){
                td.style.color='red';
                td.style.fontWeight='bold';
            }
        });
    }

    // Submit confirmation to API using showToast
    submitBtn?.addEventListener('click', async ()=>{
        if(!wizardForm) return;
        const formData = new FormData(wizardForm);
        const dataObj = {};
        formData.forEach((v,k)=> dataObj[k]=v);

        try{
            const res = await fetch('/ISCA-eInfoSys_v02/api/deskfieldapi/add_field_data_api.php',{
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body: JSON.stringify(dataObj)
            });
            const resp = await res.json();
            if(resp.success){
                showToast("Data submitted successfully!", "success");
                wizardForm.reset();
            } else {
                showToast(resp.message || "Error submitting data.", "error");
            }
        } catch(err){
            console.error(err);
            showToast("Server error while submitting data.", "error");
        }
    });

});
