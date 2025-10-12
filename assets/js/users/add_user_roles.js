document.addEventListener("DOMContentLoaded", async () => {
    const container = document.getElementById("views-users-container");

    try {
        const res = await fetch(BASE_URL + "/api/users/users_list_api.php");
        const data = await res.json();

        if (!data.success) {
            container.innerHTML = "<p>Failed to load users</p>";
            return;
        }

        const { users } = data;

        let html = `
        <table class="user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Registered</th>
                    <th>Last Updated</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
        `;

        users.forEach(user => {
            html += `
            <tr>
                <td>${user.id}</td>
                <td>${user.fname} ${user.lname}</td>
                <td>${user.email}</td>
                <td>${user.phone}</td>
                <td>${user.created_at}</td>
                <td>${user.updated_at}</td>
                <td><button onclick='openUserModal(${JSON.stringify(user)})'>Manage</button></td>
            </tr>
            `;
        });

        html += "</tbody></table>";
        container.innerHTML = html;

    } catch (err) {
        console.error(err);
        container.innerHTML = "<p>Error loading users</p>";
    }
});

// Temporary placeholder function, modal logic kuja baadaye
function openUserModal(user) {
    console.log("Open modal for user:", user);
    // Modal populate logic itakuja hapa baadaye
}