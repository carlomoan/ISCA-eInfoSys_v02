
<?php
// dashboard.php - full improved version
require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'includes/header.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

if (!checkPermission('view_dashboard')) {
    echo '<div class="no-access">Access Denied. You do not have permission to view the dashboard.</div>';
    require_once ROOT_PATH . "includes/footer.php";
    exit;
}

// ---- Config: species definitions (mapping short -> display name & feeding columns) ----
$speciesDefs = [
    'ag' => [
        'label' => 'An. gambiae',
        'fed' => 'fed_ag', 'unfed' => 'unfed_ag', 'gravid' => 'gravid_ag', 'semi_gravid' => 'semi_gravid_ag',
        'female_col' => 'female_ag'
    ],
    'af' => [
        'label' => 'An. funestus',
        'fed' => 'fed_af', 'unfed' => 'unfed_af', 'gravid' => 'gravid_af', 'semi_gravid' => 'semi_gravid_af',
        'female_col' => 'female_af'
    ],
    'oan' => [
        'label' => 'Other Anopheles',
        'fed' => 'fed_oan', 'unfed' => 'unfed_oan', 'gravid' => 'gravid_oan', 'semi_gravid' => 'semi_gravid_oan',
        'female_col' => 'female_oan'
    ],
    'culex' => [
        'label' => 'Culex',
        'fed' => 'fed_culex', 'unfed' => 'unfed_culex', 'gravid' => 'gravid_culex', 'semi_gravid' => 'semi_gravid_culex',
        'female_col' => 'female_culex'
    ],
    'other_culex' => [
        'label' => 'Other Culex',
        // view doesn't have fed_other_culex etc in dump — use zeros for feeding states; female column exists as female_other_culex
        'fed' => null, 'unfed' => null, 'gravid' => null, 'semi_gravid' => null,
        'female_col' => 'female_other_culex'
    ],
    'aedes' => [
        'label' => 'Aedes',
        // view has male_aedes/female_aedes but no fed/unfed/gravid columns — use zeros for feeding states
        'fed' => null, 'unfed' => null, 'gravid' => null, 'semi_gravid' => null,
        'female_col' => 'female_aedes'
    ],
];

// ---- 1) SUMMARY CARDS: use SQL aggregations ----
// Total clusters (clusters table)
$total_clusters = (int)$pdo->query("SELECT COUNT(*) FROM clusters")->fetchColumn();

// Total households (households table)
$total_households = (int)$pdo->query("SELECT COUNT(*) FROM households")->fetchColumn();

// Latest round (max round from merged view)
$total_rounds = (int)$pdo->query("SELECT COALESCE(MAX(`round`),0) FROM vw_merged_field_lab_data")->fetchColumn();

// Total records (male + female across species) - use COALESCE to avoid nulls
$total_records_sql = "
    SELECT COALESCE(SUM(
        COALESCE(male_ag,0)+COALESCE(female_ag,0)
      + COALESCE(male_af,0)+COALESCE(female_af,0)
      + COALESCE(male_oan,0)+COALESCE(female_oan,0)
      + COALESCE(male_culex,0)+COALESCE(female_culex,0)
      + COALESCE(male_other_culex,0)+COALESCE(female_other_culex,0)
      + COALESCE(male_aedes,0)+COALESCE(female_aedes,0)
    ),0) as total_all
    FROM vw_merged_field_lab_data
";
$total_records = (int)$pdo->query($total_records_sql)->fetchColumn();

// Total mosquitoes (female only across species)
$total_mosquitoes_sql = "
    SELECT COALESCE(SUM(
        COALESCE(female_ag,0)
      + COALESCE(female_af,0)
      + COALESCE(female_oan,0)
      + COALESCE(female_culex,0)
      + COALESCE(female_other_culex,0)
      + COALESCE(female_aedes,0)
    ),0) as total_female
    FROM vw_merged_field_lab_data
";
$total_mosquitoes = (int)$pdo->query($total_mosquitoes_sql)->fetchColumn();

