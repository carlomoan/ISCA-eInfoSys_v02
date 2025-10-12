<?php
/**
 * ================================================
 *  permissions.php — Dynamic permission loader from database
 * ================================================
 */

// Load DB if not already
if (!isset($GLOBALS['pdo'])) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/ISCA-eInfoSys_v02/config/db_connect.php');
}

$pdo = $GLOBALS['pdo'] ?? null;
$permissions = [];

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT name FROM permissions");
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($rows as $permName) {
            $permissions[$permName] = $permName;
        }

    } catch (PDOException $e) {
        error_log("⚠️ Failed to load permissions: " . $e->getMessage());

        if (defined('APP_ENV') && APP_ENV === 'development') {
            echo "<div style='color:red;font-family:monospace;'>⚠️ Error loading permissions from database.</div>";
        }

        $permissions = [];
    }
}

return $permissions;
