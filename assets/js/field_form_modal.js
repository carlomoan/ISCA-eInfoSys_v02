document.addEventListener('DOMContentLoaded', function() {
    const openFormBtn = document.getElementById('openFieldFormBtn');
    
    if (!openFormBtn) return;

    openFormBtn.addEventListener('click', function() {
        showFieldFormModal();
    });

    function showFieldFormModal() {
        // Get the form template
        const template = document.getElementById('fieldFormTemplate');
        if (!template) {
            console.error('Field form template not found');
            return;
        }

        // Create modal HTML
        const modalHTML = `
            <div class="wizard-modal-overlay" id="fieldFormModal">
                <div class="wizard-modal-container">
                    <div class="wizard-modal-header">
                        <h3><i class="fas fa-clipboard-list"></i> Add Field Collection Data</h3>
                        <button class="modal-close" aria-label="Close modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="wizard-modal-body">
                        ${template.innerHTML}
                    </div>
                </div>
            </div>
        `;

        // Insert modal
        const existingModal = document.getElementById('fieldFormModal');
        if (existingModal) existingModal.remove();

        document.body.insertAdjacentHTML('beforeend', modalHTML);

        const modal = document.getElementById('fieldFormModal');

        // Close modal handlers
        modal.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', () => {
                if (confirm('Are you sure you want to close? Unsaved data will be lost.')) {
                    modal.remove();
                }
            });
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                if (confirm('Are you sure you want to close? Unsaved data will be lost.')) {
                    modal.remove();
                }
            }
        });

        // ESC key to close
        const escHandler = function(e) {
            if (e.key === 'Escape' && modal) {
                if (confirm('Are you sure you want to close? Unsaved data will be lost.')) {
                    modal.remove();
                    document.removeEventListener('keydown', escHandler);
                }
            }
        };
        document.addEventListener('keydown', escHandler);

        // Initialize the wizard after modal is inserted
        setTimeout(() => {
            initializeWizard();
        }, 100);
    }

    function initializeWizard() {
        let currentStep = 1;
        const totalSteps = 5;

        const nextBtn = document.querySelector('#fieldFormModal #nextBtn');
        const prevBtn = document.querySelector('#fieldFormModal #prevBtn');
        const backBtn = document.querySelector('#fieldFormModal #backBtn');
        const cancelBtn = document.querySelector('#fieldFormModal #cancelBtn');
        const submitBtn = document.querySelector('#fieldFormModal #submitBtn');

        console.log('Initializing wizard...', {nextBtn, prevBtn, currentStep});

        function showStep(step) {
            console.log('Showing step:', step);
            
            // Hide all steps
            const allSteps = document.querySelectorAll('#fieldFormModal .wizard-step');
            allSteps.forEach(s => {
                s.classList.remove('active');
                console.log('Step element:', s, 'data-step:', s.getAttribute('data-step'));
            });
            
            // Show current step
            const currentStepEl = document.querySelector(`#fieldFormModal .wizard-step[data-step="${step}"]`);
            if (currentStepEl) {
                currentStepEl.classList.add('active');
                console.log('Activated step:', step, currentStepEl);
            } else {
                console.error('Could not find step element for step:', step);
            }

            // Update step indicators
            const stepIndicators = document.querySelectorAll('#fieldFormModal .wizard-steps .step');
            stepIndicators.forEach((stepEl, index) => {
                const stepNum = index + 1;
                stepEl.classList.remove('active', 'completed');
                
                if (stepNum < step) {
                    stepEl.classList.add('completed');
                } else if (stepNum === step) {
                    stepEl.classList.add('active');
                }
            });

            // Update buttons
            if (prevBtn) prevBtn.style.display = step === 1 ? 'none' : 'inline-flex';
            if (nextBtn) nextBtn.style.display = step === totalSteps ? 'none' : 'inline-flex';

            // Scroll to top of modal body
            const modalBody = document.querySelector('#fieldFormModal .wizard-modal-body');
            if (modalBody) modalBody.scrollTop = 0;
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                console.log('Next button clicked, current step:', currentStep);
                if (validateStep(currentStep)) {
                    if (currentStep < totalSteps) {
                        currentStep++;
                        showStep(currentStep);
                        
                        // Load confirmation summary on last step
                        if (currentStep === totalSteps) {
                            loadConfirmationSummary();
                        }
                    }
                }
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                console.log('Prev button clicked, current step:', currentStep);
                if (currentStep > 1) {
                    currentStep--;
                    showStep(currentStep);
                }
            });
        }

        if (backBtn) {
            backBtn.addEventListener('click', () => {
                console.log('Back button clicked, current step:', currentStep);
                if (currentStep > 1) {
                    currentStep--;
                    showStep(currentStep);
                }
            });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                const modal = document.getElementById('fieldFormModal');
                if (modal && confirm('Are you sure you want to cancel? All data will be lost.')) {
                    modal.remove();
                }
            });
        }

        // Initialize first step
        console.log('Initializing first step...');
        showStep(currentStep);
    }

    function validateStep(step) {
        const currentStepEl = document.querySelector(`#fieldFormModal .wizard-step[data-step="${step}"]`);
        if (!currentStepEl) return true;

        const requiredInputs = currentStepEl.querySelectorAll('[required]');
        let isValid = true;

        requiredInputs.forEach(input => {
            if (!input.value || input.value.trim() === '') {
                isValid = false;
                input.style.borderColor = '#dc3545';
                
                // Remove error styling on input
                input.addEventListener('input', function() {
                    this.style.borderColor = '';
                }, { once: true });
            }
        });

        if (!isValid) {
            alert('Please fill in all required fields before continuing.');
        }

        return isValid;
    }

    function loadConfirmationSummary() {
        const form = document.querySelector('#fieldFormModal #fieldWizardForm');
        if (!form) return;

        const formData = new FormData(form);
        const confirmationBody = document.querySelector('#fieldFormModal #confirmationBody');
        
        if (!confirmationBody) return;

        let tableHTML = '<table>';
        
        const fieldLabels = {
            'fldrecname': 'Field Recorder',
            'ento_fld_frm_title': 'Form Title',
            'deviceid': 'Device Code',
            'start': 'Starting Time',
            'clstname': 'Cluster Name',
            'clstid': 'Cluster Code',
            'round': 'Round',
            'clsttype_lst': 'Cluster Type',
            'field_coll_date': 'Field Collection Date',
            'hhcode': 'Household Number',
            'hhname': 'Household Head Name',
            'ddrln': 'Did it rain last night?',
            'aninsln': 'Any insecticide used?',
            'ddltwrk': 'Did light trap work?',
            'lighttrapid': 'Light Trap Number',
            'collectionbgid': 'Collection Bag Number',
            'instanceID': 'Instance ID',
            'ddltwrk_gcomment': 'Comment'
        };

        for (let [key, value] of formData.entries()) {
            if (key !== 'end' && value && fieldLabels[key]) {
                tableHTML += `
                    <tr>
                        <td>${fieldLabels[key]}</td>
                        <td><strong>${value}</strong></td>
                    </tr>
                `;
            }
        }

        tableHTML += '</table>';
        confirmationBody.innerHTML = tableHTML;
    }
});