// ---- 2) HISTOGRAM Aggregation (grouped bars per species: fed/unfed/gravid/semi_gravid) ----
// We'll get sums across the whole view for each feeding state per species (female counts only).
// Use COALESCE for missing columns (some species lack fed_* columns).
$selectPieces = [];
foreach ($speciesDefs as $key => $def) {
    // female total already handled separately, but we include it too
    $femaleCol = $def['female_col'];
    $selectPieces[] = "SUM(COALESCE($femaleCol,0)) AS {$key}_female";
    $fedCol = $def['fed'] ?? null;
    $unfedCol = $def['unfed'] ?? null;
    $gravidCol = $def['gravid'] ?? null;
    $semiCol = $def['semi_gravid'] ?? null;
    // If any of these are null (not present), COALESCE to 0 via literal 0
    $selectPieces[] = ($fedCol ? "SUM(COALESCE($fedCol,0)) AS {$key}_fed" : "0 AS {$key}_fed");
    $selectPieces[] = ($unfedCol ? "SUM(COALESCE($unfedCol,0)) AS {$key}_unfed" : "0 AS {$key}_unfed");
    $selectPieces[] = ($gravidCol ? "SUM(COALESCE($gravidCol,0)) AS {$key}_gravid" : "0 AS {$key}_gravid");
    $selectPieces[] = ($semiCol ? "SUM(COALESCE($semiCol,0)) AS {$key}_semi_gravid" : "0 AS {$key}_semi_gravid");
}
$histSql = "SELECT " . implode(", ", $selectPieces) . " FROM vw_merged_field_lab_data";
$histRow = $pdo->query($histSql)->fetch(PDO::FETCH_ASSOC);

// Build histogram structure in PHP
$histogram = [];
foreach ($speciesDefs as $key => $def) {
    $histogram[$def['label']] = [
        'fed' => (int)($histRow["{$key}_fed"] ?? 0),
        'unfed' => (int)($histRow["{$key}_unfed"] ?? 0),
        'gravid' => (int)($histRow["{$key}_gravid"] ?? 0),
        'semi_gravid' => (int)($histRow["{$key}_semi_gravid"] ?? 0),
        'female_total' => (int)($histRow["{$key}_female"] ?? 0)
    ];
}

// ---- 3) Species per cluster (female sums) for LineChart ----
$clusterSpeciesSql = "
    SELECT COALESCE(v.clstname,'(Unknown)') AS clstname,
        SUM(COALESCE(v.female_ag,0)) AS female_ag,
        SUM(COALESCE(v.female_af,0)) AS female_af,
        SUM(COALESCE(v.female_oan,0)) AS female_oan,
        SUM(COALESCE(v.female_culex,0)) AS female_culex,
        SUM(COALESCE(v.female_other_culex,0)) AS female_other_culex,
        SUM(COALESCE(v.female_aedes,0)) AS female_aedes
    FROM vw_merged_field_lab_data v
    GROUP BY clstname
    ORDER BY clstname
";
$clusterStmt = $pdo->query($clusterSpeciesSql);
$clusterRows = $clusterStmt->fetchAll(PDO::FETCH_ASSOC);
$clusterLabels = [];
$clusterSpeciesSeries = [
    'An. gambiae' => [],
    'An. funestus' => [],
    'Other Anopheles' => [],
    'Culex' => [],
    'Other Culex' => [],
    'Aedes' => []
];
foreach ($clusterRows as $r) {
    $clusterLabels[] = $r['clstname'];
    $clusterSpeciesSeries['An. gambiae'][] = (int)$r['female_ag'];
    $clusterSpeciesSeries['An. funestus'][] = (int)$r['female_af'];
    $clusterSpeciesSeries['Other Anopheles'][] = (int)$r['female_oan'];
    $clusterSpeciesSeries['Culex'][] = (int)$r['female_culex'];
    $clusterSpeciesSeries['Other Culex'][] = (int)$r['female_other_culex'];
    $clusterSpeciesSeries['Aedes'][] = (int)$r['female_aedes'];
}

// ---- 4) Trending per round (female totals) ----
$trendingSql = "
    SELECT `round`, SUM(
        COALESCE(female_ag,0)+COALESCE(female_af,0)+COALESCE(female_oan,0)+
        COALESCE(female_culex,0)+COALESCE(female_other_culex,0)+COALESCE(female_aedes,0)
    ) AS total_female
    FROM vw_merged_field_lab_data
    GROUP BY `round`
    ORDER BY `round` ASC
";
$trendStmt = $pdo->query($trendingSql);
$trendRows = $trendStmt->fetchAll(PDO::FETCH_ASSOC);
$trendLabels = [];
$trendValues = [];
foreach ($trendRows as $tr) {
    $trendLabels[] = "Round " . $tr['round'];
    $trendValues[] = (int)$tr['total_female'];
}

