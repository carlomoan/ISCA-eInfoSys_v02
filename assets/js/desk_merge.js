

document.addEventListener("DOMContentLoaded", () => {
    const apiUrl = BASE_URL + "/api/deskmergeapi/desk_merge_api.php";
    const summaryBox = document.getElementById("merge-summary");
    const previewBox = document.getElementById("merge-preview");
    const mismatchesBox = document.getElementById("merge-mismatches");
    const toastContainer = document.getElementById("toast-container");

    // --- Helper: Toast ---
    function showToast(message, type = "info") {
        const toast = document.createElement("div");
        toast.style.cssText = `
            padding:10px 15px; margin-bottom:10px; border-radius:6px; min-width:200px;
            color:white; font-size:14px; box-shadow:0 2px 6px rgba(0,0,0,0.2);
            opacity:0.95; transition: all 0.3s;
        `;
        if(type==="success") toast.style.background="#4caf50";
        else if(type==="error") toast.style.background="#f44336";
        else if(type==="warning") toast.style.background="#ff9800";
        else toast.style.background="#2196f3";

        toast.innerText = message;
        toastContainer.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    // --- Buttons Container: created dynamically below summary ---
    let actionsContainer = document.createElement("div");
    actionsContainer.style.cssText = `
        display:flex; justify-content:flex-end; gap:10px; margin:15px 0;
    `;

    let btnCheck = document.createElement("button");
    btnCheck.innerText = "Check Data";
    btnCheck.style.cssText = `
        padding:8px 14px; border:none; border-radius:5px; background:#1976d2; color:white; cursor:pointer;
        font-weight:bold; transition:0.2s;
    `;
    btnCheck.onmouseover = () => btnCheck.style.background="#1565c0";
    btnCheck.onmouseout = () => btnCheck.style.background="#1976d2";

    let btnMerge = document.createElement("button");
    btnMerge.innerText = "Merge Matched Data";
    btnMerge.disabled = true;
    btnMerge.style.cssText = `
        padding:8px 14px; border:none; border-radius:5px; background:#388e3c; color:white; cursor:pointer;
        font-weight:bold; opacity:0.7; transition:0.2s;
    `;
    btnMerge.onmouseover = () => { if(!btnMerge.disabled) btnMerge.style.opacity=1; };
    btnMerge.onmouseout = () => { if(!btnMerge.disabled) btnMerge.style.opacity=0.9; };

    let btnDelete = document.createElement("button");
    btnDelete.innerText = " Delete Temp Data";
    btnDelete.style.cssText = `
        padding:8px 14px; border:none; border-radius:5px; background:#d32f2f; color:white; cursor:pointer;
        font-weight:bold; transition:0.2s;
    `;
    btnDelete.onmouseover = () => btnDelete.style.background="#c62828";
    btnDelete.onmouseout = () => btnDelete.style.background="#d32f2f";

    actionsContainer.appendChild(btnCheck);
    actionsContainer.appendChild(btnMerge);
    actionsContainer.appendChild(btnDelete);

    // Append actions container right below summary cards
    summaryBox.insertAdjacentElement('afterend', actionsContainer);

    // --- Render Summary ---
    function renderSummary(data) {
        summaryBox.innerHTML = `
            <div style="display:flex; gap:15px; margin-bottom:20px;">
                <div style="flex:1; background:#e8f5e9; border-radius:10px; padding:20px; text-align:center; 
                            box-shadow:0 2px 6px rgba(0,0,0,0.1);">
                    <h6 style="margin:0; font-size:14px; color:#2e7d32;">Received Matched Data</h6>
                    <p style="margin:5px 0 0; font-size:20px; font-weight:bold; color:#1b5e20;">
                        ${data.matched_total}
                    </p>
                </div>

                <div style="flex:1; background:#e3f2fd; border-radius:10px; padding:20px; text-align:center; 
                            box-shadow:0 2px 6px rgba(0,0,0,0.1);">
                    <h6 style="margin:0; font-size:14px; color:#1565c0;">Received Field Data</h6>
                    <p style="margin:5px 0 0; font-size:20px; font-weight:bold; color:#0d47a1;">
                        ${data.field_only.length}
                    </p>
                </div>

                <div style="flex:1; background:#fff3e0; border-radius:10px; padding:20px; text-align:center; 
                            box-shadow:0 2px 6px rgba(0,0,0,0.1);">
                    <h6 style="margin:0; font-size:14px; color:#ef6c00;">Received Laboratory Data</h6>
                    <p style="margin:5px 0 0; font-size:20px; font-weight:bold; color:#e65100;">
                        ${data.lab_only.length}
                    </p>
                </div>
            </div>
        `;
    }

    // --- Render Preview ---
    function renderPreview(rows) {
        if (!rows || !rows.length) {
            previewBox.innerHTML = `<p style="color:#666; font-style:italic;">No matched data found.</p>`;
            return;
        }
        let table = `<h4>Received Matched Data</h4><table style="width:100%; border-collapse:collapse; margin-top:10px;">
            <thead>
                <tr style="background:#f0f0f0; text-align:left;">
                    <th style="border:1px solid #ccc; padding:6px;">Household #</th>
                    <th style="border:1px solid #ccc; padding:6px;">Header Name</th>
                    <th style="border:1px solid #ccc; padding:6px;">Cluster Name</th>
                    <th style="border:1px solid #ccc; padding:6px;">Field Collection Date</th>
                    <th style="border:1px solid #ccc; padding:6px;">Lab Sorting Date</th>
                </tr>
            </thead>
            <tbody>
                ${rows.map(r => `<tr>
                    <td style="border:1px solid #ccc; padding:6px;">${r.hhcode}</td>
                    <td style="border:1px solid #ccc; padding:6px;">${r.hhname || "-"}</td>
                    <td style="border:1px solid #ccc; padding:6px;">${r.cluster_name || "-"}</td>
                    <td style="border:1px solid #ccc; padding:6px;">${r.field_coll_date || "-"}</td>
                    <td style="border:1px solid #ccc; padding:6px;">${r.lab_date || "-"}</td>
                </tr>`).join("")}
            </tbody>
        </table>`;
        previewBox.innerHTML = table;
    }

    // --- Render Mismatches ---
    function renderMismatches(fieldOnly, labOnly) {
        mismatchesBox.innerHTML = `
            <h4>Field and Laboratory Received Data</h4>
            <div style="display:flex; gap:20px;">
                <div style="flex:1;">
                    <h5>Received Field Data (${fieldOnly.length})</h5>
                    <table style="width:100%; border-collapse:collapse;">
                        <thead><tr><th style="border:1px solid #ccc; padding:6px;">HH #</th><th style="border:1px solid #ccc; padding:6px;">Cluster Name</th></tr></thead>
                        <tbody>
                            ${fieldOnly.map(hh => `<tr><td style="border:1px solid #ccc; padding:6px;">${hh.hhcode}</td><td style="border:1px solid #ccc; padding:6px;">${hh.cluster_name || "-"}</td></tr>`).join("") || "<tr><td colspan='2'>None</td></tr>"}
                        </tbody>
                    </table>
                </div>
                <div style="flex:1;">
                    <h5>Received Laboratory Data (${labOnly.length})</h5>
                    <table style="width:100%; border-collapse:collapse;">
                        <thead><tr><th style="border:1px solid #ccc; padding:6px;">HH #</th><th style="border:1px solid #ccc; padding:6px;">Cluster Name</th></tr></thead>
                        <tbody>
                            ${labOnly.map(hh => `<tr><td style="border:1px solid #ccc; padding:6px;">${hh.hhcode}</td><td style="border:1px solid #ccc; padding:6px;">${hh.cluster_name || "-"}</td></tr>`).join("") || "<tr><td colspan='2'>None</td></tr>"}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    // --- Fetch / Check Data ---
    function fetchCheck() {
        fetch(apiUrl + "?check=1")
            .then(res => res.json())
            .then(data => {
                if (!data.success) return showToast(data.message,"error");
                renderSummary(data);
                renderPreview(data.preview);
                renderMismatches(data.field_only, data.lab_only);
                btnMerge.disabled = data.matched_total === 0;
                showToast("✅ Data check completed","success");
            })
            .catch(err => {
                console.error(err);
                showToast("❌ Error checking data","error");
            });
    }

    // --- Initial fetch ---
    fetchCheck();

    // --- Button Actions ---
    btnCheck.addEventListener("click", fetchCheck);

    btnMerge.addEventListener("click", () => {
        if(!confirm("Merge matched data?")) return;
        fetch(apiUrl, {method:"POST"})
            .then(res=>res.json())
            .then(data=>{
                if(data.success){
                    showToast("✅ "+data.message,"success");
                    btnMerge.disabled=true;
                    fetchCheck();
                } else showToast("⚠️ "+data.message,"error");
            })
            .catch(err=>{
                console.error(err);
                showToast("❌ Merge failed","error");
            });
    });

    btnDelete.addEventListener("click", () => {
        if(!confirm("Delete all temp desk data?")) return;
        fetch(apiUrl+"?delete_temp=1",{method:"POST"})
            .then(res=>res.json())
            .then(data=>{
                if(data.success){
                    showToast("🗑️ Temp data deleted","success");
                    summaryBox.innerHTML='';
                    previewBox.innerHTML='';
                    mismatchesBox.innerHTML='';
                    btnMerge.disabled=true;
                } else showToast("⚠️ "+data.message,"error");
            })
            .catch(err=>{
                console.error(err);
                showToast("❌ Delete temp failed","error");
            });
    });

});
