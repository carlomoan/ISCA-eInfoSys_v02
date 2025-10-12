document.addEventListener('DOMContentLoaded', function () {

    // ===== Session User Name =====
    const sessionUserFullName = window.sessionUserFullName || 'Unknown';

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

    // ===== Wizard Elements =====
    const wizardForm = document.getElementById("labWizardForm");
    if (!wizardForm) return;

    const steps = wizardForm.querySelectorAll(".wizard-step");
    const prevBtn = wizardForm.querySelector("#prevBtn");
    const nextBtn = wizardForm.querySelector("#nextBtn");
    const submitBtn = wizardForm.querySelector("#submitBtn");
    const backBtn = wizardForm.querySelector("#backBtn");
    const cancelBtn = wizardForm.querySelector("#cancelBtn");
    const exportPdfBtn = document.getElementById("lab-export-pdf");
    const confirmationBody = document.getElementById('labConfirmationBody');

    let currentStep = 1;
    const totalSteps = steps.length;

    // ===== Auto-fill Step 1 =====
    const startField = wizardForm.querySelector("[name=start]");
    const instanceField = wizardForm.querySelector("[name=instanceID]");
    const srtnameInput = wizardForm.querySelector("[name=srtname]");
    const deviceField = wizardForm.querySelector("[name=deviceid]");
    const labTechInput = wizardForm.querySelector("[name=lab_tech_name]");

    if (instanceField) {
        instanceField.value = `INST-${Date.now()}`;
        instanceField.readOnly = true;
    }

    if (srtnameInput) {
        if (labTechInput && labTechInput.value.trim() !== "") {
            srtnameInput.value = labTechInput.value;
        } else if (window.CURRENT_USER?.name) {
            srtnameInput.value = window.CURRENT_USER.name;
        } else if (window.SESSION_USER?.full_Name) {
            srtnameInput.value = window.SESSION_USER.full_Name;
        } else {
            srtnameInput.value = sessionUserFullName;
        }
        srtnameInput.readOnly = true;
    }

    if (startField) startField.value = new Date().toISOString().slice(0,16);
    if (deviceField) deviceField.value = `DEVICE-${Date.now()}`;

    // ===== Cluster & Household =====
    const clstnameSelect = wizardForm.querySelector("#clstname");
    const clstidInput = wizardForm.querySelector("#clstid");
    const hhcodeSelect = wizardForm.querySelector("#hhcode");
    const hhnameInput = wizardForm.querySelector("#hhname");
    const roundInput = wizardForm.querySelector("#round");

    async function loadClusters() {
        if(!clstnameSelect) return;
        clstnameSelect.innerHTML='<option>Loading...</option>';
        try {
            const res = await fetch(BASE_URL + '/api/get_user_clusters_api.php');
            const data = await res.json();
            clstnameSelect.innerHTML = '';
            if(data.success && Array.isArray(data.data)){
                clstnameSelect.innerHTML = '<option value="">--Select Cluster--</option>';
                data.data.forEach(cl => {
                    const opt = document.createElement("option");
                    opt.value = cl.cluster_name;
                    opt.textContent = cl.cluster_name;
                    opt.dataset.clusterid = cl.cluster_id;
                    clstnameSelect.appendChild(opt);
                });
            } else clstnameSelect.innerHTML = '<option>No clusters assigned</option>';
        } catch(err){
            console.error(err);
            clstnameSelect.innerHTML='<option>Error loading clusters</option>';
            showToast("Error loading clusters.", "error");
        }
    }

    async function loadHouseholds(clusterId) {
        if(!hhcodeSelect) return;
        hhcodeSelect.innerHTML='<option>Loading...</option>';
        hhnameInput.value=''; roundInput.value='';
        if(!clusterId){ hhcodeSelect.innerHTML='<option>--Select Household--</option>'; return; }

        try {
            const res = await fetch(`${BASE_URL}/api/get_cluster_households_api.php?cluster_id=${clusterId}`);
            const data = await res.json();
            hhcodeSelect.innerHTML='<option value="">--Select Household--</option>';
            if(data.success && Array.isArray(data.data)){
                data.data.forEach(hh=>{
                    const opt=document.createElement("option");
                    opt.value=hh.hh_code;
                    opt.textContent=`${hh.hh_code} - ${hh.hh_name}`;
                    opt.dataset.hhname=hh.hh_name;
                    hhcodeSelect.appendChild(opt);
                });
            }
        } catch(err){
            console.error(err);
            hhcodeSelect.innerHTML='<option>Error loading households</option>';
            showToast("Error loading households", "error");
        }

        try {
            const resRound = await fetch(`${BASE_URL}/api/get_last_round_api.php?cluster_id=${clusterId}`);
            const dataRound = await resRound.json();
            roundInput.value = (dataRound.success && dataRound.last_round!==undefined) ? (dataRound.last_round+1) : 1;
        } catch(err){ roundInput.value=1; }
    }

    if(clstnameSelect){
        clstnameSelect.addEventListener("change", ()=>{
            const sel = clstnameSelect.selectedOptions[0];
            const clusterId = sel?.dataset?.clusterid || "";
            clstidInput.value = clusterId;
            loadHouseholds(clusterId);
        });
    }

    if(hhcodeSelect){
        hhcodeSelect.addEventListener("change", ()=>{
            const sel = hhcodeSelect.selectedOptions[0];
            hhnameInput.value = sel?.dataset?.hhname || "";
        });
    }

    // ===== Wizard Functions =====
    function showStep(step){
        steps.forEach(s => s.classList.remove("active"));
        const activeStep = wizardForm.querySelector(`.wizard-step[data-step="${step}"]`);
        if(activeStep) activeStep.classList.add("active");

        prevBtn.style.display = step===1 ? "none" : "inline-block";
        nextBtn.style.display = step===totalSteps ? "none" : "inline-block";
        submitBtn.style.display = step===totalSteps ? "inline-block" : "none";

        if(step===totalSteps) fillConfirmation(collectFormData());
    }

    function validateStep(step){
        const current = wizardForm.querySelector(`.wizard-step[data-step="${step}"]`);
        const inputs = current.querySelectorAll("input[required], select[required]");
        for(let inp of inputs){
            if(!inp.value.trim()){
                inp.focus();
                showToast("Please fill all required fields.", "error");
                return false;
            }
        }
        return true;
    }

    function collectFormData(){
        const data = {};
        wizardForm.querySelectorAll("input, select, textarea").forEach(inp=>{
            if(inp.name) data[inp.name] = inp.value;
        });
        data.cluster_id = clstidInput?.value || null;
        data.srtname = srtnameInput?.value || sessionUserFullName;

        const speciesData = [];
        document.querySelectorAll('.species-row').forEach(row=>{
            const sname = row.dataset.species;
            speciesData.push({
                species: sname,
                male: Number(row.querySelector('.male')?.value||0),
                female: Number(row.querySelector('.female')?.value||0),
                total: Number(row.querySelector('.total')?.value||0),
                fed: Number(row.querySelector('.fed')?.value||0),
                unfed: Number(row.querySelector('.unfed')?.value||0),
                gravid: Number(row.querySelector('.gravid')?.value||0),
                semigravid: Number(row.querySelector('.semigravid')?.value||0)
            });
        });
        data.species = speciesData;
        return data;
    }

    // ===== Button Events =====
    nextBtn?.addEventListener("click", ()=>{ if(validateStep(currentStep)){ currentStep++; showStep(currentStep); }});
    prevBtn?.addEventListener("click", ()=>{ if(currentStep>1){ currentStep--; showStep(currentStep); }});
    backBtn?.addEventListener("click", ()=>{ currentStep=1; showStep(currentStep); });
    cancelBtn?.addEventListener("click", ()=> location.reload());

    // ===== Species Calculation =====
    function setupSpeciesCalculation(){
        document.querySelectorAll('.species-row').forEach(row=>{
            const male = row.querySelector('.male');
            const female = row.querySelector('.female');
            const total = row.querySelector('.total');
            function updateTotal(){
                total.value = Number(male.value||0)+Number(female.value||0);
                updateGrandTotal();
            }
            male.addEventListener('input', updateTotal);
            female.addEventListener('input', updateTotal);
            updateTotal();
        });
    }

    function updateGrandTotal(){
        let sum = 0;
        document.querySelectorAll('.total').forEach(t=>sum+=Number(t.value||0));
        const grandEl = document.getElementById('grandTotal');
        if(grandEl) grandEl.innerText = sum;
    }

    // ===== Fill Confirmation =====
    function naValue(val){ if(val===null||val===undefined) return 'N/A'; val=val.toString().trim(); if(val==='0'||val==='') return 'N/A'; return val; }
    function fillConfirmation(dataObj){
        if(!confirmationBody) return;
        let html = `<div class="form-header">
                        <h2>FIELD COLLECTION AND LABORATORY DATA</h2>
                        <h3>Entomology Field and Laboratory Sorting Data - ISCA - PHASE - III, 2025</h3>
                        <div class="meta"><span>Date: ${naValue(dataObj['field_coll_date'])}</span> | <span>Round: ${naValue(dataObj['round'])}</span></div>
                    </div>`;
        html += `<table class="info-table">
                    <tr><th colspan="4">Household Information</th></tr>
                    <tr><td>Household Name</td><td>${naValue(dataObj['hhname'])}</td><td>Household Code</td><td>${naValue(dataObj['hhcode'])}</td></tr>
                    <tr><td>Cluster ID</td><td>${naValue(dataObj['cluster_id'])}</td><td>Cluster Name</td><td>${naValue(dataObj['clstname'])}</td></tr>
                    <tr><td>Collection Date</td><td>${naValue(dataObj['field_coll_date'])}</td><td>Form Title</td><td>${naValue(dataObj['ento_lab_frm_title'])}</td></tr>
                    <tr><td>Device ID</td><td>${naValue(dataObj['deviceid'])}</td><td>Recorded by</td><td>${naValue(dataObj['srtname'])}</td></tr>
                 </table>`;
        dataObj.species?.forEach(sp=>{
            html+=`<table class="info-table">
                    <tr><th colspan="6">${sp.species}</th></tr>
                    <tr><td>Male</td><td>${naValue(sp.male)}</td>
                        <td>Female</td><td>${naValue(sp.female)}</td>
                        <td>Total</td><td>${naValue(sp.total)}</td></tr>
                    <tr><td>Fed</td><td>${naValue(sp.fed)}</td>
                        <td>Unfed</td><td>${naValue(sp.unfed)}</td>
                        <td>Gravid</td><td>${naValue(sp.gravid)}</td></tr>
                    <tr><td>Semi-Gravid</td><td>${naValue(sp.semigravid)}</td><td colspan="4"></td></tr>
                   </table>`;
        });
        let grandTotal = dataObj.species?.reduce((sum, sp) => sum + (sp.total || 0), 0) || 0;
        html += `<p><strong>Grand Total: ${grandTotal}</strong></p>`;
        confirmationBody.innerHTML = html;
        window.labDataObj = dataObj;
    }

    // ===== Submit =====
    submitBtn?.addEventListener("click", async ()=>{
        const dataObj = collectFormData();
        try{
            const res = await fetch(BASE_URL + '/api/desklabapi/add_lab_data_api.php',{
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body: JSON.stringify(dataObj)
            });
            const resp = await res.json();
            if(resp.success){
                showToast("Data submitted successfully!", "success");
                wizardForm.reset();
                currentStep=1;
                showStep(currentStep);
            } else showToast(resp.message||"Submission failed!", "error");
        } catch(err){
            console.error(err);
            showToast("Server error!", "error");
        }
    });

    // ===== Export PDF =====
    exportPdfBtn?.addEventListener('click', async (e)=>{
        e.preventDefault();
        if(!confirmationBody) return;
        if(!window.jspdf || !window.html2canvas){
            showToast("jsPDF or html2canvas not loaded.", "error");
            return;
        }

        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('landscape','pt','a4');
        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();

        const canvas = await html2canvas(confirmationBody, { scale: 1.5 });
        const imgData = canvas.toDataURL('image/png');
        const imgWidth = pageWidth - 80;
        const imgHeight = (canvas.height * imgWidth) / canvas.width;

        pdf.addImage(imgData,'PNG',40,60,imgWidth,imgHeight);

        // Footer
        const dateStr = new Date().toLocaleString();
        pdf.setFontSize(10);
        pdf.text(`Prepared by: ${sessionUserFullName}`, 40, pageHeight - 40);
        pdf.text(`Signature: ______________________`, pageWidth/2, pageHeight - 40, {align:'center'});
        pdf.text(`Date: ${dateStr}`, pageWidth - 40, pageHeight - 40, {align:'right'});

        pdf.save(`lab_confirmation_${Date.now()}.pdf`);
        showToast("PDF exported successfully!", "success");
    });

    // ===== Initialize =====
    loadClusters();
    showStep(currentStep);
    setupSpeciesCalculation();
});
