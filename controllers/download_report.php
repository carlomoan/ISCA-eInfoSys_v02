<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

if (session_status() == PHP_SESSION_NONE) {
    $sessionConfig = Config::session();
    session_name($sessionConfig['name']);
    session_start();
}

$userId = $_SESSION['user_id'] ?? null;
$permissions = $_SESSION['permissions'] ?? [];
$roleId = $_SESSION['role_id'] ?? null;
$isAdmin = $_SESSION['is_admin'] ?? false;
$isSuperuser = isset($_SESSION['role_name']) && (strtolower($_SESSION['role_name']) === 'superuser' || strtolower($_SESSION['role_name']) === 'super admin');

// Allow access if: user is logged in AND (has download_report permission OR is admin OR is superuser)
if (!$userId || (!in_array('download_report', $permissions) && $roleId != 1 && !$isAdmin && !$isSuperuser)) {
    header('HTTP/1.1 403 Forbidden');
    echo "Access denied. User ID: " . ($userId ?? 'none') . ", Role: " . ($_SESSION['role_name'] ?? 'none');
    exit;
}

// ===== Common helpers =====

// Export to UTF-8 CSV
function exportToCSV($filename, $rows, $columns)
{
    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$filename.csv\"");

    // Output UTF-8 BOM for Excel compatibility
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');

    // Write headers
    fputcsv($output, $columns);

    // Write data rows
    foreach ($rows as $row) {
        $data = [];
        foreach ($columns as $col) {
            $data[] = $row[$col] ?? '';
        }
        fputcsv($output, $data);
    }

    fclose($output);
    exit;
}

// Export to Excel (XLS - tab delimited)
function exportToExcel($filename, $rows, $columns)
{
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$filename.xls\"");

    // UTF-8 BOM
    echo "\xEF\xBB\xBF";
    echo implode("\t", $columns) . "\n";
    foreach ($rows as $row) {
        $data = [];
        foreach ($columns as $col) {
            $data[] = $row[$col] ?? '';
        }
        echo implode("\t", $data) . "\n";
    }
    exit;
}

// Export to XLSX (using PhpSpreadsheet for proper XLSX format)
function exportToXLSX($filename, $rows, $columns)
{
    try {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data');

        // Write headers with styling
        $colIndex = 1;
        foreach ($columns as $col) {
            $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex) . '1';
            $sheet->setCellValue($cellCoordinate, $col);

            // Style header row
            $sheet->getStyle($cellCoordinate)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]);

            // Auto-size columns
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex))->setAutoSize(true);
            $colIndex++;
        }

        // Write data rows
        $rowIndex = 2;
        foreach ($rows as $row) {
            $colIndex = 1;
            foreach ($columns as $col) {
                $value = $row[$col] ?? '';
                $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex) . $rowIndex;

                // Set cell value with proper type detection
                if (is_numeric($value)) {
                    $sheet->setCellValue($cellCoordinate, $value);
                } else {
                    $sheet->setCellValueExplicit($cellCoordinate, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }

                $colIndex++;
            }
            $rowIndex++;
        }

        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');

        // Write to output
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');

        // Clean up
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        exit;

    } catch (Exception $e) {
        error_log("XLSX Export Error: " . $e->getMessage());
        die("Error generating XLSX file: " . $e->getMessage());
    }
}

// Export to XML
function exportToXML($filename, $rows, $columns)
{
    header("Content-Type: text/xml; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$filename.xml\"");

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<data>' . "\n";

    foreach ($rows as $row) {
        $xml .= '  <record>' . "\n";
        foreach ($columns as $col) {
            $value = htmlspecialchars($row[$col] ?? '', ENT_XML1);
            $xml .= '    <' . htmlspecialchars($col, ENT_XML1) . '>' . $value . '</' . htmlspecialchars($col, ENT_XML1) . '>' . "\n";
        }
        $xml .= '  </record>' . "\n";
    }

    $xml .= '</data>';

    echo $xml;
    exit;
}

