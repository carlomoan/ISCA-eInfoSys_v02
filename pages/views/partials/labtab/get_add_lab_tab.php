 <h3>Add Sorting Data</h3>
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
    <form id="labWizardForm">

        <!-- Step 1: General Lab Info -->
        <div class="wizard-step active" data-step="1">
            <div class="form-group">
                <label>Laboratory Technician</label>
                <input type="text" name="lab_tech_name" readonly value="<?= htmlspecialchars($full_Name) ?>">
                <input type="hidden" name="lab_tech_id" value="<?= $_SESSION['user_id'] ?? '' ?>">
                
            </div>
            <div class="form-group">
                <label>Lab Date</label>
                <input type="date" name="lab_date" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label>Form Title</label>
                <input type="text" name="ento_lab_frm_title" value="ISCA_desk_lab_form" required>
            </div>
            <div class="form-group">
                <label>Device Code</label>
                <input type="text" name="deviceid" readonly>
            </div>
            <div class="form-group">
                <label>Start Time</label>
                <input type="datetime-local" name="start" readonly>
            </div>
            <div class="form-group">
              <label>Instance ID</label>
              <input type="text" name="instanceID" readonly>
            </div>
            <input type="hidden" name="end" value="<?= date('Y-m-d H:i:s') ?>">
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

