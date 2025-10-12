<?php
/**
 * Database Export API
 * Exports the entire database as SQL dump
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . 'config/db_connect.php';
require_once ROOT_PATH . 'helpers/permission_helper.php';

// Check if user is admin or has manage_settings permission
if (!checkPermission('manage_settings') && !($_SESSION['is_admin'] ?? false)) {
    http_response_code(403);
    echo "Access denied. Only administrators can export the database.";
    exit;
}

try {
    // Get database credentials from PDO
    $dbHost = DB_HOST ?? 'localhost';
    $dbName = DB_NAME ?? '';
    $dbUser = DB_USER ?? '';
    $dbPass = DB_PASS ?? '';

    if (empty($dbName)) {
        throw new Exception("Database name not configured");
    }

    // Set filename with timestamp
    $filename = 'database_backup_' . date('Y-m-d_H-i-s') . '.sql';
    $tempFile = sys_get_temp_dir() . '/' . $filename;

    // Use mysqldump command
    $command = sprintf(
        'mysqldump --host=%s --user=%s --password=%s %s > %s 2>&1',
        escapeshellarg($dbHost),
        escapeshellarg($dbUser),
        escapeshellarg($dbPass),
        escapeshellarg($dbName),
        escapeshellarg($tempFile)
    );

    // Execute mysqldump
    exec($command, $output, $returnCode);

    // Check if mysqldump succeeded
    if ($returnCode !== 0 || !file_exists($tempFile) || filesize($tempFile) === 0) {
        // Fallback to PHP-based export
        exportDatabaseWithPHP($pdo, $tempFile, $dbName);
    }

    // Send file to browser
    if (file_exists($tempFile) && filesize($tempFile) > 0) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tempFile));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: public');

        readfile($tempFile);
        unlink($tempFile); // Delete temp file
        exit;
    } else {
        throw new Exception("Failed to create database backup");
    }

} catch (Exception $e) {
    error_log("Database export error: " . $e->getMessage());
    http_response_code(500);
    echo "Error exporting database: " . $e->getMessage();
    exit;
}

/**
 * Fallback PHP-based database export
 */
function exportDatabaseWithPHP($pdo, $tempFile, $dbName) {
    $sql = "-- Database Export\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Database: {$dbName}\n\n";
    $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sql .= "SET time_zone = \"+00:00\";\n\n";

    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        // DROP TABLE IF EXISTS
        $sql .= "-- \n";
        $sql .= "-- Table structure for table `{$table}`\n";
        $sql .= "-- \n\n";
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";

        // CREATE TABLE
        $stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
        $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
        $sql .= $createTable['Create Table'] . ";\n\n";

        // INSERT DATA
        $stmt = $pdo->query("SELECT * FROM `{$table}`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) > 0) {
            $sql .= "-- \n";
            $sql .= "-- Dumping data for table `{$table}`\n";
            $sql .= "-- \n\n";

            foreach ($rows as $row) {
                $values = array_map(function($value) use ($pdo) {
                    return $value === null ? 'NULL' : $pdo->quote($value);
                }, $row);

                $sql .= "INSERT INTO `{$table}` VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }
    }

    file_put_contents($tempFile, $sql);
}
