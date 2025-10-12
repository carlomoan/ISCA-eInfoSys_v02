<?php
require_once __DIR__ . '/../../../config/db_connect.php';

// Default filters
$filterRound   = $_GET['round'] ?? '';
$filterCluster = $_GET['cluster'] ?? '';

// Fetch unique rounds & clusters for filters
$rounds = $pdo->query("SELECT DISTINCT round FROM field_collector ORDER BY round ASC")->fetchAll(PDO::FETCH_COLUMN);
$clusters = $pdo->query("SELECT DISTINCT clstname FROM field_collector ORDER BY clstname ASC")->fetchAll(PDO::FETCH_COLUMN);

// ✅ Fetch summarized generated data
$query = "
    SELECT 
        fc.round, 
        fc.clstname,
        fc.hhcode, 
        fc.hhname,
        ls.male_ag + ls.female_ag + ls.male_af + ls.female_af +
        ls.male_oan + ls.female_oan + ls.male_culex + ls.female_culex +
        ls.male_aedes + ls.female_aedes AS total_mosquitoes,
        ls.lab_date
    FROM field_collector fc
    INNER JOIN lab_sorter ls 
        ON fc.hhcode = ls.hhcode AND fc.round = ls.round
    WHERE 1=1
";

// Filters
$params = [];
if (!empty($filterRound)) {
    $query .= " AND fc.round = :round";
    $params['round'] = $filterRound;
}
if (!empty($filterCluster)) {
    $query .= " AND fc.clstname = :cluster";
    $params['cluster'] = $filterCluster;
}

// ✅ Restrict duplicate hhcode for same round (keep first occurrence only)
$query .= " GROUP BY fc.round, fc.hhcode ORDER BY fc.round ASC, fc.clstname ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- ✅ FILTER FORM -->
<form method="get" style="margin-bottom:15px; display:flex; gap:10px; flex-wrap:wrap;">
    <input type="hidden" name="page" value="reports">
    <input type="hidden" name="tab" value="generated">

    <div>
        <label for="round" style="font-size:13px;">Round:</label><br>
        <select name="round" id="round" style="padding:5px;">
            <option value="">All</option>
            <?php foreach ($rounds as $r): ?>
                <option value="<?= htmlspecialchars($r) ?>" <?= $r == $filterRound ? 'selected' : '' ?>><?= htmlspecialchars($r) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label for="cluster" style="font-size:13px;">Cluster:</label><br>
        <select name="cluster" id="cluster" style="padding:5px;">
            <option value="">All</option>
            <?php foreach ($clusters as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>" <?= $c == $filterCluster ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div style="align-self:flex-end;">
        <button type="submit" style="padding:6px 12px; background:#007bff; color:#fff; border:none; border-radius:4px;">
            <i class="fas fa-filter"></i> Filter
        </button>
    </div>
</form>

<!-- ✅ DATA TABLE -->
<table style="width:100%; border-collapse:collapse; font-size:14px;">
    <thead>
        <tr style="background:#f0f0f0;">
            <th style="border:1px solid #ccc; padding:6px;">Round</th>
            <th style="border:1px solid #ccc; padding:6px;">Cluster Name</th>
            <th style="border:1px solid #ccc; padding:6px;">Household Code</th>
            <th style="border:1px solid #ccc; padding:6px;">Household Name</th>
            <th style="border:1px solid #ccc; padding:6px;">Total Mosquitoes</th>
            <th style="border:1px solid #ccc; padding:6px;">Lab Date</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($results)): ?>
            <?php foreach ($results as $row): ?>
                <tr>
                    <td style="border:1px solid #ccc; padding:6px;"><?= htmlspecialchars($row['round']) ?></td>
                    <td style="border:1px solid #ccc; padding:6px;"><?= htmlspecialchars($row['clstname']) ?></td>
                    <td style="border:1px solid #ccc; padding:6px;"><?= htmlspecialchars($row['hhcode']) ?></td>
                    <td style="border:1px solid #ccc; padding:6px;"><?= htmlspecialchars($row['hhname']) ?></td>
                    <td style="border:1px solid #ccc; padding:6px; font-weight:bold;"><?= htmlspecialchars($row['total_mosquitoes']) ?></td>
                    <td style="border:1px solid #ccc; padding:6px;"><?= htmlspecialchars($row['lab_date']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="border:1px solid #ccc; padding:8px; text-align:center; color:#999;">
                    No records found.
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
