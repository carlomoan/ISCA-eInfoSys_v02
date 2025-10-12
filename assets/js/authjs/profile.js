
function goBack() {
    if (document.referrer) {
        window.location.href = document.referrer; 
    } else {
        window.location.href = 'index.php?page=dashboard'; // fallback
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const profileForm = document.getElementById('profileForm');

    // Fetch user profile
    fetch(BASE_URL + '/api/auth/profile_api.php?action=get')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('fname').value = data.user.fname || '';
                document.getElementById('lname').value = data.user.lname || '';
                document.getElementById('phone').value = data.user.phone || '';
                document.getElementById('email').value = data.user.email || '';
                document.getElementById('password').value = '********';

                // Profile card
                document.getElementById('profile-fullname').textContent = data.user.fname + ' ' + data.user.lname;
                document.getElementById('profile-role').textContent = data.user.role_name || 'User';
                document.getElementById('profile-registered').textContent = data.user.created_at || '';
            } else {
                showToast(data.message, "error");
            }
        });

    // Submit form (update profile)
    profileForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(profileForm);
        formData.append('action', 'update');

        fetch(BASE_URL + '/api/auth/profile_api.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(r => {
                if (r.success) {
                    showToast(r.message, "success");

                    // Update profile card fullname
                    document.getElementById('profile-fullname').textContent =
                        document.getElementById('fname').value + ' ' + document.getElementById('lname').value;
                } else {
                    showToast(r.message, "error");
                }
            })
            .catch(() => showToast("Something went wrong.", "error"));
    });
});

// Close modal
function closeProfileModal() {
    const modal = document.getElementById('profileModal');
    if (modal) modal.classList.remove('show');
}
