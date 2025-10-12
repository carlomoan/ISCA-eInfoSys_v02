<?php
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'helpers/permissions.php';

// Check basic view permission
if (!checkPermission('data_entry') && !($_SESSION['is_admin'] ?? false)) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

$collectorCode = $_SESSION['role_id'] ?? null;
if (!$collectorCode) {
    echo "Collector code not set in session.";
    exit;
}


// Fetch Lab Data for user
$labData = [];
if ($collectorCode) {
    $stmt2 = $pdo->prepare("
        SELECT * FROM lab_sorter WHERE srtname = ? ORDER BY round DESC, lab_date DESC
    ");
    $stmt2->execute([$collectorCode]);
    $labData = $stmt2->fetchAll(PDO::FETCH_ASSOC);
}

?>

<!-- Field Data Table -->
<h4>Field Data Collected</h4>
<table class="reports-table" style="width:100%">
    <thead>
        <tr>
            <th>Round</th>
            <th>Household Code</th>
            <th>Collector</th>
            <th>Collection Date</th>
            <th>Lab Sorter</th>
            <th>Lab Date</th>
            <th>Lab Form Title</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($fieldData)): ?>
            <?php foreach ($fieldData as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['round']) ?></td>
                    <td><?= htmlspecialchars($row['hhcode']) ?></td>
                    <td><?= htmlspecialchars($row['collector']) ?></td>
                    <td><?= htmlspecialchars($row['field_coll_date']) ?></td>
                    <td><?= htmlspecialchars($row['sorter'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['lab_date'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['ento_lab_frm_title'] ?? '-') ?></td>
                    <td>
                        <button class="view-btn" data-type="field" data-hhcode="<?= htmlspecialchars($row['hhcode']) ?>" data-round="<?= htmlspecialchars($row['round']) ?>">View</button>
                        <button class="export-btn" data-type="field" data-hhcode="<?= htmlspecialchars($row['hhcode']) ?>" data-round="<?= htmlspecialchars($row['round']) ?>" data-filetype="excel">Download Excel</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8">No Field Data found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Lab Data Table -->
<h4>Lab Data Collected</h4>
<table class="reports-table" style="width:100%; margin-top:1em;">
    <thead>
        <tr>
            <th>Round</th>
            <th>Household Code</th>
            <th>Household Name</th>
            <th>Sorter Name</th>
            <th>Lab Date</th>
            <th>Lab Form Title</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($labData)): ?>
            <?php foreach ($labData as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['round']) ?></td>
                    <td><?= htmlspecialchars($row['hhcode']) ?></td>
                    <td><?= htmlspecialchars($row['hhname']) ?></td>
                    <td><?= htmlspecialchars($row['srtname']) ?></td>
                    <td><?= htmlspecialchars($row['lab_date']) ?></td>
                    <td><?= htmlspecialchars($row['ento_lab_frm_title']) ?></td>
                    <td>
                        <button class="view-btn" data-type="lab" data-hhcode="<?= htmlspecialchars($row['hhcode']) ?>" data-round="<?= htmlspecialchars($row['round']) ?>">View</button>
                        <button class="export-btn" data-type="lab" data-hhcode="<?= htmlspecialchars($row['hhcode']) ?>" data-round="<?= htmlspecialchars($row['round']) ?>" data-filetype="excel">Download Excel</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7">No Lab Data found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