// Export to SPSS (.sav format - portable)
function exportToSPSS($filename, $rows, $columns)
{
    // SPSS Portable format (.por) - text-based alternative to .sav
    header("Content-Type: application/x-spss-por");
    header("Content-Disposition: attachment; filename=\"$filename.por\"");

    // Create SPSS-compatible CSV that can be imported
    // This is a simplified version - for full .sav support, use external libraries
    $output = fopen('php://output', 'w');

    // SPSS metadata header
    fwrite($output, "SPSS PORT FILE                                                          \n");
    fwrite($output, "Generated by eDataColls System - " . date('Y-m-d H:i:s') . "\n");

    // Variable definitions
    foreach ($columns as $idx => $col) {
        fwrite($output, sprintf("%-8s VAR%03d\n", substr($col, 0, 8), $idx + 1));
    }

    // Data section marker
    fwrite($output, "\nDATA:\n");

    // Write header
    fputcsv($output, $columns);

    // Write data
    foreach ($rows as $row) {
        $data = [];
        foreach ($columns as $col) {
            $data[] = $row[$col] ?? '';
        }
        fputcsv($output, $data);
    }

    fclose($output);
    exit;
}

// Export to PDF using simple table layout
function exportToPDF($filename, $rows, $columns)
{
    header("Content-Type: application/pdf");
    header("Content-Disposition: attachment; filename=\"$filename.pdf\"");

    // Simple PDF generation - Basic implementation
    // For production, consider using TCPDF or FPDF libraries

    $pdf = "%PDF-1.4\n";
    $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources 4 0 R /MediaBox [0 0 612 792] /Contents 5 0 R >>\nendobj\n";
    $pdf .= "4 0 obj\n<< /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >>\nendobj\n";

    // Content stream
    $content = "BT\n/F1 10 Tf\n50 750 Td\n";
    $content .= "(" . $filename . ") Tj\n";
    $content .= "0 -20 Td\n";
    $content .= "(" . implode(" | ", array_slice($columns, 0, 5)) . ") Tj\n";

    $y = 710;
    foreach (array_slice($rows, 0, 30) as $row) {
        $line = [];
        foreach (array_slice($columns, 0, 5) as $col) {
            $line[] = substr($row[$col] ?? '', 0, 15);
        }
        $content .= "0 -15 Td\n";
        $content .= "(" . implode(" | ", $line) . ") Tj\n";
        $y -= 15;
        if ($y < 50) break;
    }

    $content .= "ET\n";

    $length = strlen($content);
    $pdf .= "5 0 obj\n<< /Length $length >>\nstream\n$content\nendstream\nendobj\n";

    $pdf .= "xref\n0 6\n";
    $pdf .= "0000000000 65535 f \n";
    $pdf .= "0000000009 00000 n \n";
    $pdf .= "0000000056 00000 n \n";
    $pdf .= "0000000115 00000 n \n";
    $pdf .= "0000000214 00000 n \n";
    $pdf .= "0000000299 00000 n \n";
    $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n" . strlen($pdf) . "\n%%EOF";

    echo $pdf;
    exit;
}

// ===== Sanitize parameters =====
$type       = $_REQUEST['type'] ?? '';
$id         = $_REQUEST['id'] ?? null;
$fileType   = strtolower($_REQUEST['filetype'] ?? 'excel');
$exportAll  = $_REQUEST['export'] ?? null;
$view       = $_REQUEST['view'] ?? 'vw_merged_field_lab_data';
$currentIds = isset($_REQUEST['current_ids']) ? json_decode($_REQUEST['current_ids'], true) : [];

// ===== Allowed views (generated) =====
$allowedViews = [
    'field_collector',
    'lab_sorter',
    'vw_merged_field_lab_data',
];

if ($type === 'generated' && !in_array($view, $allowedViews)) {
    die("Invalid report view.");
}