// ---- 5) Household table data (aggregated per hhcode), with lat/lng from households table ----
$householdSql = "
    SELECT v.hhcode,
      h.latitude, h.longitude,
      SUM(COALESCE(v.male_ag,0)+COALESCE(v.female_ag,0)) AS ag_total,
      SUM(COALESCE(v.male_af,0)+COALESCE(v.female_af,0)) AS af_total,
      SUM(COALESCE(v.male_oan,0)+COALESCE(v.female_oan,0)) AS oan_total,
      SUM(COALESCE(v.male_culex,0)+COALESCE(v.female_culex,0)) AS culex_total,
      SUM(COALESCE(v.male_other_culex,0)+COALESCE(v.female_other_culex,0)) AS other_culex_total,
      SUM(COALESCE(v.male_aedes,0)+COALESCE(v.female_aedes,0)) AS aedes_total,
      SUM(
         COALESCE(v.male_ag,0)+COALESCE(v.female_ag,0)
       + COALESCE(v.male_af,0)+COALESCE(v.female_af,0)
       + COALESCE(v.male_oan,0)+COALESCE(v.female_oan,0)
       + COALESCE(v.male_culex,0)+COALESCE(v.female_culex,0)
       + COALESCE(v.male_other_culex,0)+COALESCE(v.female_other_culex,0)
       + COALESCE(v.male_aedes,0)+COALESCE(v.female_aedes,0)
      ) AS total_mf
    FROM vw_merged_field_lab_data v
    LEFT JOIN households h ON v.hhcode = h.hhcode
    GROUP BY v.hhcode
    ORDER BY v.hhcode
";
$hhStmt = $pdo->query($householdSql);
$hhRows = $hhStmt->fetchAll(PDO::FETCH_ASSOC);

// build household display rows and also markers payload (skip null lat/lng)
$householdTable = [];
$mapMarkers = []; // { hhcode, lat, lng, total_mf, speciesSummary }
foreach ($hhRows as $r) {
    $speciesParts = [];
    if ((int)$r['ag_total'] > 0) $speciesParts[] = "AG: " . (int)$r['ag_total'];
    if ((int)$r['af_total'] > 0) $speciesParts[] = "AF: " . (int)$r['af_total'];
    if ((int)$r['oan_total'] > 0) $speciesParts[] = "OtherAn: " . (int)$r['oan_total'];
    if ((int)$r['culex_total'] > 0) $speciesParts[] = "Culex: " . (int)$r['culex_total'];
    if ((int)$r['other_culex_total'] > 0) $speciesParts[] = "OtherCulex: " . (int)$r['other_culex_total'];
    if ((int)$r['aedes_total'] > 0) $speciesParts[] = "Aedes: " . (int)$r['aedes_total'];

    $speciesSummary = implode(", ", $speciesParts);
    $householdTable[] = [
        'hhcode' => $r['hhcode'],
        'species' => $speciesSummary,
        'total' => (int)$r['total_mf']
    ];
    if (!empty($r['latitude']) && !empty($r['longitude'])) {
        $mapMarkers[] = [
            'hhcode' => $r['hhcode'],
            'lat' => (float)$r['latitude'],
            'lng' => (float)$r['longitude'],
            'total' => (int)$r['total_mf'],
            'speciesSummary' => $speciesSummary,
            'clstname' => null //  fetch cluster name here; not needed
        ];
    }
}

// ---- 6) clusterTotals for shading (female totals) approximate with cluster centroid later ----
$clusterTotals = [];
foreach ($clusterRows as $r) {
    $sum = (int)$r['female_ag'] + (int)$r['female_af'] + (int)$r['female_oan'] + (int)$r['female_culex'] + (int)$r['female_other_culex'] + (int)$r['female_aedes'];
    $clusterTotals[$r['clstname']] = $sum;
}




// ---- Data prepared, now render UI ----
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">



<div class="container-fluid">
  <div class="breadcrumb my-2"><i class="fas fa-home"></i> &nbsp Dashboard</div>

