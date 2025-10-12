

let usersData = [];
let currentUserId = null;

async function fetchUsers(){
    try {
        const res = await fetch(BASE_URL + "/api/users/users_list_api.php");
        const data = await res.json();
        if(data.success){
            usersData = data.users;
            renderUsersTable(usersData);
        } else {
            document.getElementById("usersBody").innerHTML = '<tr><td colspan="12">No users found</td></tr>';
        }
    } catch(e){ console.error(e); alert("Error fetching users"); }
}

function renderUsersTable(data){
    const tbody = document.getElementById("usersBody");
    tbody.innerHTML = data.map(u=>{
        const projectsHtml = u.projects && u.projects.length 
            ? u.projects.map(p => `<span class="badge">${p.name}</span>`).join(' ')
            : `<span class="text-muted">No Projects</span>`;
        return `
        <tr id="user-row-${u.id}">
            <td><input type="checkbox" class="row-select" data-id="${u.id}"></td>
            <td>${u.id}</td>
            <td>${u.fname}</td>
            <td>${u.lname}</td>
            <td>${u.email}</td>
            <td>${u.phone}</td>
            <td>${u.role_name||'-'}</td>
            <td>${u.lab_tech_name||'-'}</td>
            <td>${projectsHtml}</td>
            <td>${u.created_at}</td>
            <td>${u.updated_at}</td>
            <td><button class="action-btn" data-id="${u.id}">Actions</button></td>
        </tr>`;
    }).join('');

    document.querySelectorAll('.action-btn').forEach(btn=>{
        btn.addEventListener('click',()=>openUserModal(btn.dataset.id));
    });
}

// ----- Actions Modal -----
const userModal = document.createElement('div');
userModal.className = 'modal hidden';
userModal.innerHTML = `
<div class="modal-content" style="width:450px;text-align:left;">
    <span class="close-btn" onclick="closeUserModal()">&times;</span>
    <h4 id="modalUserName"></h4>
    <div id="modalFields"></div>
    <button id="modalSaveBtn">Save</button>
    <button id="modalDeleteBtn" style="background:red;color:white;">Delete User</button>
</div>`;
document.body.appendChild(userModal);

function openUserModal(userId){
    currentUserId = userId;
    const user = usersData.find(u=>u.id==userId);
    if(!user) return;
    userModal.classList.remove('hidden');
    document.getElementById('modalUserName').textContent = `${user.fname} ${user.lname}`;
    const fieldsDiv = document.getElementById('modalFields');
    fieldsDiv.innerHTML = `
        <label>First Name</label><input type="text" id="modal_fname" value="${user.fname}"><br>
        <label>Last Name</label><input type="text" id="modal_lname" value="${user.lname}"><br>
        <label>Phone</label><input type="text" id="modal_phone" value="${user.phone}"><br>
        <label>Lab Technician</label>
        <select id="modal_lab_tech"></select><br>
        <label>Projects (comma separated)</label>
        <textarea id="modal_projects" rows="3">${user.projects.map(p=>p.name).join(', ')}</textarea>
    `;
    populateLabTechSelect(user.lab_tech_id);
}

function closeUserModal(){ userModal.classList.add('hidden'); currentUserId=null; }

async function populateLabTechSelect(selectedId){
    try{
        const res = await fetch(BASE_URL + "/api/users/lab_tech_list_api.php");
        const data = await res.json();
        const select = document.getElementById('modal_lab_tech');
        select.innerHTML = '';
        data.forEach(l=>{
            const opt = document.createElement('option');
            opt.value = l.id; opt.textContent = l.name;
            if(l.id==selectedId) opt.selected=true;
            select.appendChild(opt);
        });
    }catch(e){ console.error(e); }
}

// ----- Save User -----
document.getElementById('modalSaveBtn').addEventListener('click', async ()=>{
    if(!currentUserId) return;
    const fields = {
        fname: document.getElementById('modal_fname').value,
        lname: document.getElementById('modal_lname').value,
        phone: document.getElementById('modal_phone').value,
        lab_tech_id: document.getElementById('modal_lab_tech').value,
        projects: document.getElementById('modal_projects').value.split(',').map(v=>v.trim()).filter(Boolean)
    };
    try{
        for(const key in fields){
            await fetch(BASE_URL + "/api/users/users_update_api.php",{
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body: JSON.stringify({id:currentUserId, field:key, value:fields[key]})
            });
        }
        alert("User updated");
        closeUserModal();
        fetchUsers();
    }catch(e){ console.error(e); alert("Error saving user"); }
});

// ----- Delete User -----
document.getElementById('modalDeleteBtn').addEventListener('click', async ()=>{
    if(!currentUserId) return;
    if(!confirm("Are you sure you want to delete this user?")) return;
    try{
        const res = await fetch(BASE_URL + "/api/users/users_delete_api.php",{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({id:currentUserId})
        });
        const data = await res.json();
        if(data.success){
            alert("User deleted");
            closeUserModal();
            fetchUsers();
        } else alert("Delete failed: "+data.message);
    }catch(e){ console.error(e); alert("Error deleting user"); }
});

document.addEventListener('DOMContentLoaded', fetchUsers);
