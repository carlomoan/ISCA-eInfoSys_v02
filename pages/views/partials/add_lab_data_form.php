<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../../helpers/permission_helper.php';

if (!checkPermission('add_lab_data')) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <h2>Add Lab Data</h2>
    </div>

    <div class="content-body">
        <?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

// Hakikisha user ana ruhusa
if (!checkPermission('add_field_data')) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}
?>

<?php include ROOT_PATH . "pages/layout/header.php"; ?>
<?php include ROOT_PATH . "pages/layout/sidebar.php"; ?>

<div class="main-content">
  <h2 class="text-xl font-bold mb-4">Add Field Data</h2>

  <!-- Wizard Progress -->
  <div id="wizard-progress" class="flex items-center justify-between mb-6">
    <div class="step active">
      <div class="dot">1</div>
      <p>Cluster</p>
    </div>
    <div class="line"></div>
    <div class="step">
      <div class="dot">2</div>
      <p>Household</p>
    </div>
    <div class="line"></div>
    <div class="step">
      <div class="dot">3</div>
      <p>Data Entry</p>
    </div>
  </div>

  <!-- Step Forms -->
  <form id="fieldDataForm" class="wizard-form">
    <!-- Step 1 -->
    <div class="wizard-step active" data-step="1">
      <label>Select Cluster:</label>
      <select id="clusterSelect" name="cluster_id" required class="input">
        <option value="">-- Choose Cluster --</option>
      </select>
      <button type="button" class="btn-next">Next</button>
    </div>

    <!-- Step 2 -->
    <div class="wizard-step" data-step="2">
      <label>Select Household (hhcode):</label>
      <select id="householdSelect" name="hhcode" required class="input">
        <option value="">-- Choose Household --</option>
      </select>
      <div class="flex justify-between mt-4">
        <button type="button" class="btn-prev">Back</button>
        <button type="button" class="btn-next">Next</button>
      </div>
    </div>

    <!-- Step 3 -->
    <div class="wizard-step" data-step="3">
      <label>Instance ID:</label>
      <input type="text" id="instanceID" name="instanceID" readonly class="input faint">

      <label>Collection BG ID:</label>
      <input type="number" name="collectionbgid" required class="input">

      <!-- Add more form fields here -->
      
      <div class="flex justify-between mt-4">
        <button type="button" class="btn-prev">Back</button>
        <button type="submit" class="btn-submit">Submit</button>
      </div>
    </div>
  </form>
</div>

<?php include ROOT_PATH . "pages/layout/footer.php"; ?>

<!-- JS -->
<script src="<?php echo BASE_URL; ?>assets/js/add_field_data_form.js"></script>

<style>
/* Wizard style */
#wizard-progress {
  display: flex;
  align-items: center;
}
#wizard-progress .step {
  text-align: center;
  flex: 1;
}
#wizard-progress .dot {
  width: 30px; height: 30px;
  border-radius: 50%;
  border: 2px solid #999;
  display: flex; align-items: center; justify-content: center;
  margin: auto;
  font-weight: bold;
}
#wizard-progress .step.active .dot {
  background: #4caf50;
  color: #fff;
  border-color: #4caf50;
}
#wizard-progress .line {
  flex: 1;
  height: 2px;
  background: #999;
}
#wizard-progress .step.completed .dot {
  background: #4caf50;
  color: #fff;
}
.input {
  display: block; margin: 8px 0; padding: 6px; width: 100%;
}
.faint {
  background-color: #f3f3f3; color: #666;
}
.btn-next, .btn-prev, .btn-submit {
  padding: 6px 12px; border-radius: 6px; margin-top: 10px;
}
</style>

    </div>
</div>
