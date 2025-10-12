<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db_connect.php';

$roleId      = $_SESSION['role_id'] ?? 0;
$isAdmin     = ($roleId == 1);
$permissions = $_SESSION['permissions'] ?? [];
$canViewReports   = $isAdmin || in_array('view_reports', $permissions);
$canUploadReports = $isAdmin || in_array('manage_reports', $permissions);

$action = $_REQUEST['action'] ?? '';

if (!$canViewReports) {
    http_response_code(403);
    echo "Permission denied.";
    exit;
}

switch ($action) {

    /* =========================================================
     * ✅ 1. UPLOAD REPORT
     * ========================================================= */
    case 'upload':
        if (!$canUploadReports) {
            echo "You don't have permission to upload reports.";
            exit;
        }

        if (!isset($_FILES['report_file'])) {
            echo "No file uploaded.";
            exit;
        }

        $allowedExtensions = ['pdf', 'csv', 'xlsx', 'xls'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB

        $file = $_FILES['report_file'];
        $fileName = basename($file['name']);
        $fileSize = $file['size'];
        $fileTmp  = $file['tmp_name'];
        $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowedExtensions)) {
            echo "Invalid file type. Allowed: pdf, csv, xlsx, xls.";
            exit;
        }

        if ($fileSize > $maxFileSize) {
            echo "File too large. Max 5MB allowed.";
            exit;
        }

        $newName = time() . "_" . preg_replace("/[^a-zA-Z0-9_\.-]/", "_", $fileName);
        $uploadPath = __DIR__ . '/../uploads/reports/' . $newName;

        if (move_uploaded_file($fileTmp, $uploadPath)) {
            $stmt = $pdo->prepare("INSERT INTO uploaded_reports (file_name, uploaded_by) VALUES (?, ?)");
            $stmt->execute([$newName, $_SESSION['user_id']]);

            echo "File uploaded successfully!";
        } else {
            echo "Failed to upload file.";
        }
        break;

    /* =========================================================
     * ✅ 2. DELETE REPORT
     * ========================================================= */
    case 'delete':
        if (!$isAdmin) {
            echo "Only admin can delete files.";
            exit;
        }

        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo "Invalid file ID.";
            exit;
        }

        $stmt = $pdo->prepare("SELECT file_name FROM uploaded_reports WHERE id=?");
        $stmt->execute([$id]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$file) {
            echo "File not found.";
            exit;
        }

        $filePath = __DIR__ . '/../uploads/reports/' . $file['file_name'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $stmt = $pdo->prepare("DELETE FROM uploaded_reports WHERE id=?");
        $stmt->execute([$id]);

        echo "File deleted successfully.";
        break;

    /* =========================================================
     * ✅ 3. EXPORT CSV (Generated Reports)
     * ========================================================= */
    case 'export_csv':
        $round   = $_GET['round'] ?? '';
        $cluster = $_GET['cluster'] ?? '';

        $sql = "
            SELECT fc.round, fc.clstname, fc.hhcode, fc.hhname,
                   SUM(ls.male_ag + ls.female_ag + ls.male_af + ls.female_af + 
                       ls.male_oan + ls.female_oan + ls.male_culex + ls.female_culex + 
                       ls.male_aedes + ls.female_aedes) AS total_mosquitoes
            FROM field_collector fc
            JOIN lab_sorter ls 
                ON fc.hhcode = ls.hhcode AND fc.round = ls.round
            WHERE 1=1
        ";

        $params = [];
        if (!empty($round)) {
            $sql .= " AND fc.round = ? ";
            $params[] = $round;
        }
        if (!empty($cluster)) {
            $sql .= " AND fc.clstname = ? ";
            $params[] = $cluster;
        }

        $sql .= " GROUP BY fc.round, fc.clstname, fc.hhcode, fc.hhname ORDER BY fc.round DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="generated_reports.csv"');

        $output = fopen("php://output", "w");
        fputcsv($output, ['Round', 'Cluster', 'Household Code', 'Household Name', 'Total Mosquitoes']);
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;

    /* =========================================================
     * ✅ 4. EXPORT PDF (Generated Reports)
     * ========================================================= */
    case 'export_pdf':
        require_once __DIR__ . '/../vendor/autoload.php';
        use Dompdf\Dompdf;

        $round   = $_GET['round'] ?? '';
        $cluster = $_GET['cluster'] ?? '';

        $sql = "
            SELECT fc.round, fc.clstname, fc.hhcode, fc.hhname,
                   SUM(ls.male_ag + ls.female_ag + ls.male_af + ls.female_af + 
                       ls.male_oan + ls.female_oan + ls.male_culex + ls.female_culex + 
                       ls.male_aedes + ls.female_aedes) AS total_mosquitoes
            FROM field_collector fc
            JOIN lab_sorter ls 
                ON fc.hhcode = ls.hhcode AND fc.round = ls.round
            WHERE 1=1
        ";

        $params = [];
        if (!empty($round)) {
            $sql .= " AND fc.round = ? ";
            $params[] = $round;
        }
        if (!empty($cluster)) {
            $sql .= " AND fc.clstname = ? ";
            $params[] = $cluster;
        }

        $sql .= " GROUP BY fc.round, fc.clstname, fc.hhcode, fc.hhname ORDER BY fc.round DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $html = '<h3>Generated Reports</h3><table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;"><thead><tr><th>Round</th><th>Cluster</th><th>Household Code</th><th>Household Name</th><th>Total Mosquitoes</th></tr></thead><tbody>';
        foreach ($data as $row) {
            $html .= "<tr>
                <td>{$row['round']}</td>
                <td>{$row['clstname']}</td>
                <td>{$row['hhcode']}</td>
                <td>{$row['hhname']}</td>
                <td>{$row['total_mosquitoes']}</td>
            </tr>";
        }
        $html .= '</tbody></table>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream("generated_reports.pdf");
        exit;

    default:
        echo "Invalid action.";
        break;
}