<!-- Step 4: Species Data -->
<div class="wizard-step" data-step="4">

  <div class="row">
    <!-- Anopheles Gambiae -->
    <div class="col-md-4">
      <h5>Anopheles Gambiae</h5>
      <div class="species-row" data-species="Anopheles Gambiae">
        <div class="left-column">
          <label>Male: </label><input type="number" class="male" name="male_ag" placeholder="Male">
          <label>Female: </label><input type="number" class="female" name="female_ag" placeholder="Female">
          <label>Grand Total: </label><input type="number" class="total" name="total_ag" placeholder="Total" readonly>
        </div>
        <div class="right-column">
          <label>Fed: </label><input type="number" class="fed" name="fed_ag" placeholder="Fed">
          <label>Unfed: </label><input type="number" class="unfed" name="unfed_ag" placeholder="Unfed">
          <label>Gravid: </label><input type="number" class="gravid" name="gravid_ag" placeholder="Gravid">
          <label>Semi-Gravid: </label><input type="number" class="semigravid" name="semi_gravid_ag" placeholder="Semi-Gravid">
        </div>
      </div>
    </div>

    <!-- Anopheles Funestus -->
    <div class="col-md-4">
      <h5>Anopheles Funestus</h5>
      <div class="species-row" data-species="Anopheles Funestus">
        <div class="left-column">
          <label>Male: </label><input type="number" class="male" name="male_af" placeholder="Male">
          <label>Female: </label><input type="number" class="female" name="female_af" placeholder="Female">
          <label>Grand Total: </label><input type="number" class="total" name="total_af" placeholder="Total" readonly>
        </div>
        <div class="right-column">
          <label>Fed: </label><input type="number" class="fed" name="fed_af" placeholder="Fed">
          <label>Unfed: </label><input type="number" class="unfed" name="unfed_af" placeholder="Unfed">
          <label>Gravid: </label><input type="number" class="gravid" name="gravid_af" placeholder="Gravid">
          <label>Semi-Gravid: </label><input type="number" class="semigravid" name="semi_gravid_af" placeholder="Semi-Gravid">
        </div>
      </div>
    </div>

    <!-- Other Anopheles -->
    <div class="col-md-4">
      <h5>Other Anopheles</h5>
      <div class="species-row" data-species="Other Anopheles">
        <div class="left-column">
          <label>Male: </label><input type="number" class="male" name="male_oan" placeholder="Male">
          <label>Female: </label><input type="number" class="female" name="female_oan" placeholder="Female">
          <label>Grand Total: </label><input type="number" class="total" name="total_oan" placeholder="Total" readonly>
        </div>
        <div class="right-column">
          <label>Fed: </label><input type="number" class="fed" name="fed_oan" placeholder="Fed">
          <label>Unfed: </label><input type="number" class="unfed" name="unfed_oan" placeholder="Unfed">
          <label>Gravid: </label><input type="number" class="gravid" name="gravid_oan" placeholder="Gravid">
          <label>Semi-Gravid: </label><input type="number" class="semigravid" name="semi_gravid_oan" placeholder="Semi-Gravid">
        </div>
      </div>
    </div>
  </div>

  <div class="row mt-3">
    <!-- Culex -->
    <div class="col-md-4">
      <h5>Culex</h5>
      <div class="species-row" data-species="Culex">
        <div class="left-column">
          <label>Male: </label><input type="number" class="male" name="male_cx" placeholder="Male">
          <label>Female: </label><input type="number" class="female" name="female_cx" placeholder="Female">
          <label>Grand Total: </label><input type="number" class="total" name="total_cx" placeholder="Total" readonly>
        </div>
        <div class="right-column">
          <label>Fed: </label><input type="number" class="fed" name="fef_cx" placeholder="Fed">
          <label>Unfed: </label><input type="number" class="unfed" name="unfed_cx" placeholder="Unfed">
          <label>Gravid: </label><input type="number" class="gravid" name="gravid_cx" placeholder="Gravid">
          <label>Semi-Gravid: </label><input type="number" class="semigravid" name="semigravid_cx" placeholder="Semi-Gravid">
        </div>
      </div>
    </div>

    <!-- Other Culicine -->
    <div class="col-md-4">
      <h5>Other Culicine</h5>
      <div class="species-row" data-species="Other Culicine">
        <div class="left-column">
          <label>Male: </label><input type="number" class="male" name="male_oc" placeholder="Male">
          <label>Female: </label><input type="number" class="female" name="female_oc" placeholder="Female">
          <label>Grand Total: </label><input type="number" class="total" name="total_oc" placeholder="Total" readonly>
        </div>
        <div class="right-column">
          <label>Fed: </label><input type="number" class="fed" placeholder="-" readonly>
          <label>Unfed: </label><input type="number" class="unfed" placeholder="-" readonly>
          <label>Gravid: </label><input type="number" class="gravid" placeholder="-" readonly>
          <label>Semi-Gravid: </label><input type="number" class="semigravid" placeholder="-" readonly>
        </div>
      </div>
    </div>

    <!-- Aedes -->
    <div class="col-md-4">
      <h5>Aedes</h5>
      <div class="species-row" data-species="Aedes">
        <div class="left-column">
          <label>Male: </label><input type="number" class="male" name="male_ad" placeholder="Male">
          <label>Female: </label><input type="number" class="female" name="female_ad" placeholder="Female">
          <label>Grand Total: </label><input type="number" class="total" name="total_ad" placeholder="Total" readonly>
        </div>
        <div class="right-column">
          <label>Fed: </label><input type="number" class="fed" placeholder="-" readonly>
          <label>Unfed: </label><input type="number" class="unfed" placeholder="-" readonly>
          <label>Gravid: </label><input type="number" class="gravid" placeholder="-" readonly>
          <label>Semi-Gravid: </label><input type="number" class="semigravid" placeholder="-" readonly>
        </div>
      </div>
    </div>
  </div>

  <div class="row mt-3">
    <div class="col-md-12">
      <strong>Grand Total: <span id="grandTotal">0</span></strong>
    </div>
  </div>

</div>








        <!-- Step 5: Confirmation -->
        <div class="wizard-step" data-step="5">
            <h3>Confirmation</h3>
           <div id="labConfirmationBody">
                <!-- JS will populate summary here -->
            </div>
            <div class="actions">
                <button type="button" id="backBtn">← Back</button>
                <button type="button" id="cancelBtn">✖ Cancel</button>
                <button type="button" id="submitBtn">✔ Confirm & Submit</button>
                <button type="button" id="lab-export-pdf">🖨 Export PDF</button>
            </div>
        </div>

        <!-- Wizard Navigation -->
        <div class="actions">
            <button type="button" id="prevBtn">Previous</button>
            <button type="button" id="nextBtn">Next</button>
        </div>
        <!-- WITHIN A FORM -->

    </form>
</div>






