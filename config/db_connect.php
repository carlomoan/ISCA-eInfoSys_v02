<?php
/**
 * ================================================
 *  DATABASE CONNECTION (PDO) - ENHANCED VERSION
 * ================================================
 *  Features:
 *  - Environment variable configuration (no hardcoded credentials)
 *  - Enhanced security with prepared statements
 *  - Automatic retry mechanism
 *  - Detailed error logging
 *  - Helper functions for database operations
 * ================================================
 */

// Load configuration if not already loaded
if (!class_exists('Config')) {
    require_once __DIR__ . '/config.php';
}

// Get database configuration from environment variables
$dbConfig = Config::database();

$host = $dbConfig['host'];
$port = $dbConfig['port'];
$db = $dbConfig['database'];
$user = $dbConfig['username'];
$pass = $dbConfig['password'];
$charset = $dbConfig['charset'];
$timeout = $dbConfig['timeout'];

// Build DSN
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

// PDO Options - Enhanced Security Configuration
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => $timeout,
    PDO::ATTR_PERSISTENT => $dbConfig['persistent'],
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}",
    PDO::ATTR_STRINGIFY_FETCHES => false,
];

/**
 * Attempt database connection with retry mechanism
 */
function createDatabaseConnection(string $dsn, string $user, string $pass, array $options, int $maxRetries = 3): ?PDO
{
    $attempt = 0;
    $lastError = null;

    while ($attempt < $maxRetries) {
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);

            // Connection successful
            if (Config::isDebug()) {
                error_log("✓ Database connected successfully" . ($attempt > 0 ? " (Attempt " . ($attempt + 1) . ")" : ""));
            }

            return $pdo;

        } catch (PDOException $e) {
            $attempt++;
            $lastError = $e;

            if ($attempt < $maxRetries) {
                $waitTime = min($attempt * 2, 5); // Exponential backoff, max 5 seconds
                error_log("⚠ Database connection failed (Attempt $attempt/$maxRetries). Retrying in {$waitTime}s...");
                sleep($waitTime);
            } else {
                error_log("❌ Database connection failed after $maxRetries attempts: " . $e->getMessage());
            }
        }
    }

    // Log the final error
    if ($lastError && Config::isDebug()) {
        error_log("Final connection error: " . $lastError->getMessage());
    }

    return null;
}

/**
 * Check database connection health
 */
function checkDatabaseHealth(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1');
        return true;
    } catch (PDOException $e) {
        error_log("Database health check failed: " . $e->getMessage());
        return false;
    }
}

// Attempt connection
try {
    $maxRetries = $dbConfig['max_retries'] ?? 3;
    $pdo = createDatabaseConnection($dsn, $user, $pass, $options, $maxRetries);

    if ($pdo === null) {
        throw new PDOException("Failed to connect to database after $maxRetries retry attempts");
    }

    // Make globally available (backward compatibility)
    if (!defined('PDO_READY')) {
        define('PDO_READY', true);
        $GLOBALS['pdo'] = $pdo;
    }

    // Set MySQL session settings for proper data handling
    try {
        $pdo->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES'");
        $pdo->exec("SET SESSION time_zone = '+03:00'"); // East Africa Time (EAT)

        // Optional: Set additional MySQL session variables for performance
        if (Config::isProduction()) {
            $pdo->exec("SET SESSION query_cache_type = ON");
        }
    } catch (PDOException $e) {
        // Log but don't fail if session settings fail
        error_log("⚠ Warning: Could not set MySQL session variables: " . $e->getMessage());
    }

    // Perform initial health check
    if (!checkDatabaseHealth($pdo)) {
        throw new PDOException("Database connection established but health check failed");
    }

} catch (PDOException $e) {
    // Handle connection error
    $logFile = dirname(__DIR__) . '/logs/db_error.log';
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $envInfo = Config::getString('APP_ENV', 'unknown');
    $errorDetails = "[$timestamp] [ENV: $envInfo] " . $e->getMessage() . "\n";
    $errorDetails .= "DSN: mysql:host=$host;port=$port;dbname=$db\n";
    $errorDetails .= "User: $user\n";
    $errorDetails .= str_repeat('-', 80) . "\n";

    @file_put_contents($logFile, $errorDetails, FILE_APPEND);

    // Display user-friendly error message
    if (Config::isProduction()) {
        http_response_code(503);
        die("
        <!DOCTYPE html>
        <html>
        <head><title>Service Unavailable</title></head>
        <body>
            <h1>503 - Service Unavailable</h1>
            <p>We're experiencing technical difficulties. Please try again later.</p>
            <p>If the problem persists, contact the system administrator.</p>
        </body>
        </html>
        ");
    } else {
        http_response_code(503);
        die("
        <!DOCTYPE html>
        <html>
        <head><title>Database Connection Error</title></head>
        <body>
            <h1>❌ Database Connection Failed</h1>
            <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <p><strong>Host:</strong> $host:$port</p>
            <p><strong>Database:</strong> $db</p>
            <p><strong>User:</strong> $user</p>
            <p>Check your .env file configuration and ensure the database server is running.</p>
        </body>
        </html>
        ");
    }
}

// ================================================
// HELPER FUNCTIONS
// ================================================

/**
 * Execute a safe query with parameters
 */
function dbQuery(PDO $pdo, string $query, array $params = []): PDOStatement
{
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        if (Config::isDebug()) {
            error_log("SQL: $query | Params: " . json_encode($params));
        }
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage() . " | Query: $query");
        throw $e;
    }
}

/**
 * Get single row
 */
function dbFetchOne(PDO $pdo, string $query, array $params = [])
{
    $stmt = dbQuery($pdo, $query, $params);
    return $stmt->fetch();
}

/**
 * Get all rows
 */
function dbFetchAll(PDO $pdo, string $query, array $params = []): array
{
    $stmt = dbQuery($pdo, $query, $params);
    return $stmt->fetchAll();
}

/**
 * Get single column value
 */
function dbFetchColumn(PDO $pdo, string $query, array $params = [])
{
    $stmt = dbQuery($pdo, $query, $params);
    return $stmt->fetchColumn();
}

/**
 * Execute INSERT/UPDATE/DELETE
 */
function dbExecute(PDO $pdo, string $query, array $params = []): int
{
    $stmt = dbQuery($pdo, $query, $params);
    return $stmt->rowCount();
}
