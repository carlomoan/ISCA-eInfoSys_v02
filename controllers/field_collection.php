<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/ISCA-eInfoSys_v02/config/constants.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

if (!checkPermission('view_field_data')) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

require_once ROOT_PATH . 'config/db_connect.php';

$userId = $_SESSION['user_id'] ?? null;
$roleId = $_SESSION['role_id'] ?? null;

// Fetch only collector’s field data + lab info
$records = [];
try {
    $stmt = $pdo->prepare("
        SELECT f.*, l.lab_result, l.lab_notes
        FROM field_data f
        LEFT JOIN lab_data l ON f.round = l.round AND f.house_code = l.house_code AND f.collector = l.collector
        WHERE f.collector_id = ?
        ORDER BY f.round DESC, f.house_code ASC
    ");
    $stmt->execute([$userId]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $records = [];
}
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/reports.css">
<script src="<?= BASE_URL ?>/assets/js/field_collector.js" defer></script>

<div class="reports-page">
    <div class="breadcrumb">
        <i class="fas fa-home"></i> <a href="?page=dashboard">Dashboard</a> / <strong>My Field Data</strong>
    </div>

    <h2>My Collected Field Data (with Lab Info)</h2>

    <div class="top-actions">
        <input type="text" id="searchInput" placeholder="Search field data..." class="search-box">
        <button class="export-btn" data-type="field" data-export="all" data-filetype="excel">Download Excel</button>
        <button class="export-btn" data-type="field" data-export="all" data-filetype="pdf">Download PDF</button>
    </div>

    <table class="reports-table" id="fieldDataTable">
        <thead>
            <?php if (!empty($records)): ?>
                <tr>
                    <?php 
                        $headers = array_keys(array_slice($records[0], 0, 12));
                        foreach ($headers as $col):
                    ?>
                        <th><?= htmlspecialchars(ucwords(str_replace("_", " ", $col))) ?></th>
                    <?php endforeach; ?>
                    <th>Actions</th>
                </tr>
            <?php endif; ?>
        </thead>
        <tbody>
            <?php if ($records): ?>
                <?php foreach ($records as $row): ?>
                    <tr>
                        <?php 
                            $limitedRow = array_slice($row, 0, 12, true);
                            foreach ($limitedRow as $cell): ?>
                                <td><?= htmlspecialchars($cell) ?></td>
                        <?php endforeach; ?>
                        <td>
                            <button class="view-btn" 
                                data-type="field" 
                                data-id="<?= htmlspecialchars($row['id']) ?>">View</button>
                            <button class="download-btn" 
                                data-type="field" 
                                data-id="<?= htmlspecialchars($row['id']) ?>" 
                                data-filetype="excel">Excel</button>
                            <button class="download-btn" 
                                data-type="field" 
                                data-id="<?= htmlspecialchars($row['id']) ?>" 
                                data-filetype="pdf">PDF</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="10">No field data found for your account.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="reportModal" class="modal">
    <div class="modal-content" style="max-width:90%;overflow:auto;">
        <span class="close">&times;</span>
        <div id="modalBody">Loading...</div>
    </div>
</div>
