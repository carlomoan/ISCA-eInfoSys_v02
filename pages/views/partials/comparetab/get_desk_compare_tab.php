 <h3>Add Field Data</h3>
    <div id="field-wizard-container">

        <!-- Wizard Step Indicator -->
        <div class="wizard-steps">
            <?php for($i=1; $i<=5; $i++): ?>
                <div class="step <?= $i===1 ? 'active' : '' ?>" data-step="<?= $i ?>">
                    <span class="step-dot"><?= $i ?></span>
                    <span class="step-label">
                        <?php 
                        switch($i){
                            case 1: echo 'Meta'; break;
                            case 2: echo 'Cluster & Round'; break;
                            case 3: echo 'Household'; break;
                            case 4: echo 'Other Info'; break;
                            case 5: echo 'Confirm'; break;
                        }
                        ?>
                    </span>
                </div>
            <?php endfor; ?>
        </div>

        <!-- Wizard Form -->
        <form id="fieldWizardForm">

            <!-- Step 1: Meta -->
            <div class="wizard-step active" data-step="1">
                <div class="form-group"><label>Field Recorder</label><input type="text" name="fldrecname" value="<?= htmlspecialchars($full_Name) ?>" readonly></div>
                <div class="form-group"><label>Form Title</label><input type="text" name="ento_fld_frm_title" value="ISCA_desk_field_form"></div>
                <div class="form-group"><label>Device Code</label><input type="text" name="deviceid" readonly></div>
                <div class="form-group"><label>Starting Time</label><input type="datetime-local" name="start" readonly></div>
                <div class="form-group"><label><input type="datetime-local" name="end" value="<?= date('Y-m-d\ H:i:s') ?>" hidden></div>
            </div>

            <!-- Step 2: Cluster & Round -->
            <div class="wizard-step" data-step="2">
                <div class="form-group"><label>Cluster Name</label>
                    <select name="clstname" id="clstname" required><option value="">--Select Cluster--</option></select>
                </div>
                <div class="form-group"><label>Cluster Code</label><input type="text" name="clstid" id="clstid" readonly></div>
                <div class="form-group"><label>Round</label><input type="number" name="round" id="round" readonly></div>
                <div class="form-group"><label>Current Cluster Type</label>
                    <select name="clsttype_lst" required>
                        <option value="">--Select--</option>
                        <option value="baseline">Baseline</option>
                        <option value="control">Control</option>
                        <option value="treatment">Treatment</option>
                    </select>
                </div>
                <div class="form-group"><label>Field Collection Date</label>
                    <input type="date" name="field_coll_date" id="field_coll_date" value="<?= date('Y-m-d') ?>" readonly>
                </div>
                <input type="hidden" id="user_role" name="user_role" value="<?= htmlspecialchars(ucfirst($_SESSION['role_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <!-- Step 3: Household -->
            <div class="wizard-step" data-step="3">
                <div class="form-group"><label>Household Number</label>
                    <select name="hhcode" id="hhcode" required><option value="">--Select Household--</option></select>
                </div>
                <div class="form-group"><label>Household Head Name</label><input type="text" name="hhname" id="hhname" readonly></div>
            </div>

            <!-- Step 4: Other Info -->
            <div class="wizard-step" data-step="4">
                <div class="radio-grid">
                    <div class="radio-item">
                        <label>Did it rain last night?</label>
                        <div class="radio-group">
                            <label><input type="radio" name="ddrln" value="YES"> YES</label>
                            <label><input type="radio" name="ddrln" value="NO"> NO</label>
                        </div>
                    </div>
                    <div class="radio-item">
                        <label>Any insecticide used inside the house last night?</label>
                        <div class="radio-group">
                            <label><input type="radio" name="aninsln" value="YES"> YES</label>
                            <label><input type="radio" name="aninsln" value="NO"> NO</label>
                        </div>
                    </div>
                    <div class="radio-item">
                        <label>Did the light trap work through the night?</label>
                        <div class="radio-group">
                            <label><input type="radio" name="ddltwrk" value="YES"> YES</label>
                            <label><input type="radio" name="ddltwrk" value="NO"> NO</label>
                        </div>
                    </div>
                </div>

                <div class="radio-grid" style="margin-top:15px;">
                    <div class="radio-item"><label>Light Trap Number</label><input type="number" name="lighttrapid"></div>
                    <div class="radio-item"><label>Collection Bag Number</label><input type="number" name="collectionbgid"></div>
                    <div class="radio-item"><label>Instance ID</label><input type="text" name="instanceID" readonly></div>
                </div>

                <div class="radio-item" style="margin-top:15px;">
                    <label>Comment</label>
                    <textarea name="ddltwrk_gcomment"></textarea>
                </div>
            </div>


<!-- Step 5: Confirmation -->
<div class="wizard-step" data-step="5">
    <div class="confirmation-form">
        <h2>Confirmation</h2>
        <div id="confirmationBody">
            <!-- JS itajaza summary table hapa -->
        </div>
        <div class="form-footer">
            <button type="button" id="backBtn">← Back</button>
            <button type="button" id="cancelBtn">✖ Cancel</button>
            <button type="button" id="submitBtn">✔ Confirm & Submit</button>
            <button id="export-pdf" class="btn btn-dark">🖨 Export PDF</button>


        </div>
    </div>
</div>



            <!-- Wizard Navigation -->
            <div class="wizard-buttons">
                <button type="button" id="prevBtn">Previous</button>
                <button type="button" id="nextBtn">Next</button>
            </div>

        </form>
    </div>
   