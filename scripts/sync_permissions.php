<?php
/**
 * ================================================
 * Permission Sync Script
 * ================================================
 *
 * Auto-discovers pages and creates corresponding permissions
 * Run this after adding new pages or deploying
 *
 * Usage: php scripts/sync_permissions.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../classes/Permission.php';

echo "╔════════════════════════════════════════╗" . PHP_EOL;
echo "║   Permission Synchronization Script   ║" . PHP_EOL;
echo "╚════════════════════════════════════════╝" . PHP_EOL . PHP_EOL;

$permission = new Permission($pdo);
$pagesDir = ROOT_PATH . 'pages/views';

echo "📁 Scanning pages directory: $pagesDir" . PHP_EOL . PHP_EOL;

// Sync page permissions
$results = $permission->syncPagePermissions($pagesDir);

// Display results
echo "✅ Added Permissions:" . PHP_EOL;
if (empty($results['added'])) {
    echo "   (none)" . PHP_EOL;
} else {
    foreach ($results['added'] as $perm) {
        echo "   ✓ $perm" . PHP_EOL;
    }
}

echo PHP_EOL . "✓ Existing Permissions:" . PHP_EOL;
if (empty($results['existing'])) {
    echo "   (none)" . PHP_EOL;
} else {
    foreach ($results['existing'] as $perm) {
        echo "   → $perm" . PHP_EOL;
    }
}

if (!empty($results['errors'])) {
    echo PHP_EOL . "❌ Errors:" . PHP_EOL;
    foreach ($results['errors'] as $error) {
        echo "   ⚠ $error" . PHP_EOL;
    }
}

echo PHP_EOL . "════════════════════════════════════════" . PHP_EOL;
echo "Summary:" . PHP_EOL;
echo "  Added: " . count($results['added']) . PHP_EOL;
echo "  Existing: " . count($results['existing']) . PHP_EOL;
echo "  Errors: " . count($results['errors']) . PHP_EOL;
echo "════════════════════════════════════════" . PHP_EOL . PHP_EOL;

echo "✓ Permission sync completed!" . PHP_EOL;
