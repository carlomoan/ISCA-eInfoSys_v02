/**
 * Registration Wizard JavaScript
 * Handles multi-step registration form with validation
 */

document.addEventListener('DOMContentLoaded', function() {
    let currentStep = 1;
    const totalSteps = 4;
    const form = document.getElementById('registerUserWizardForm');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    const backBtn = document.getElementById('backBtn');
    const cancelBtn = document.getElementById('cancelBtn');

    // Load roles and projects
    loadRolesAndProjects();

    // Navigation buttons
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            if (validateCurrentStep() && currentStep < totalSteps) {
                currentStep++;
                showStep(currentStep);
            }
        });
    }

    if (submitBtn) {
        submitBtn.addEventListener('click', submitRegistration);
    }

    if (backBtn) {
        backBtn.addEventListener('click', () => {
            currentStep--;
            showStep(currentStep);
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            if (confirm('Are you sure you want to cancel registration?')) {
                form.reset();
                currentStep = 1;
                showStep(1);
            }
        });
    }

    // Show specific step
    function showStep(step) {
        // Hide all steps
        document.querySelectorAll('.wizard-step').forEach(s => {
            s.classList.remove('active');
        });

        // Show current step
        const stepElement = document.querySelector(`.wizard-step[data-step="${step}"]`);
        if (stepElement) {
            stepElement.classList.add('active');
        }

        // Update step indicators
        document.querySelectorAll('.step').forEach((s, idx) => {
            if (idx < step) {
                s.classList.add('active');
            } else {
                s.classList.remove('active');
            }
        });

        // Show/hide navigation buttons
        if (prevBtn) prevBtn.style.display = step === 1 ? 'none' : 'inline-block';
        if (nextBtn) nextBtn.style.display = step === totalSteps ? 'none' : 'inline-block';

        // On confirmation step, populate summary
        if (step === 4) {
            populateConfirmation();
        }
    }

    // Validate current step
    function validateCurrentStep() {
        const stepElement = document.querySelector(`.wizard-step[data-step="${currentStep}"]`);
        if (!stepElement) return true;

        const inputs = stepElement.querySelectorAll('input[required], select[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.classList.add('error');
                showToast(`${input.name} is required`, 'error');
            } else {
                input.classList.remove('error');

                // Email validation
                if (input.type === 'email' && !validateEmail(input.value)) {
                    isValid = false;
                    input.classList.add('error');
                    showToast('Invalid email address', 'error');
                }

                // Password validation
                if (input.type === 'password') {
                    const password = input.value;
                    if (password.length < 8) {
                        isValid = false;
                        input.classList.add('error');
                        showToast('Password must be at least 8 characters', 'error');
                    } else if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                        isValid = false;
                        input.classList.add('error');
                        showToast('Password must include a special character', 'error');
                    }
                }
            }
        });

        return isValid;
    }

    // Validate email
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // Populate confirmation summary
    function populateConfirmation() {
        const formData = new FormData(form);
        const confirmationBody = document.getElementById('confirmationBody');

        const roleSelect = document.getElementById('roleSelect');
        const projectSelect = document.getElementById('projectSelect');

        const roleName = roleSelect.options[roleSelect.selectedIndex]?.text || 'Not Selected';
        const projectName = projectSelect.options[projectSelect.selectedIndex]?.text || 'Not Selected';

        confirmationBody.innerHTML = `
            <div class="confirmation-grid">
                <div class="confirm-item">
                    <strong>First Name:</strong>
                    <span>${escapeHtml(formData.get('fname'))}</span>
                </div>
                <div class="confirm-item">
                    <strong>Last Name:</strong>
                    <span>${escapeHtml(formData.get('lname'))}</span>
                </div>
                <div class="confirm-item">
                    <strong>Email:</strong>
                    <span>${escapeHtml(formData.get('email'))}</span>
                </div>
                <div class="confirm-item">
                    <strong>Phone:</strong>
                    <span>${escapeHtml(formData.get('phone'))}</span>
                </div>
                <div class="confirm-item">
                    <strong>Role:</strong>
                    <span>${escapeHtml(roleName)}</span>
                </div>
                <div class="confirm-item">
                    <strong>Project:</strong>
                    <span>${escapeHtml(projectName)}</span>
                </div>
            </div>
        `;
    }

    // Load roles and projects from API
    function loadRolesAndProjects() {
        // Load roles
        fetch(`${BASE_URL}/api/users/get_roles.php`)
            .then(res => res.json())
            .then(data => {
                const roleSelect = document.getElementById('roleSelect');
                if (data.success && Array.isArray(data.roles)) {
                    data.roles.forEach(role => {
                        const option = document.createElement('option');
                        option.value = role.id;
                        option.textContent = role.name;
                        roleSelect.appendChild(option);
                    });
                }
            })
            .catch(err => {
                console.error('Error loading roles:', err);
                showToast('Failed to load roles', 'error');
            });

        // Load projects
        fetch(`${BASE_URL}/api/users/get_projects.php`)
            .then(res => res.json())
            .then(data => {
                const projectSelect = document.getElementById('projectSelect');
                if (data.success && Array.isArray(data.projects)) {
                    data.projects.forEach(project => {
                        const option = document.createElement('option');
                        option.value = project.project_id;
                        option.textContent = project.project_name;
                        projectSelect.appendChild(option);
                    });
                }
            })
            .catch(err => {
                console.error('Error loading projects:', err);
                showToast('Failed to load projects', 'error');
            });
    }

    // Submit registration
    function submitRegistration() {
        const formData = new FormData(form);
        const data = {};

        formData.forEach((value, key) => {
            data[key] = value;
        });

        // Show loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';

        fetch(`${BASE_URL}/api/auth/register_user_api.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                showToast(response.message || 'User registered successfully!', 'success');
                setTimeout(() => {
                    window.location.href = `${BASE_URL}/?page=user_permissions`;
                }, 2000);
            } else {
                showToast(response.message || 'Registration failed', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '✔ Confirm & Register';
            }
        })
        .catch(err => {
            console.error('Registration error:', err);
            showToast('An error occurred during registration', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '✔ Confirm & Register';
        });
    }

    // Toast notification
    function showToast(message, type = 'info') {
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        toastContainer.appendChild(toast);

        setTimeout(() => toast.remove(), 4000);
    }

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize
    showStep(1);
});
