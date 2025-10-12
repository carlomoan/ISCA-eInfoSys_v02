
document.addEventListener('DOMContentLoaded', function () {
    // ===== Toast System =====
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
        setTimeout(() => { t.classList.remove("visible"); setTimeout(() => t.remove(), 500); }, duration);
    }

    // ===== Elements =====
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

    // ===== Create indicators and hints =====
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
    const passwordIndicator = createIndicatorAndHint(passwordInput, 'Min 8 chars, 1 special char');

    let currentStep = 1;
    const totalSteps = steps.length;

    // ===== Functions =====
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
        for (let inp of inputs) {
            if (!inp.value.trim()) {
                inp.focus();
                showToast("Please fill all required fields", "error");
                return false;
            }
        }

        if(step === 2){ // Email & Phone validation on step 2
            const email = emailInput.value.trim();
            const phone = phoneInput.value.trim();
            if (!email.includes('@')) { showToast("Email must contain @", "error"); return false; }
            if (!/^\+255\d{9}$/.test(phone)) { showToast("Phone must start with +255 followed by 9 digits", "error"); return false; }
        }

        if (step === 3) { // Password confirmation
            const pass = passwordInput.value;
            const confirm = confirmInput.value;
            if (pass !== confirm) { showToast("Passwords do not match", "error"); return false; }
            if(pass.length < 8 || !/[!@#$%^&*(),.?":{}|<>]/.test(pass)) {
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
        for (let k in data) { html += `<tr><td><strong>${k}</strong></td><td>${data[k]}</td></tr>`; }
        html += '</tbody></table>';
        c.innerHTML = html;
    }

    // ===== Realtime validation & strength =====
    emailInput?.addEventListener('input', () => {
        emailIndicator.innerText = emailInput.value.includes('@') ? '✅' : '❌';
    });
    phoneInput?.addEventListener('input', () => {
        phoneIndicator.innerText = /^\+255\d{9}$/.test(phoneInput.value) ? '✅' : '❌';
    });
    passwordInput?.addEventListener('input', () => {
        const val = passwordInput.value;
        let strength = "Weak";
        let color = "red";
        if(val.length >= 8 && /[!@#$%^&*(),.?":{}|<>]/.test(val)) { strength = "Good"; color="orange"; }
        if(val.length >= 12 && /[!@#$%^&*(),.?":{}|<>]/.test(val)) { strength = "Strong"; color="green"; }
        passwordIndicator.innerText = `Strength: ${strength}`;
        passwordIndicator.style.color = color;
    });

    // ===== Navigation =====
    nextBtn?.addEventListener('click', () => { if(validateStep(currentStep)) { markCompleted(currentStep); currentStep++; showStep(currentStep); } });
    prevBtn?.addEventListener('click', () => { if (currentStep > 1) { currentStep--; showStep(currentStep); } });
    backBtn?.addEventListener('click', () => { currentStep = 1; showStep(currentStep); });
    cancelBtn?.addEventListener('click', () => location.reload());

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
            } else { showToast(r.message, "error"); }
        } catch (err) {
            console.error(err);
            showToast("Error submitting form", "error");
        }
    });

    // ===== Initialize =====
    showStep(currentStep);
});
