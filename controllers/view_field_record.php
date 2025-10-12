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

$id = $_GET['id'] ?? null;
$userId = $_SESSION['user_id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo "Invalid request.";
    exit;
}

// Fetch record and verify ownership
try {
    $stmt = $pdo->prepare("
        SELECT f.*, l.*
        FROM field_collector f
        LEFT JOIN lab_sorter l ON f.round = l.round AND f.hhcode = l.hhcode
        WHERE f.hhcode = ? AND f.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$id, $userId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        http_response_code(404);
        echo "Record not found or you don't have permission.";
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "Error fetching record.";
    exit;
}

?>

<h3>Field Collector Data Detail</h3>
<table style="width:100%;border-collapse:collapse;" border="1" cellpadding="5">
    <?php foreach ($record as $key => $value): ?>
        <tr>
            <th style="text-align:left; width:30%; background:#eee;"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $key))) ?></th>
            <td><?= htmlspecialchars($value) ?></td>
        </tr>
    <?php endforeach; ?>
</table>
