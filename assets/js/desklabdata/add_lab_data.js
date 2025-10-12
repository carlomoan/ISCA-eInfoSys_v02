document.addEventListener('DOMContentLoaded', function() {

    const form = document.getElementById('fieldWizardForm');
    const steps = document.querySelectorAll('.wizard-step');
    const stepIndicators = document.querySelectorAll('.wizard-steps .step');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');

    let currentStep = 0;

    function showStep(n){
        steps.forEach((s,i)=> s.classList.toggle('active', i===n));
        stepIndicators.forEach((s,i)=>{
            s.classList.toggle('active', i===n);
            if(i<n) s.classList.add('completed'); else s.classList.remove('completed');
        });
        prevBtn.style.display = n===0 ? 'none':'inline-block';
        nextBtn.style.display = n===steps.length-1 ? 'none':'inline-block';
        submitBtn.style.display = n===steps.length-1 ? 'inline-block':'none';
    }

    showStep(currentStep);

    nextBtn.addEventListener('click', function(){
        if(validateStep(currentStep)){
            currentStep++;
            showStep(currentStep);
        }
    });
    prevBtn.addEventListener('click', function(){
        currentStep--;
        showStep(currentStep);
    });

    function validateStep(n){
        let inputs = steps[n].querySelectorAll('input, select, textarea');
        for(let inp of inputs){
            if(inp.hasAttribute('required') && !inp.value){
                inp.focus();
                return false;
            }
        }
        return true;
    }

    // Auto-populate meta fields
    const startInput = form.querySelector('input[name="start"]');
    const endInput = form.querySelector('input[name="end"]');
    const deviceInput = form.querySelector('input[name="deviceid"]');
    const instanceInput = form.querySelector('input[name="instanceID"]');

    startInput.value = new Date().toISOString().slice(0,16);
    deviceInput.value = generateDeviceID();
    instanceInput.value = generateInstanceID();

    function generateDeviceID(){ return 'DEV-' + Math.random().toString(36).substring(2,10); }
    function generateInstanceID(){ return 'INST-' + Date.now(); }

    // Auto-populate clusters
    const clstnameSelect = form.querySelector('#clstname');
    const clstidInput = form.querySelector('#clstid');
    fetch(BASE_URL + 'api/get_user_clusters_api.php')
        .then(res=>res.json())
        .then(data=>{
            data.forEach(c=>{
                let opt = document.createElement('option');
                opt.value = c.cluster_name;
                opt.dataset.clstid = c.cluster_id;
                opt.textContent = c.cluster_name;
                clstnameSelect.appendChild(opt);
            });
        });

    clstnameSelect.addEventListener('change', function(){
        clstidInput.value = clstnameSelect.selectedOptions[0]?.dataset.clstid || '';
        loadHouseholds(clstidInput.value);
        setRound(clstidInput.value);
    });

    // Load households based on cluster
    const hhcodeSelect = form.querySelector('#hhcode');
    const hhnameInput = form.querySelector('#hhname');
    function loadHouseholds(clusterId){
        hhcodeSelect.innerHTML='<option value="">--Select--</option>';
        if(!clusterId) return;
        fetch(`${BASE_URL}api/get_cluster_households_api.php?cluster_id=${clusterId}`)
            .then(res=>res.json())
            .then(data=>{
                data.forEach(hh=>{
                    let opt=document.createElement('option');
                    opt.value = hh.hhcode;
                    opt.dataset.hhname = hh.head_name;
                    opt.textContent = hh.hhcode;
                    hhcodeSelect.appendChild(opt);
                });
            });
    }

    hhcodeSelect.addEventListener('change', function(){
        hhnameInput.value = hhcodeSelect.selectedOptions[0]?.dataset.hhname || '';
    });

    // Auto-round (last round +1)
    function setRound(clusterId){
        fetch(`${BASE_URL}api/get_last_round_api.php?cluster_id=${clusterId}`)
            .then(res=>res.json())
            .then(data=>{
                form.querySelector('#round').value = (parseInt(data.last_round)||0)+1;
            });
    }

    // On submit
    form.addEventListener('submit', function(e){
        e.preventDefault();
        endInput.value = new Date().toISOString().slice(0,16); // set end time
        const fd = new FormData(form);
        fetch(BASE_URL + 'api/desklabapi/add_lab_data.php',{
            method:'POST',
            body:fd
        }).then(res=>res.json())
        .then(resp=>{
            if(resp.success) alert('Laboratory data saved successfully!');
            else alert(resp.message);
        });
    });

});
