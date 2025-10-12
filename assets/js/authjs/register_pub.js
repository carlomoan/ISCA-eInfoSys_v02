document.addEventListener('DOMContentLoaded', function () {

    // ===== TOAST SYSTEM =====
    let toastContainer = document.getElementById("toast-container");
    if (!toastContainer) {
        toastContainer = document.createElement("div");
        toastContainer.id = "toast-container";
        document.body.appendChild(toastContainer);
    }

    function showToast(msg, type = "info", duration = 4000) {
        const t = document.createElement("div");
        t.className = `toast ${type}`;
        t.innerText = msg;
        toastContainer.appendChild(t);
        setTimeout(() => t.classList.add("visible"), 50);
        setTimeout(() => {
            t.classList.remove("visible");
            setTimeout(() => t.remove(), 500);
        }, duration);
    }

    // ===== ELEMENTS =====
    const form = document.getElementById('publicRegisterWizardForm');
    if (!form) return;

    const steps = form.querySelectorAll('.wizard-step');
    const indicators = document.querySelectorAll('.wizard-steps .step');
    const prevBtn = form.querySelector('#prevBtn');
    const nextBtn = form.querySelector('#nextBtn');
    const backBtn = form.querySelector('#backBtn');
    const cancelBtn = form.querySelector('#cancelBtn');
    const submitBtn = form.querySelector('#submitBtn');
    const honeypot = form.querySelector('input[name="website"]');

    const emailInput = form.querySelector('input[name="email"]');
    const phoneInput = form.querySelector('input[name="phone"]');
    const passwordInput = form.querySelector('input[name="password"]');
    const confirmInput = form.querySelector('input[name="confirm_password"]');

    // ===== INDICATORS & HINTS =====
    function createIndicatorAndHint(afterEl, hintText) {
        const wrapper = document.createElement('div');
        wrapper.style.marginTop = '2px';
        wrapper.style.fontSize = '0.85em';
        wrapper.style.color = '#555';
        afterEl.parentNode.insertBefore(wrapper, afterEl.nextSibling);

        const indicator = document.createElement('span');
        indicator.style.fontWeight = 'bold';
        indicator.style.marginRight = '8px';
        wrapper.appendChild(indicator);

        const hint = document.createElement('span');
        hint.innerText = hintText;
        wrapper.appendChild(hint);

        return indicator;
    }

    const emailIndicator = createIndicatorAndHint(emailInput, 'Must contain "@"');
    const phoneIndicator = createIndicatorAndHint(phoneInput, 'Format: +255xxxxxxxxx');

    // ===== PASSWORD STRENGTH BAR =====
    const passwordIndicator = createIndicatorAndHint(passwordInput, 'Min 8 chars, 1 special char');
    const progressWrapper = document.createElement('div');
    progressWrapper.classList.add('password-strength-wrapper');

    const progressBar = document.createElement('div');
    progressBar.classList.add('password-strength-bar');

    progressWrapper.appendChild(progressBar);
    passwordInput.parentNode.insertBefore(progressWrapper, passwordInput.nextSibling.nextSibling);

    // ===== WIZARD STATE =====
    let currentStep = 1;
    const totalSteps = steps.length;

    // ===== FUNCTIONS =====
    function showStep(step) {
        steps.forEach(s => s.classList.remove('active'));
        indicators.forEach(i => i.classList.remove('active'));

        const activeStep = form.querySelector(`.wizard-step[data-step="${step}"]`);
        const activeInd = document.querySelector(`.wizard-steps .step[data-step="${step}"]`);

        if (activeStep) activeStep.classList.add('active');
        if (activeInd) activeInd.classList.add('active');

        prevBtn.style.display = step === 1 ? "none" : "inline-block";
        nextBtn.style.display = step === totalSteps ? "none" : "inline-block";
        submitBtn.style.display = step === totalSteps ? "inline-block" : "none";

        if (step === totalSteps) fillConfirmation(collectData());
    }

    function validateStep(step) {
        const current = form.querySelector(`.wizard-step[data-step="${step}"]`);
        const inputs = current.querySelectorAll("input[required], select[required]");

        // Check all required inputs
        for (let inp of inputs) {
            if (!inp.value.trim()) {
                inp.focus();
                showToast("Please fill all required fields", "error");
                return false;
            }
        }

        // Step 2: Email & Phone validation
        if (step === 2) {
            const email = emailInput.value.trim();
            const phone = phoneInput.value.trim();
            if (!email.includes('@')) { showToast("Email must contain @", "error"); return false; }
            if (!/^\+255\d{9}$/.test(phone)) { showToast("Phone must start with +255 followed by 9 digits", "error"); return false; }
        }

        // Step 3: Password validation
        if (step === 3) {
            const pass = passwordInput.value;
            const confirm = confirmInput.value;
            if (pass !== confirm) { showToast("Passwords do not match", "error"); return false; }
            if (pass.length < 8 || !/[!@#$%^&*(),.?":{}|<>]/.test(pass)) {
                showToast("Password must be at least 8 chars with special character", "error");
                return false;
            }
        }
        return true;
    }

    function markCompleted(step) {
        const s = document.querySelector(`.wizard-steps .step[data-step="${step}"]`);
        if (s) {
            s.classList.add("completed");
            const dot = s.querySelector('.step-dot');
            if (dot) dot.innerHTML = "✔";
        }
    }

    function collectData() {
        const data = {};
        form.querySelectorAll("input, select").forEach(inp => {
            if (inp.name && inp.type !== "hidden" && inp.name !== "confirm_password" && inp.name !== "website") {
                data[inp.name] = inp.value.trim();
            }
        });
        return data;
    }

    function fillConfirmation(data) {
        const c = document.getElementById('confirmationBody');
        if (!c) return;
        let html = '<table class="table"><tbody>';
        for (let k in data) {
            html += `<tr><td><strong>${k}</strong></td><td>${data[k]}</td></tr>`;
        }
        html += '</tbody></table>';
        c.innerHTML = html;
    }

    // ===== REALTIME VALIDATION =====
    emailInput?.addEventListener('input', () => {
        emailIndicator.innerText = emailInput.value.includes('@') ? '✅' : '❌';
    });

    phoneInput?.addEventListener('input', () => {
        phoneIndicator.innerText = /^\+255\d{9}$/.test(phoneInput.value) ? '✅' : '❌';
    });

    passwordInput?.addEventListener('input', () => {
        const val = passwordInput.value;
        let strength = 0;
        if (val.length >= 8) strength++;
        if (/[!@#$%^&*(),.?":{}|<>]/.test(val)) strength++;
        if (val.length >= 12) strength++;

        if (strength === 0) {
            passwordIndicator.innerText = "Strength: Very Weak";
            progressBar.style.width = "10%";
            progressBar.style.background = "red";
        } else if (strength === 1) {
            passwordIndicator.innerText = "Strength: Weak";
            progressBar.style.width = "40%";
            progressBar.style.background = "orange";
        } else if (strength === 2) {
            passwordIndicator.innerText = "Strength: Good";
            progressBar.style.width = "70%";
            progressBar.style.background = "blue";
        } else {
            passwordIndicator.innerText = "Strength: Strong";
            progressBar.style.width = "100%";
            progressBar.style.background = "green";
        }
    });

    // ===== WIZARD NAVIGATION =====
    nextBtn?.addEventListener('click', () => {
        if(validateStep(currentStep)) {
            markCompleted(currentStep);
            currentStep++;
            showStep(currentStep);
        }
    });

    prevBtn?.addEventListener('click', () => {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
        }
    });

    backBtn?.addEventListener('click', () => {
        currentStep = 1;
        showStep(currentStep);
    });

    cancelBtn?.addEventListener('click', () => location.reload());

    // ===== SUBMIT FORM =====
    submitBtn?.addEventListener('click', async () => {
        if (!validateStep(currentStep)) return;
        if (honeypot?.value) { showToast("Bot detected", "error"); return; }

        const data = collectData();

        try {
            const res = await fetch(BASE_URL + '/api/auth/register_user_pub_api.php', {
                method: "POST",
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const r = await res.json();
            if (r.success) {
                showToast(r.message, "success");
                window.location.href = BASE_URL + '/api/auth/awaiting_approval.php';
            } else {
                showToast(r.message, "error");
            }
        } catch (err) {
            console.error(err);
            showToast("Error submitting form", "error");
        }
    });

    // ===== INITIALIZE WIZARD =====
    showStep(currentStep);

});
