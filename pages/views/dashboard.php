<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'includes/header.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

if(!checkPermission('view_dashboard')){
    echo '<div class="no-access">Access Denied. You do not have permission to view the dashboard.</div>';
    require_once ROOT_PATH . 'includes/footer.php';
    exit;
}

// Fetch projects assigned to user
$userId = $_SESSION['user_id'] ?? 0;
$projects = [];
try{
    $stmt = $pdo->prepare("
        SELECT p.project_id, p.project_name, p.principal_investigator, 
               p.project_current_stage, p.project_status
        FROM user_projects dp
        INNER JOIN projects p ON dp.project_id = p.project_id
        WHERE dp.user_id = :user_id
    ");
    $stmt->execute([':user_id'=>$userId]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
}catch(PDOException $e){
    echo "<div class='error'>Error fetching projects: ".htmlspecialchars($e->getMessage())."</div>";
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">

<div class="container-fluid">
  <div class="breadcrumb my-2"><i class="fas fa-home"></i> &nbsp; Dashboard</div>

  <!-- Row 1: Project Details -->
  <?php if(!$projects): ?>
    <div class='no-project'>
        <h4><strong>Project Dashboard</strong></h4>
        <p>You are not assigned to any project.</p>
    </div>
  <?php else: ?>
    <?php foreach($projects as $project): ?>
      <h4><strong>Project name: <?= htmlspecialchars($project['project_name']) ?></strong></h4>
      <div class="project-meta mb-2">
        <span><strong>Principal Investigator:</strong> <?= htmlspecialchars($project['principal_investigator']) ?></span><br>
        <span><strong>Project Status:</strong> <?= nl2br(htmlspecialchars($project['project_status'])) ?></span><br>
        <span><strong>Current Stage:</strong> <?= nl2br(htmlspecialchars($project['project_current_stage'])) ?></span>
      </div>
      <hr>
    <?php endforeach; ?>
  <?php endif; ?>

<!-- Row 2: Summary Cards with Icon Background -->
<div class="row summary-cards g-2 mb-3">
  <div class="col">
    <div class="card d-flex">
      <div class="icon-container bg-primary text-white">
        <i class="bi bi-diagram-3"></i>
      </div>
      <div class="card-text flex-grow-1">
        <h6>Total Clusters</h6>
        <p id="totalClusters">0</p>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card d-flex">
      <div class="icon-container bg-success text-white">
        <i class="bi bi-house"></i>
      </div>
      <div class="card-text flex-grow-1">
        <h6>Total Households</h6>
        <p id="totalHouseholds">0</p>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card d-flex">
      <div class="icon-container bg-warning text-white">
        <i class="bi bi-clock-history"></i>
      </div>
      <div class="card-text flex-grow-1">
        <h6>Current Round</h6>
        <p id="currentRound">0</p>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card d-flex">
      <div class="icon-container bg-info text-white">
        <i class="bi bi-file-earmark-text"></i>
      </div>
      <div class="card-text flex-grow-1">
        <h6>Total Records (M+F)</h6>
        <p id="totalRecords">0</p>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card d-flex">
      <div class="icon-container bg-danger text-white">
        <i class="bi bi-bug"></i>
      </div>
      <div class="card-text flex-grow-1">
        <h6>Total Mosquitoes (Female)</h6>
        <p id="totalMosquitoes">0</p>
      </div>
    </div>
  </div>
</div>


  <!-- Row 3: Charts -->
  <div class="row g-2 mb-2">
    <div class="col-md-9">
      <div class="card p-3">
        <h6>Trend per Round (Female total)</h6>
        <canvas id="lineTrending" style="height:250px;"></canvas>
      </div>
    </div>
    
    <div class="col-md-3">
      <div class="card p-3">
        <h6>Species Distribution (Female)</h6>
        <canvas id="pieChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Row 4: Map + Table -->
  <div class="row g-2 mb-2">
    <div class="col-md-9">
      <div class="card p-3">
        <h6>Map: Household Locations</h6>
        <div id="map" style="height:400px;"></div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card p-3">
        <h6>Clusters & Households</h6>
        <table id="clusterTable" class="display compact table table-sm table-hover w-100">
          <thead class="table-light">
            <tr>
              <th>Cluster</th>
              <th>Total Female</th>
              <th>Households</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/gh/elfalem/Leaflet.curve/leaflet.curve.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.curve/0.9.0/leaflet.curve.min.js"></script>


<script src="<?= BASE_URL ?>/assets/js/dashboard_current.js"></script>
<script src="<?= BASE_URL ?>/assets/js/dashboard_map.js"></script>
