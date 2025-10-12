let currentManageUserId = null;

// ===== Toast helper =====
function showToast(msg, type="success", duration=2000){
    const t = document.createElement("div");
    t.className = `toast ${type}`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(()=>{ t.remove(); }, duration);
}

// ===== Open Manage User Modal =====
async function openManageUserModal(userId){
    currentManageUserId = userId;
    const modal = document.getElementById("manageUserModal");
    modal.classList.remove("hidden");

    const user = usersData.find(u=>u.id==userId);
    if(!user) return;

    // Populate toggles
    document.getElementById("toggleVerified").checked = user.is_verified;
    document.getElementById("toggleAdmin").checked = user.is_admin;

    // Populate Projects
    const projectsContainer = document.getElementById("manageProjectsContainer");
    projectsContainer.innerHTML = "Loading...";
    const resProjects = await fetch(BASE_URL + "/api/projects/projects_list_api.php");
    const dataProjects = await resProjects.json();
    if(dataProjects.success){
        projectsContainer.innerHTML = "";
        dataProjects.projects.forEach(p=>{
            const chk = document.createElement("input");
            chk.type="checkbox"; chk.value=p.project_id; chk.id=`proj-${p.project_id}`;
            if(user.projects.some(up=>up.id==p.project_id)) chk.checked = true;
            const label = document.createElement("label");
            label.htmlFor = `proj-${p.project_id}`; label.textContent=p.project_name;
            projectsContainer.appendChild(chk); projectsContainer.appendChild(label);
            projectsContainer.appendChild(document.createElement("br"));
        });
    }

    // Populate Clusters
    const clustersContainer = document.getElementById("manageClustersContainer");
    clustersContainer.innerHTML = "Loading...";
    const resClusters = await fetch(BASE_URL + "/api/clusters/clusters_list_api.php");
    const dataClusters = await resClusters.json();
    if(dataClusters.success){
        clustersContainer.innerHTML = "";
        dataClusters.clusters.forEach(c=>{
            const chk = document.createElement("input");
            chk.type="checkbox"; chk.value=c.cluster_id; chk.id=`cluster-${c.cluster_id}`;
            if(user.clusters.some(uc=>uc.id==c.cluster_id)) chk.checked = true;
            const label = document.createElement("label");
            label.htmlFor = `cluster-${c.cluster_id}`; label.textContent=c.cluster_name;
            clustersContainer.appendChild(chk); clustersContainer.appendChild(label);
            clustersContainer.appendChild(document.createElement("br"));
        });
    }

    // Populate Lab Technicians
    const labtechSelect = document.getElementById("manageLabTechSelect");
    labtechSelect.innerHTML = "<option value=''>-- Select Lab Technician --</option>";
    const resLab = await fetch(BASE_URL + "/api/lab_technicians/lab_technicians_list_api.php");
    const dataLab = await resLab.json();
    if(dataLab.success){
        dataLab.lab_technicians.forEach(l=>{
            const option = document.createElement("option");
            option.value = l.lab_tech_id;
            option.textContent = `${l.fname} ${l.lname}`;
            if(user.lab_tech_id && user.lab_tech_id==l.lab_tech_id) option.selected = true;
            labtechSelect.appendChild(option);
        });
    }
}

// ===== Close Modal =====
function closeManageUserModal(){
    document.getElementById("manageUserModal").classList.add("hidden");
}

// ===== Save Changes =====
document.getElementById("manageUserSaveBtn").addEventListener("click", async ()=>{
    if(!currentManageUserId) return;

    const payload = {
        user_id: currentManageUserId,
        is_verified: document.getElementById("toggleVerified").checked ? 1 : 0,
        is_admin: document.getElementById("toggleAdmin").checked ? 1 : 0,
        project_ids: Array.from(document.querySelectorAll("#manageProjectsContainer input:checked")).map(c=>c.value),
        cluster_ids: Array.from(document.querySelectorAll("#manageClustersContainer input:checked")).map(c=>c.value),
        lab_tech_id: document.getElementById("manageLabTechSelect").value || null
    };

    try{
        const res = await fetch(BASE_URL + "/api/users/users_manage_update.php",{
            method:"POST",
            headers:{"Content-Type":"application/json"},
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if(data.success){
            showToast("User updated successfully!");
            closeManageUserModal();
            fetchUsers(); // refresh table
        } else showToast("Update failed: "+data.message,"error");
    } catch(err){ console.error(err); showToast("Update error","error"); }
});