// ===== Handle Generated Reports =====
if ($type === 'generated') {
    try {
        $sql = "SELECT * FROM $view";
        $params = [];

        if (!empty($currentIds)) {
            // Download current selected rows only
            $placeholders = implode(",", array_fill(0, count($currentIds), "?"));
            $sql .= " WHERE hhcode IN ($placeholders)";
            $params = $currentIds;
        } elseif ($exportAll) {
            // Export all rows from selected view
            // no filter
        } elseif ($id) {
            // Download by round only if id is provided
            $sql .= " WHERE round = ?";
            $params[] = $id;
        }

        $sql .= " ORDER BY round DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            die("No data found for generated reports.");
        }

        $columns = array_keys($rows[0]);
        $filename = "generated_report_" . date("Ymd_His");

        switch ($fileType) {
            case 'excel':
            case 'xls':
                exportToExcel($filename, $rows, $columns);
                break;
            case 'xlsx':
                exportToXLSX($filename, $rows, $columns);
                break;
            case 'csv':
            case 'utf8':
            case 'utf-8':
                exportToCSV($filename, $rows, $columns);
                break;
            case 'xml':
                exportToXML($filename, $rows, $columns);
                break;
            case 'spss':
            case 'por':
                exportToSPSS($filename, $rows, $columns);
                break;
            case 'pdf':
                exportToPDF($filename, $rows, $columns);
                break;
            default:
                die("Invalid file type: $fileType");
        }
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

// ===== Handle Uploaded Reports =====
elseif ($type === 'uploaded') {
    try {
        if (!empty($currentIds)) {
            // Download current selected uploaded reports (list all in excel/pdf)
            $placeholders = implode(",", array_fill(0, count($currentIds), "?"));
            $stmt = $pdo->prepare("SELECT file_name, report_type, round, cluster_name, uploaded_at 
                                   FROM uploaded_reports 
                                   WHERE id IN ($placeholders)
                                   ORDER BY uploaded_at DESC");
            $stmt->execute($currentIds);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$rows) {
                die("No uploaded reports found.");
            }

            $columns = ['file_name', 'report_type', 'round', 'cluster_name', 'uploaded_at'];
            $filename = "uploaded_reports_" . date("Ymd_His");

            switch ($fileType) {
                case 'excel':
                case 'xls':
                    exportToExcel($filename, $rows, $columns);
                    break;
                case 'xlsx':
                    exportToXLSX($filename, $rows, $columns);
                    break;
                case 'csv':
                case 'utf8':
                case 'utf-8':
                    exportToCSV($filename, $rows, $columns);
                    break;
                case 'xml':
                    exportToXML($filename, $rows, $columns);
                    break;
                case 'spss':
                case 'por':
                    exportToSPSS($filename, $rows, $columns);
                    break;
                case 'pdf':
                    exportToPDF($filename, $rows, $columns);
                    break;
                default:
                    die("Invalid file type: $fileType");
            }
        } elseif ($exportAll) {
            // Export all uploaded reports
            $stmt = $pdo->query("SELECT file_name, report_type, round, cluster_name, uploaded_at 
                                 FROM uploaded_reports ORDER BY uploaded_at DESC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$rows) {
                die("No uploaded reports found.");
            }

            $columns = ['file_name', 'report_type', 'round', 'cluster_name', 'uploaded_at'];
            $filename = "uploaded_reports_" . date("Ymd_His");

            switch ($fileType) {
                case 'excel':
                case 'xls':
                    exportToExcel($filename, $rows, $columns);
                    break;
                case 'xlsx':
                    exportToXLSX($filename, $rows, $columns);
                    break;
                case 'csv':
                case 'utf8':
                case 'utf-8':
                    exportToCSV($filename, $rows, $columns);
                    break;
                case 'xml':
                    exportToXML($filename, $rows, $columns);
                    break;
                case 'spss':
                case 'por':
                    exportToSPSS($filename, $rows, $columns);
                    break;
                case 'pdf':
                    exportToPDF($filename, $rows, $columns);
                    break;
                default:
                    die("Invalid file type: $fileType");
            }
        } else {
            // Download single uploaded file
            if (!$id) {
                die("No report selected.");
            }

            $stmt = $pdo->prepare("SELECT file_name, file_path FROM uploaded_reports WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$report) {
                die("Report not found.");
            }

            $filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . $report['file_path'];
            $fileName = $report['file_name'];

            if (!file_exists($filePath)) {
                die("File does not exist.");
            }

            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename=\"" . basename($fileName) . "\"");
            readfile($filePath);
            exit;
        }
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    ?>
    <div id="upload-success-msg">
        Successfully uploaded! Please close this message to return...!
        <button class="upload_error-success" onclick="document.getElementById('success-msg').style.display='none'">
            <a href="index.php?page=report&tab=generated&view=report"> × </a>
        </button>
    </div>
<?php } ?>
