document.addEventListener("DOMContentLoaded", function(){

    const steps = document.querySelectorAll(".form-step");
    const stepIndicators = document.querySelectorAll(".step-indicator .step");
    const fieldForm = document.getElementById("fieldDataForm");
    let currentStep = 0;

    // ===== Show step =====
    function showStep(index){
        steps.forEach((s,i)=>s.classList.toggle("active",i===index));
        stepIndicators.forEach((s,i)=>{
            s.classList.toggle("active",i===index);
            s.classList.toggle("complete",i<index);
        });
    }

    // ===== Navigation buttons =====
    document.querySelectorAll(".btn-next").forEach(btn=>{
        btn.addEventListener("click",()=>{
            if(currentStep<steps.length-1){
                currentStep++;
                showStep(currentStep);
            }
        });
    });
    document.querySelectorAll(".btn-prev").forEach(btn=>{
        btn.addEventListener("click",()=>{
            if(currentStep>0){
                currentStep--;
                showStep(currentStep);
            }
        });
    });

    showStep(currentStep);

    // ===== Auto fields =====
    const startInput = fieldForm.querySelector('input[name="start"]');
    const endInput = fieldForm.querySelector('input[name="end"]');
    const deviceInput = fieldForm.querySelector('input[name="deviceid"]');
    const fldrecnameInput = fieldForm.querySelector('input[name="fldrecname"]');
    const roundInput = fieldForm.querySelector('input[name="round"]');
    const instanceIDInput = fieldForm.querySelector('input[name="instanceID"]');
    const clstnameSelect = fieldForm.querySelector('select[name="clstname"]');
    const clstidInput = fieldForm.querySelector('input[name="clstid"]');
    const hhcodeSelect = fieldForm.querySelector('select[name="hhcode"]');
    const hhnameInput = fieldForm.querySelector('input[name="hhname"]');

    // Auto start datetime
    if(startInput) startInput.value = new Date().toISOString().slice(0,16);

    // Auto device id
    if(deviceInput){
        deviceInput.value = navigator.userAgent; // use user agent as device id
        deviceInput.readOnly = true;
    }

    // Auto instanceID
    if(instanceIDInput){
        instanceIDInput.value = "ISCA_" + Date.now() + "_" + Math.floor(Math.random()*1000);
        instanceIDInput.readOnly = true;
    }

    // Auto fldrecname from session (assume available globally)
    if(fldrecnameInput && window.SESSION_USER_FULLNAME){
        fldrecnameInput.value = window.SESSION_USER_FULLNAME;
        fldrecnameInput.readOnly = true;
    }

    // ===== Fetch clusters assigned to user =====
    fetch(BASE_URL + "/api/get_user_clusters_api.php")
        .then(res=>res.json())
        .then(data=>{
            if(data.success && Array.isArray(data.clusters)){
                data.clusters.forEach(cl=>{
                    const opt = document.createElement("option");
                    opt.value = cl.clustername;
                    opt.dataset.clusterid = cl.clusterid;
                    clstnameSelect.appendChild(opt);
                });
            }
        })
        .catch(err=>console.error("Error loading clusters:", err));

    // ===== On cluster change, populate cluster_id, hhcode dropdown and round =====
    clstnameSelect.addEventListener("change", function(){
        const selected = clstnameSelect.selectedOptions[0];
        const clusterId = selected?.dataset?.clusterid || "";
        clstidInput.value = clusterId;

        // Fetch households for selected cluster
        if(clusterId){
            fetch(`${BASE_URL}/api/get_cluster_households_api.php?cluster_id=${clusterId}`)
            .then(res=>res.json())
            .then(data=>{
                hhcodeSelect.innerHTML = '<option value="">--Select Household--</option>';
                if(data.success && Array.isArray(data.households)){
                    data.households.forEach(hh=>{
                        const opt = document.createElement("option");
                        opt.value = hh.hhcode;
                        opt.textContent = hh.hhcode;
                        opt.dataset.hhname = hh.head_name;
                        hhcodeSelect.appendChild(opt);
                    });
                }
            });
        }

        // Auto calculate next round (from last round of cluster)
        if(clusterId){
            fetch(`${BASE_URL}/api/get_last_round_api.php?cluster_id=${clusterId}`)
            .then(res=>res.json())
            .then(data=>{
                if(data.success && data.last_round !== undefined){
                    roundInput.value = data.last_round + 1;
                } else {
                    roundInput.value = 1;
                }
            });
        }
    });

    // ===== On household change, populate hhname =====
    hhcodeSelect.addEventListener("change", function(){
        const selected = hhcodeSelect.selectedOptions[0];
        hhnameInput.value = selected?.dataset?.hhname || "";
    });

    // ===== On form submit =====
    fieldForm.addEventListener("submit", function(e){
        e.preventDefault();
        endInput.value = new Date().toISOString().slice(0,16); // set end time

        const formData = new FormData(fieldForm);
        fetch(BASE_URL + "/api/deskfieldapi/add_field_data.php", {
            method:"POST",
            body: formData
        })
        .then(async res=>{
            const text = await res.text();
            try{
                return JSON.parse(text);
            } catch(err){
                throw new Error("Server did not return JSON:\n" + text.substring(0,200));
            }
        })
        .then(data=>{
            if(data.success){
                alert(data.message || "Field data saved successfully.");
                fieldForm.reset();
                showStep(0);
            } else {
                alert(data.message || "Failed to submit field data.");
            }
        })
        .catch(err=>{
            console.error(err);
            alert("Error submitting form: "+err.message);
        });
    });

});