<?php
try {
    // Fetch projects assigned to this user
    $stmt = $pdo->prepare("
        SELECT p.project_id, p.project_name,
               p.project_description, 
               p.principal_investigator,
               p.project_type, 
               p.project_current_stage,
               p.project_status
        FROM user_projects dp
        INNER JOIN projects p ON dp.project_id = p.project_id
        WHERE dp.user_id = :user_id
    ");
    $stmt->execute([':user_id' => $userId]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$projects) {
        echo "<div class='no-project'>
                <h4><strong>Project Dashboard</strong></h4>
                <p>You are not Assigned to any Project.</p>
              </div>";
    } else {
        foreach ($projects as $project) {
            ?>
            <!-- PROJECT DETAILS -->
            <h4><strong>Project name: <?= htmlspecialchars($project['project_name']) ?></strong></h4>

            <!-- Tabs -->
            <div class="tabs">
                <button class="tab-btn active" data-tab="generated">Dashboard</button>
            </div>

            <div class="project-meta" style="margin-bottom:15px;">
                <span><strong>Principal Investigator:</strong> <?= htmlspecialchars($project['principal_investigator']) ?></span>
            </div>

            <div class="project-description">
                <span><strong>Project Status: </strong><?= nl2br(htmlspecialchars($project['project_status'])) ?>
            </div>

            <div class="project-description">
               <span><strong>Current Stage: </strong>On <?= nl2br(htmlspecialchars($project['project_current_stage'])) ?></span>
                
            </div>
            <hr>
            <?php
        }
    }
} catch (PDOException $e) {
    echo "<div class='error'>Error fetching project details: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

  <!-- SUMMARY CARDS -->
  <div class="row summary-cards g-2 mb-3">
    <div class="col"><div class="card p-3 text-center"><h6>Total Clusters</h6><p><?= (int)$total_clusters ?></p></div></div>
    <div class="col"><div class="card p-3 text-center"><h6>Total Households</h6><p><?= (int)$total_households ?></p></div></div>
    <div class="col"><div class="card p-3 text-center"><h6>Current Round</h6><p><?= (int)$total_rounds ?></p></div></div>
    <div class="col"><div class="card p-3 text-center"><h6>Total Records (M+F)</h6><p><?= (int)$total_records ?></p></div></div>
    <div class="col"><div class="card p-3 text-center"><h6>Total Mosquitoes (Female)</h6><p><?= (int)$total_mosquitoes ?></p></div></div>
  </div>

    <div class="row g-2 mb-1">
     <div class="col-md-12">
      <div class="card p-12">
        <h6>Trend per Round (Female total)</h6>
        <div class="chart-container"><canvas id="lineTrending"></canvas></div>
      </div>
    </div>
  </div>

<?php
// ===== Step 1: Prepare cluster-level data with household grouping =====
// speciesCols and feeding groups (already defined)
$speciesCols = ['ag','af','oan','culex','other_culex','aedes']; 
$groups = ['fed','unfed','gravid','semi_gravid'];

$clusterData = []; // cluster => species & household aggregation
$mapMarkers = [];  // cluster marker for map

$sql = "SELECT * FROM vw_merged_field_lab_data";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($rows as $row){
    $clstname = $row['clstname'] ?? '(Unknown)';
    $hhcode = $row['hhcode'];

    // init cluster if not exists
    if(!isset($clusterData[$clstname])){
        $clusterData[$clstname] = [
            'total_female'=>0,
            'species'=>[],
            'households'=>[] // will store per HH species summary
        ];
        foreach($speciesCols as $sp){
            $clusterData[$clstname]['species'][$sp] = [];
            foreach($groups as $gr){
                $col = $gr."_".$sp;
                $clusterData[$clstname]['species'][$sp][$gr] = isset($row[$col])?(int)$row[$col]:0;
            }
        }
    } else {
        // sum feeding states per species
        foreach($speciesCols as $sp){
            foreach($groups as $gr){
                $col = $gr."_".$sp;
                $clusterData[$clstname]['species'][$sp][$gr] += isset($row[$col])?(int)$row[$col]:0;
            }
        }
    }

    // total female per cluster
    foreach($speciesCols as $sp){
        $clusterData[$clstname]['total_female'] += isset($row['female_'.$sp])?(int)$row['female_'.$sp]:0;
    }

    // per household species summary (for table + popup)
    $speciesParts = [];
    foreach($speciesCols as $sp){
        $totalSp = (int)($row['male_'.$sp]??0) + (int)($row['female_'.$sp]??0);
        if($totalSp>0){
            $speciesParts[] = strtoupper($sp) . ": ".$totalSp;
        }
    }
    $speciesSummary = implode(", ", $speciesParts);

    $clusterData[$clstname]['households'][] = [
        'hhcode'=>$hhcode,
        'lat'=>$row['latitude']??null,
        'lng'=>$row['longitude']??null,
        'speciesSummary'=>$speciesSummary,
        'total'=>array_sum(array_map(function($sp){ return $sp; }, [$row['male_ag']??0,$row['female_ag']??0,$row['male_af']??0,$row['female_af']??0,$row['male_oan']??0,$row['female_oan']??0,$row['male_culex']??0,$row['female_culex']??0,$row['male_other_culex']??0,$row['female_other_culex']??0,$row['male_aedes']??0,$row['female_aedes']??0]))
    ];

    // cluster marker (first HH as centroid for simplicity)
    if(!isset($mapMarkers[$clstname]) && !empty($row['latitude']) && !empty($row['longitude'])){
        $mapMarkers[$clstname] = [
            'cluster'=>$clstname,
            'lat'=>$row['latitude'],
            'lng'=>$row['longitude'],
            'total_female'=>$clusterData[$clstname]['total_female']
        ];
    }
}

?>





  <!-- CHARTS ROW -->
  <div class="row g-2 mb-2">
    <div class="col-md-6">
      <div class="card p-6">
        <h6>Histogram: Feeding states by Species (Female only)</h6>
        <div class="chart-container"><canvas id="histogramChart"></canvas></div>
        <small class="text-muted">X = species, grouped bars = Fed / Unfed / Gravid / SemiGravid (counts)</small>
      </div>
    </div>

    
  
    <div class="col-md-6">
      <div class="card p-6">
        <h6>Species per Cluster (Female)</h6>
        <div class="chart-container"><canvas id="lineSpeciesCluster"></canvas></div>
      </div>
    </div>





  <!-- MAP + TABLE -->
<!-- ===== Step 2: Build cluster table with HH dropdown (grouped) ===== -->

  <div class="row g-2 mb-2">
    <div class="col-md-6">
      <div class="card p-3">
  <h6>Map: Household Locations</h6>
  <div id="map"></div>
</div>
</div>

<div class="col-md-6">
  <div class="card p-3">
    <h6>Clusters & Households</h6>
    <div class="table-responsive" style="max-height: 400px; overflow-y:auto;">
      <table id="clusterTable" class="display compact table table-sm table-hover w-100">
        <thead class="table-light">
          <tr>
            <th>Cluster</th>
            <th>Total Female</th>
            <th>Households</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($clusterData as $clstname => $cdata): ?>
          <tr data-cluster="<?= htmlspecialchars($clstname) ?>">
            <td>
              <button class="btn btn-sm btn-outline-primary toggle-households">+</button>
              <strong><?= htmlspecialchars($clstname) ?></strong>
            </td>
            <td><?= (int)$cdata['total_female'] ?></td>
            <td>
              <ul class="household-list list-unstyled mb-0 ps-3">
                <?php foreach($cdata['households'] as $hh): ?>
                <li class="hh-link"
                    data-lat="<?= $hh['lat'] ?>"
                    data-lng="<?= $hh['lng'] ?>"
                    data-species="<?= htmlspecialchars($hh['speciesSummary']) ?>">
                  <?= htmlspecialchars($hh['hhcode']) ?> (<?= $hh['total'] ?>)
                </li>
                <?php endforeach; ?>
              </ul>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>



<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>






<script>
/* ====== Prepare JS data from PHP ====== */
const histogram = <?= json_encode($histogram, JSON_UNESCAPED_UNICODE) ?>;
const speciesOrder = <?= json_encode(array_values(array_map(function($d){return $d['label'];}, $speciesDefs))) ?>;
const clusterLabels = <?= json_encode($clusterLabels) ?>;
const clusterSeries = <?= json_encode($clusterSpeciesSeries) ?>;
const trendLabels = <?= json_encode($trendLabels) ?>;
const trendValues = <?= json_encode($trendValues) ?>;
const hhMarkers = <?= json_encode($mapMarkers) ?>;
const hhTableData = <?= json_encode($householdTable) ?>;
const clusterTotals = <?= json_encode($clusterTotals) ?>;

/* ====== Histogram (grouped bar per species: fed/unfed/gravid/semi_gravid) ====== */
(function(){
    const ctx = document.getElementById('histogramChart').getContext('2d');
    const labels = Object.keys(histogram); // species labels
    // For each group: fed/unfed/gravid/semi_gravid build dataset across species
    const fedData = labels.map(s => histogram[s].fed);
    const unfedData = labels.map(s => histogram[s].unfed);
    const gravidData = labels.map(s => histogram[s].gravid);
    const semiData = labels.map(s => histogram[s].semi_gravid);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                { label: 'Fed', data: fedData, backgroundColor:'#1f77b4' },
                { label: 'Unfed', data: unfedData, backgroundColor:'#ff7f0e' },
                { label: 'Gravid', data: gravidData, backgroundColor:'#2ca02c' },
                { label: 'SemiGravid', data: semiData, backgroundColor:'#d62728' }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: { stacked: false },
                y: { beginAtZero: true, title: { display: true, text: 'Female count (absolute)' } }
            }
        }
    });
})();

