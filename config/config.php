<?php
/**
 * ================================================
 *  GLOBAL CONFIGURATION FILE (ENHANCED)
 * ================================================
 *  - ROOT_PATH: Server-side absolute path
 *  - BASE_URL : Browser-side base link
 *  - APP_ENV  : 'development' or 'production'
 *  - Loads from .env file for security
 * ================================================
 */

// =====================================================
// CONFIGURATION CLASS - Environment Variable Manager
// =====================================================
class Config
{
    private static array $cache = [];
    private static bool $loaded = false;

    public static function load(?string $envPath = null): bool
    {
        if (self::$loaded) return true;

        if ($envPath === null) {
            $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        }

        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                        $value = $matches[2];
                    }
                    if (!isset($_ENV[$key])) {
                        putenv("$key=$value");
                        $_ENV[$key] = $value;
                        $_SERVER[$key] = $value;
                    }
                }
            }
        }
        self::$loaded = true;
        return true;
    }

    public static function get(string $key, $default = null)
    {
        if (isset(self::$cache[$key])) return self::$cache[$key];
        $value = getenv($key);
        if ($value === false) {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }
        self::$cache[$key] = $value;
        return $value;
    }

    public static function getString(string $key, string $default = ''): string
    {
        return (string) self::get($key, $default);
    }

    public static function getInt(string $key, int $default = 0): int
    {
        return (int) self::get($key, $default);
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default);
        if (is_bool($value)) return $value;
        $value = strtolower((string) $value);
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    public static function getArray(string $key, array $default = [], string $delimiter = ','): array
    {
        $value = self::get($key, $default);
        if (is_array($value)) return $value;
        if (is_string($value) && !empty($value)) {
            return array_map('trim', explode($delimiter, $value));
        }
        return $default;
    }

    public static function database(): array
    {
        return [
            'host' => self::getString('DB_HOST', 'localhost'),
            'port' => self::getInt('DB_PORT', 3306),
            'database' => self::getString('DB_NAME', 'survey_amrc_db'),
            'username' => self::getString('DB_USER', 'root'),
            'password' => self::getString('DB_PASS', ''),
            'charset' => self::getString('DB_CHARSET', 'utf8mb4'),
            'timeout' => self::getInt('DB_TIMEOUT', 30),
            'max_retries' => self::getInt('DB_MAX_RETRIES', 3),
            'persistent' => self::getBool('DB_PERSISTENT', false),
        ];
    }

    public static function session(): array
    {
        return [
            'name' => self::getString('SESSION_NAME', 'isca_session'),
            'lifetime' => self::getInt('SESSION_LIFETIME', 7200),
            'secure' => self::getBool('SESSION_SECURE', false),
            'httponly' => self::getBool('SESSION_HTTPONLY', true),
            'samesite' => self::getString('SESSION_SAMESITE', 'Strict'),
            'regenerate_interval' => self::getInt('SESSION_REGENERATE_INTERVAL', 1800),
        ];
    }

    public static function isDebug(): bool
    {
        return self::getBool('APP_DEBUG', false);
    }

    public static function isProduction(): bool
    {
        return self::getString('APP_ENV', 'development') === 'production';
    }

    public static function isMaintenance(): bool
    {
        return self::getBool('MAINTENANCE_MODE', false);
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }
}

// Load environment variables
Config::load();

// Load path helper functions
require_once __DIR__ . '/path_helper.php';

// =====================
// 0. ENVIRONMENT SETUP
// =====================
// Get environment from .env or use default
if (!defined('APP_ENV')) {
    define('APP_ENV', Config::getString('APP_ENV', 'development'));
}

// Set error reporting based on environment
if (APP_ENV === 'development' || Config::isDebug()) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}


// =====================
// 1. ROOT & BASE URL
// =====================
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR);
    // e.g., C:/xampp/htdocs/ISCA-eInfoSys_v02/
}

if (!defined('BASE_URL')) {
    $baseUrlFromEnv = Config::getString('BASE_URL', '');
    if (!empty($baseUrlFromEnv)) {
        define('BASE_URL', $baseUrlFromEnv);
    } else {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $projectFolder = basename(ROOT_PATH);
        define('BASE_URL', $protocol . $host . '/' . $projectFolder);
    }
}

// =====================
// 2. FILE UPLOADS
// =====================
if (!defined('UPLOADS_FIELD_DATA')) {
    define('UPLOADS_FIELD_DATA', ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR . 'field_data' . DIRECTORY_SEPARATOR);
}
if (!defined('UPLOADS_REPORTS')) {
    define('UPLOADS_REPORTS', ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR);
}
if (!defined('ALLOWED_FILE_TYPES')) {
    define('ALLOWED_FILE_TYPES', Config::getArray('ALLOWED_FILE_TYPES', ['pdf', 'csv', 'xlsx', 'xls', 'doc', 'docx']));
}
if (!defined('MAX_FILE_SIZE_MB')) {
    define('MAX_FILE_SIZE_MB', Config::getInt('MAX_FILE_SIZE_MB', 10));
}

// Ensure upload directories exist and are writable
foreach ([UPLOADS_FIELD_DATA, UPLOADS_REPORTS] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// =====================
// 3. TIMEZONE & ERROR REPORTING
// =====================
date_default_timezone_set(Config::getString('APP_TIMEZONE', 'Africa/Dar_es_Salaam'));

if (APP_ENV === 'production') {
    // In production, log errors but don't display to user
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT_PATH . 'logs' . DIRECTORY_SEPARATOR . 'error.log');
}

// =====================
// 4. SECURITY HEADERS
// =====================
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");

// =====================
// 5. SESSION CONFIGURATION
// =====================
if (session_status() === PHP_SESSION_NONE) {
    // Get session configuration from environment
    $sessionConfig = Config::session();

    // Set session cookie parameters
    session_set_cookie_params([
        'lifetime' => $sessionConfig['lifetime'],
        'path' => '/',
        'domain' => '',
        'secure' => $sessionConfig['secure'],
        'httponly' => $sessionConfig['httponly'],
        'samesite' => $sessionConfig['samesite']
    ]);

    // Set session name
    session_name($sessionConfig['name']);

    // Start session
    session_start();

    // Regenerate session ID periodically to prevent fixation attacks
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > $sessionConfig['regenerate_interval']) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }

    // Check maintenance mode (but allow admins)
    if (Config::isMaintenance() && !($_SESSION['is_admin'] ?? false)) {
        $maintenanceMsg = Config::getString('MAINTENANCE_MESSAGE', 'System is under maintenance.');
        $whitelist = Config::getArray('MAINTENANCE_WHITELIST_IPS', []);
        $currentIP = $_SERVER['REMOTE_ADDR'] ?? '';

        if (!in_array($currentIP, $whitelist)) {
            http_response_code(503);
            die("<h1>503 Service Unavailable</h1><p>$maintenanceMsg</p>");
        }
    }
}

// =====================
// 6. DB TABLE PREFIX (optional)
// =====================
if (!defined('TABLE_PREFIX')) {
    define('TABLE_PREFIX', '');
}
