document.addEventListener('DOMContentLoaded', function () {

    // Toast system
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
        setTimeout(() => { toast.classList.remove("visible"); setTimeout(() => toast.remove(), 500); }, duration);
    }

    const tabContainer = document.getElementById("tab-desk_add_field");
    if (!tabContainer) return;

    const form = tabContainer.querySelector("#fieldWizardForm");
    const steps = form.querySelectorAll(".wizard-step");
    const indicators = tabContainer.querySelectorAll(".actions .step");
    const prevBtn = form.querySelector("#prevBtn");
    const nextBtn = form.querySelector("#nextBtn");
    const submitBtn = form.querySelector("#submitBtn");
    const cancelBtn = form.querySelector("#cancelBtn");
    const backBtn = form.querySelector("#backBtn");
    const printBtn = form.querySelector("#printBtn");

    let currentStep = 1;
    const totalSteps = steps.length;

    // Auto-fill fields
    const startField = form.querySelector("[name=start]");
    const endField = form.querySelector("[name=end]");
    const deviceField = form.querySelector("[name=deviceid]");
    const instanceField = form.querySelector("[name=instanceID]");
    const fldrecnameInput = form.querySelector("[name=fldrecname]");
    const userRoleInput = form.querySelector("[name=user_role]");

    if (startField) startField.value = new Date().toISOString().slice(0,16);
    if (deviceField) { deviceField.value = `DEVICE-${Date.now()}`; deviceField.readOnly = true; }
    if (instanceField) { instanceField.value = `INST-${Date.now()}`; instanceField.readOnly = true; }
    if (fldrecnameInput && window.CURRENT_USER?.name) fldrecnameInput.value = window.CURRENT_USER.name;
    if (userRoleInput && window.CURRENT_USER?.role) userRoleInput.value = window.CURRENT_USER.role;

    // Cluster & Household
    const clstnameSelect = form.querySelector("#clstname");
    const clstidInput = form.querySelector("#clstid");
    const hhcodeSelect = form.querySelector("#hhcode");
    const hhnameInput = form.querySelector("#hhname");
    const roundInput = form.querySelector("#round");

    async function loadClusters() {
        if(!clstnameSelect) return;
        clstnameSelect.innerHTML='<option>Loading...</option>';
        try {
            const res = await fetch(BASE_URL + '/api/get_user_clusters_api.php');
            const data = await res.json();
            clstnameSelect.innerHTML='';
            if(data.success && Array.isArray(data.data)){
                clstnameSelect.innerHTML='<option value="">--Select Cluster--</option>';
                data.data.forEach(cl=>{
                    const opt = document.createElement("option");
                    opt.value = cl.cluster_name;
                    opt.textContent = cl.cluster_name;
                    opt.dataset.clusterid = cl.cluster_id;
                    clstnameSelect.appendChild(opt);
                });
            } else clstnameSelect.innerHTML='<option>No clusters assigned</option>';
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
            showToast("Error loading households.", "error");
        }

        try {
            const resRound = await fetch(`${BASE_URL}/api/get_last_round_api.php?cluster_id=${clusterId}`);
            const dataRound = await resRound.json();
            roundInput.value = (dataRound.success && dataRound.last_round!==undefined) ? (dataRound.last_round+1) : 1;
        } catch(err){
            console.error(err);
            roundInput.value=1;
        }
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

    // Wizard functions
    function showStep(step){
        steps.forEach(s => s.classList.remove("active"));
        indicators.forEach(i => i.classList.remove("active"));
        const activeStep = form.querySelector(`.wizard-step[data-step="${step}"]`);
        const activeInd = tabContainer.querySelector(`.actions .step[data-step="${step}"]`);
        if(activeStep) activeStep.classList.add("active");
        if(activeInd) activeInd.classList.add("active");
        prevBtn.style.display = step===1 ? "none" : "inline-block";
        nextBtn.style.display = step===totalSteps ? "none" : "inline-block";
        submitBtn.style.display = step===totalSteps ? "inline-block" : "none";

        if(step===totalSteps && typeof fillConfirmation === 'function'){
            fillConfirmation(collectFormData());
        }
    }

    function validateStep(step){
        const current = form.querySelector(`.wizard-step[data-step="${step}"]`);
        const inputs = current.querySelectorAll("input[required], select[required]");
        for(let inp of inputs){
            if(!inp.value.trim()){
                inp.focus();
                showToast("Fill all required fields.", "error");
                return false;
            }
        }
        return true;
    }

    function markCompleted(step){
        const stepEl = tabContainer.querySelector(`.wizard-steps .step[data-step="${step}"]`);
        if(stepEl){
            stepEl.classList.add("completed");
            const dot = stepEl.querySelector(".step-dot");
            if(dot) dot.innerHTML="✔";
        }
    }

    function collectFormData(){
        const data = {};
        form.querySelectorAll("input, select, textarea").forEach(inp=>{
            if(inp.name) data[inp.name]=inp.value;
        });
        return data;
    }

    nextBtn?.addEventListener("click", ()=>{
        if(validateStep(currentStep)){
            markCompleted(currentStep);
            currentStep++;
            showStep(currentStep);
        }
    });
    prevBtn?.addEventListener("click", ()=>{
        if(currentStep>1){
            currentStep--;
            showStep(currentStep);
        }
    });
    backBtn?.addEventListener("click", ()=>{
        currentStep=1;
        showStep(currentStep);
    });
    cancelBtn?.addEventListener("click", ()=> location.reload());

    // Initialize
    loadClusters();
    showStep(currentStep);

});
