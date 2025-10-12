<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db_connect.php';

$sql = file_get_contents(__DIR__ . '/../database/migrations/update_permissions_table_fixed.sql');
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (empty($statement) || strpos($statement, '--') === 0) continue;

    try {
        $pdo->exec($statement);
        echo "✓ " . substr($statement, 0, 60) . "..." . PHP_EOL;
    } catch (PDOException $e) {
        // Ignore duplicate column/key errors
        if (strpos($e->getMessage(), 'Duplicate column') !== false ||
            strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "→ Already exists: " . substr($statement, 0, 40) . "..." . PHP_EOL;
        } else {
            echo "⚠ " . $e->getMessage() . PHP_EOL;
        }
    }
}

echo PHP_EOL . "✓ Migration completed successfully" . PHP_EOL;