/* ====== Species per cluster (line) ====== */
(function(){
    const ctx = document.getElementById('lineSpeciesCluster').getContext('2d');
    const datasets = [];
    const colors = ['#1f77b4','#ff7f0e','#2ca02c','#d62728','#9467bd','#8c564b'];
    let i=0;
    for (const sp in clusterSeries) {
        datasets.push({
            label: sp,
            data: clusterSeries[sp],
            borderColor: colors[i % colors.length],
            backgroundColor: colors[i % colors.length],
            fill: false,
            tension: 0.3
        });
        i++;
    }
    new Chart(ctx, { type:'line', data:{ labels: clusterLabels, datasets } });
})();

/* ====== Trending per round (line) ====== */
(function(){
    const ctx = document.getElementById('lineTrending').getContext('2d');
    new Chart(ctx, {
        type:'line',
        data:{ labels: trendLabels, datasets:[{ label: 'Female total', data: trendValues, borderColor:'#1f77b4', fill:false, tension:0.25 }] },
        options:{ responsive:true, scales:{ y:{ beginAtZero:true } } }
    });
})();

// Leaflet Map
// ===== Leaflet Map =====
var map = L.map('map').setView([-6.8,39.2],7);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19}).addTo(map);

const clusterMarkers = {};
const markersData = <?= json_encode(array_values($mapMarkers)) ?>;

// color and radius helpers
function getColorByCount(count){ return count<=50?'green':count<=150?'yellow':'red'; }
function getRadiusByCount(count){ return 5 + Math.min(count/20,20); }

// add cluster markers
markersData.forEach(function(c){
    var color = getColorByCount(c.total_female);
    var radius = getRadiusByCount(c.total_female);
    var marker = L.circleMarker([c.lat,c.lng],{
        radius: radius,
        color: color,
        fillColor: color,
        fillOpacity:0.7
    }).addTo(map)
    .bindPopup(c.cluster ? c.cluster+"<br>Total Female: "+c.total_female : "HH: "+c.hhcode);
    if(c.cluster) clusterMarkers[c.cluster] = marker;
});

// ===== Toggle households (collapse/expand) =====
document.querySelectorAll('.toggle-households').forEach(btn => {
    btn.addEventListener('click', function(){
        const tr = this.closest('tr');
        const ul = tr.querySelector('.household-list');
        if (!ul) return;

        // collapse all other lists
        document.querySelectorAll('.household-list').forEach(otherUl => {
            if(otherUl !== ul) otherUl.style.display = 'none';
            const otherBtn = otherUl.closest('tr').querySelector('.toggle-households');
            if(otherBtn) otherBtn.textContent = '+';
        });

        // toggle current
        if(ul.style.display === 'none' || ul.style.display === ''){
            ul.style.display = 'block';
            this.textContent = '−';
        } else {
            ul.style.display = 'none';
            this.textContent = '+';
        }
    });
});

// ===== Event delegation: HH click opens map popup =====
document.querySelector('#clusterTable tbody').addEventListener('click', function(e){
    const hh = e.target.closest('.hh-link');
    if(!hh) return;

    const lat = parseFloat(hh.dataset.lat);
    const lng = parseFloat(hh.dataset.lng);
    const species = hh.dataset.species;
    const hhcode = hh.textContent.split(" ")[0];

    if(!isNaN(lat) && !isNaN(lng)){
        map.setView([lat,lng],13);
        L.popup()
         .setLatLng([lat,lng])
         .setContent("<b>HHCode: "+hhcode+"</b><br>Species: "+species)
         .openOn(map);
    }
});


</script>





<?php require_once ROOT_PATH . "includes/footer.php"; ?>

