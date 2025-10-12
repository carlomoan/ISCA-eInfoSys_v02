# Configuration Guide - ISCA e-InfoSys v02

## Overview
This document describes the **complete enhanced configuration system** including environment management, security enhancements, database connection improvements, and bug fixes.

---

## 🎯 Latest Updates (2025-10-07)

### ✅ Fixed Critical Bugs
1. **Login API Missing Input Handler** - Fixed `api/auth/login.php:21` (added JSON input parsing)
2. **Hardcoded Path Issue** - Fixed `api/auth/login.php:17` (changed to relative path)
3. **Redirect Issues** - Fixed `logout.php` and added path helper functions

### ✅ Environment Management
- Complete `.env.example`, `.env.development`, and `.env.production` files
- Easy environment switching with `cp .env.development .env`
- BASE_URL configuration for all environments
- **PHP Built-in Server Support** - Development environment now uses `http://localhost:8000`

### ✅ Enhanced Features
- Session management with auto-regeneration
- Maintenance mode with IP whitelist
- Database retry with exponential backoff
- Connection health checks
- Comprehensive error handling
- **NEW: Path Helper Functions** - Centralized URL and path management

---

## What Changed?

### 🔒 Security Improvements
1. **No Hardcoded Credentials**: Database credentials are now loaded from `.env` file
2. **Environment-based Configuration**: Different settings for development and production
3. **Enhanced Error Handling**: Retry mechanism for database connections
4. **Security Headers**: Automatic security headers for web responses
5. **Protected Sensitive Files**: `.gitignore` prevents committing credentials

### ⚡ New Features
1. **Config Class**: Centralized configuration management with type casting
2. **Database Helper Functions**: Simplified database operations
3. **Automatic Logging**: Error logs saved to `/logs/` directory
4. **Connection Retry**: Automatic retry on database connection failure
5. **Debug Mode**: Detailed logging in development environment

---

## Quick Start

### 1. Environment Setup

The `.env` file contains your application configuration:

```bash
# Already created for you with current settings
# Location: /home/andrew/PhProjects/eDataColls/ISCA-eInfoSys_v02/.env
```

### 2. Update Configuration

Edit `.env` file to match your environment:

```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=survey_amrc_db
DB_USER=root
DB_PASS=your_password_here
DB_CHARSET=utf8mb4

# Application Environment (development|production)
APP_ENV=development
APP_DEBUG=true
```

### 3. Production Deployment

For production servers:

```env
APP_ENV=production
APP_DEBUG=false
DB_HOST=your_production_host
DB_PASS=your_secure_password
```

---

## Configuration Files

### 📁 File Structure

```
config/
├── config.php          # Main configuration (enhanced with Config class)
├── db_connect.php      # Database connection (now uses environment variables)
.env                    # Your configuration (DO NOT commit to git)
.env.example           # Template file (commit this)
.gitignore             # Protects sensitive files
```

### 📄 config.php

Enhanced with `Config` class that provides:

```php
// Load environment variables
Config::load();

// Get configuration values
$value = Config::get('KEY_NAME', 'default_value');
$string = Config::getString('DB_HOST', 'localhost');
$int = Config::getInt('DB_PORT', 3306);
$bool = Config::getBool('APP_DEBUG', false);
$array = Config::getArray('ALLOWED_FILE_TYPES', ['pdf', 'csv']);

// Get database configuration
$dbConfig = Config::database();

// Check environment
if (Config::isProduction()) {
    // Production code
}

if (Config::isDebug()) {
    // Debug code
}
```

### 📄 db_connect.php

Features:
- Loads credentials from `.env` file
- Connection retry mechanism (3 attempts)
- Enhanced error logging
- Helper functions for database operations

Helper functions available:

```php
// Execute query with parameters (safe from SQL injection)
$stmt = dbQuery($pdo, "SELECT * FROM users WHERE id = ?", [123]);

// Get single row
$user = dbFetchOne($pdo, "SELECT * FROM users WHERE id = ?", [123]);

// Get all rows
$users = dbFetchAll($pdo, "SELECT * FROM users WHERE status = ?", ['active']);

// Get single value
$count = dbFetchColumn($pdo, "SELECT COUNT(*) FROM users");

// Execute INSERT/UPDATE/DELETE
$affected = dbExecute($pdo, "UPDATE users SET status = ? WHERE id = ?", ['active', 123]);
```

---

## Environment Variables Reference

### Application Settings

| Variable | Type | Default | Description |
|----------|------|---------|-------------|
| `APP_ENV` | string | `development` | Environment mode (development/production) |
| `APP_DEBUG` | boolean | `false` | Enable debug mode |
| `APP_NAME` | string | `ISCA e-InfoSys` | Application name |
| `APP_TIMEZONE` | string | `Africa/Dar_es_Salaam` | Default timezone |

### Database Settings

| Variable | Type | Default | Description |
|----------|------|---------|-------------|
| `DB_HOST` | string | `localhost` | Database host |
| `DB_PORT` | integer | `3306` | Database port |
| `DB_NAME` | string | `survey_amrc_db` | Database name |
| `DB_USER` | string | `root` | Database username |
| `DB_PASS` | string | `` | Database password |
| `DB_CHARSET` | string | `utf8mb4` | Character set |
| `DB_TIMEOUT` | integer | `30` | Connection timeout (seconds) |

### File Upload Settings

| Variable | Type | Default | Description |
|----------|------|---------|-------------|
| `MAX_FILE_SIZE_MB` | integer | `10` | Maximum file size (MB) |
| `ALLOWED_FILE_TYPES` | array | `pdf,csv,xlsx,xls,doc,docx` | Allowed file extensions |

### Security Settings

| Variable | Type | Default | Description |
|----------|------|---------|-------------|
| `SESSION_LIFETIME` | integer | `7200` | Session lifetime (seconds) |
| `SESSION_NAME` | string | `isca_session` | Session cookie name |

---

## Usage Examples

### Example 1: Using Config Class

```php
<?php
require_once 'config/config.php';

// Get configuration values
$appName = Config::getString('APP_NAME');
$maxSize = Config::getInt('MAX_FILE_SIZE_MB');
$allowedTypes = Config::getArray('ALLOWED_FILE_TYPES');

if (Config::isDebug()) {
    echo "Running in debug mode";
}
```

### Example 2: Database Operations

```php
<?php
require_once 'config/db_connect.php';

// Old way (still works)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([123]);
$user = $stmt->fetch();

// New way (recommended - using helper functions)
$user = dbFetchOne($pdo, "SELECT * FROM users WHERE id = ?", [123]);
$users = dbFetchAll($pdo, "SELECT * FROM users WHERE status = ?", ['active']);
$count = dbFetchColumn($pdo, "SELECT COUNT(*) FROM users");

// Insert/Update
$affected = dbExecute($pdo,
    "INSERT INTO users (name, email) VALUES (?, ?)",
    ['John Doe', 'john@example.com']
);
```

### Example 3: Environment-Specific Code

```php
<?php
require_once 'config/config.php';

if (Config::isProduction()) {
    // Production settings
    ini_set('display_errors', 0);
    $apiKey = Config::getString('PRODUCTION_API_KEY');
} else {
    // Development settings
    ini_set('display_errors', 1);
    $apiKey = Config::getString('DEV_API_KEY');
}
```

---

## Security Best Practices

### ✅ DO:
- Keep `.env` file secure and never commit it to version control
- Use strong passwords for database connections
- Set `APP_ENV=production` and `APP_DEBUG=false` in production
- Use HTTPS in production environments
- Regularly rotate database passwords
- Review error logs regularly

### ❌ DON'T:
- Don't commit `.env` file to git (already in `.gitignore`)
- Don't use the same credentials for development and production
- Don't enable debug mode in production
- Don't hardcode credentials in PHP files anymore
- Don't share `.env` file contents publicly

---

## Troubleshooting

### Issue: Database connection failed

**Solution:**
1. Check `.env` file exists and has correct credentials
2. Verify database server is running
3. Check error log at `/logs/db_error.log`
4. Verify user has permissions to access database

### Issue: Configuration not loading

**Solution:**
1. Ensure `.env` file is in project root directory
2. Check file permissions (should be readable)
3. Verify `Config::load()` is called before using config values
4. Check syntax of `.env` file (KEY=VALUE format)

### Issue: Cannot write to logs directory

**Solution:**
1. Create logs directory: `mkdir -p logs`
2. Set proper permissions: `chmod 755 logs`
3. Verify web server user can write to directory

---

## Migration from Old System

### Before (Hardcoded):
```php
$host = 'localhost';
$db   = 'survey_amrc_db';
$user = 'root';
$pass = '123456';  // ❌ Hardcoded and insecure
```

### After (Environment Variables):
```php
// In .env file:
DB_HOST=localhost
DB_NAME=survey_amrc_db
DB_USER=root
DB_PASS=123456  // ✅ Secure, not in code

// In code:
$dbConfig = Config::database();  // ✅ Loads from .env
```

---

## Additional Resources

### Files Created/Modified:
1. ✅ [config/config.php](config/config.php) - Enhanced with Config class
2. ✅ [config/db_connect.php](config/db_connect.php) - Uses environment variables
3. ✅ [.env](.env) - Your configuration file
4. ✅ [.env.example](.env.example) - Template for new environments
5. ✅ [.gitignore](.gitignore) - Protects sensitive files
6. ✅ [CONFIGURATION.md](CONFIGURATION.md) - This documentation

### Backward Compatibility:
All existing code continues to work! The global `$pdo` variable is still available:

```php
// Still works
global $pdo;
$stmt = $pdo->query("SELECT * FROM users");
```

---

## Support

For issues or questions:
1. Check error logs at `/logs/db_error.log`
2. Verify `.env` configuration
3. Review this documentation
4. Check file permissions

---

## 🔗 Path Helper Functions

### Overview
Path helper functions eliminate hardcoded paths and ensure portability across different environments (XAMPP, built-in server, production).

### Available Functions

#### URL Functions (for HTML/Redirects)
```php
// Generate URLs (respects BASE_URL from .env)
url('login.php')                     // http://localhost:8000/login.php
url('index.php?page=dashboard')      // http://localhost:8000/index.php?page=dashboard
url()                                 // http://localhost:8000

// Asset URLs (CSS, JS, images)
asset('css/global.css')               // http://localhost:8000/assets/css/global.css
asset('js/login.js')                  // http://localhost:8000/assets/js/login.js
asset('images/logo.png')              // http://localhost:8000/assets/images/logo.png
```

#### Path Functions (for require/include)
```php
// Application paths
app_path('config/config.php')         // /path/to/project/config/config.php
config_path('db_connect.php')         // /path/to/project/config/db_connect.php
helper_path('permission_helper.php')  // /path/to/project/helpers/permission_helper.php
vendor_path('autoload.php')           // /path/to/project/vendor/autoload.php
```

#### Redirect Functions
```php
// Redirect to a page
redirect('login.php');                // Redirects to http://localhost:8000/login.php

// Redirect with custom status code
redirect('dashboard.php', 301);       // 301 Permanent redirect

// Redirect back to previous page
redirect_back('index.php');           // Goes back or fallback to index.php
```

### Migration Examples

#### Before (Hardcoded):
```php
// ❌ Bad - Breaks when moving environments
require_once $_SERVER['DOCUMENT_ROOT'] . '/ISCA-eInfoSys_v02/config/db_connect.php';
header('Location: /ISCA-eInfoSys_v02/login.php');
echo '<link href="/ISCA-eInfoSys_v02/assets/css/style.css">';
```

#### After (Dynamic):
```php
// ✅ Good - Works anywhere
require_once config_path('db_connect.php');
redirect('login.php');
echo '<link href="' . asset('css/style.css') . '">';
```

### Usage in Different Files

#### In Root Files (index.php, login.php, logout.php)
```php
<?php
require_once __DIR__ . '/config/config.php';  // Loads path helpers

// Now you can use helper functions
redirect('dashboard.php');
?>
<link href="<?= asset('css/global.css') ?>" rel="stylesheet">
<a href="<?= url('login.php') ?>">Login</a>
```

#### In API Files
```php
<?php
// Old way - AVOID
require_once $_SERVER['DOCUMENT_ROOT'] . '/ISCA-eInfoSys_v02/config/config.php';

// New way - RECOMMENDED
require_once __DIR__ . '/../../config/config.php';
// or
require_once config_path('config.php');
```

#### In View Files
```php
<!-- Old way - AVOID -->
<a href="/ISCA-eInfoSys_v02/login.php">Login</a>
<img src="/ISCA-eInfoSys_v02/assets/images/logo.png">

<!-- New way - RECOMMENDED -->
<a href="<?= url('login.php') ?>">Login</a>
<img src="<?= asset('images/logo.png') ?>">
```

### Testing Path Configuration
Visit `test_paths.php` in your browser to verify all paths are working:
```
http://localhost:8000/test_paths.php
```

This page shows:
- Environment configuration (APP_ENV, BASE_URL)
- All helper function outputs
- File existence checks
- Critical link tests

---

## 🧪 Testing & Verification

### Test Results (Development Environment)
```
✓ Environment: development
✓ Database: survey_amrc_db
✓ MySQL Version: 8.0.43
✓ Tables Found: 29
✓ Connection Test: PASSED
✓ Web Server Test: HTTP 200
✓ No Errors Logged
```

### How to Test
```bash
# 1. Switch to development
cp .env.development .env

# 2. Run connection test
php test_connection.php

# 3. Start development server
php -S localhost:8000

# 4. Visit in browser
http://localhost:8000/login.php
```

---

## 📋 Complete Environment Variables

### Application Settings
| Variable | Dev Value | Prod Value | Description |
|----------|-----------|------------|-------------|
| `APP_ENV` | `development` | `production` | Environment mode |
| `APP_DEBUG` | `true` | `false` | Debug mode |
| `BASE_URL` | `http://localhost/...` | `/ISCA-eInfoSys_v02` | Base URL |

### Database Configuration
| Variable | Dev Value | Prod Value | Description |
|----------|-----------|------------|-------------|
| `DB_HOST` | `localhost` | `localhost` | Database host |
| `DB_PORT` | `3306` | `3306` | Database port |
| `DB_NAME` | `survey_amrc_db` | `edatomvt_edata` | Database name |
| `DB_USER` | `root` | `edatomvt_admin` | Database user |
| `DB_PASS` | `123456` | `EDataColls@2025` | Database password |
| `DB_MAX_RETRIES` | `3` | `3` | Connection retries |
| `DB_PERSISTENT` | `false` | `false` | Persistent connections |

### Session Security
| Variable | Dev Value | Prod Value | Description |
|----------|-----------|------------|-------------|
| `SESSION_NAME` | `isca_session_dev` | `isca_session_prod` | Cookie name |
| `SESSION_LIFETIME` | `7200` | `7200` | Lifetime (seconds) |
| `SESSION_SECURE` | `false` | `true` | HTTPS only |
| `SESSION_HTTPONLY` | `true` | `true` | No JavaScript access |
| `SESSION_SAMESITE` | `Lax` | `Strict` | CSRF protection |
| `SESSION_REGENERATE_INTERVAL` | `1800` | `1800` | Regeneration interval |

### Password Policy
| Variable | Dev Value | Prod Value | Description |
|----------|-----------|------------|-------------|
| `PASSWORD_MIN_LENGTH` | `6` | `8` | Minimum length |
| `PASSWORD_REQUIRE_UPPERCASE` | `false` | `true` | Require uppercase |
| `PASSWORD_REQUIRE_LOWERCASE` | `false` | `true` | Require lowercase |
| `PASSWORD_REQUIRE_NUMBERS` | `false` | `true` | Require numbers |
| `PASSWORD_REQUIRE_SPECIAL` | `false` | `true` | Require special chars |

### Rate Limiting
| Variable | Dev Value | Prod Value | Description |
|----------|-----------|------------|-------------|
| `RATE_LIMIT_LOGIN` | `10` | `5` | Login attempts/min |
| `RATE_LIMIT_API` | `100` | `60` | API requests/min |
| `RATE_LIMIT_REGISTER` | `10` | `3` | Registration/min |

### CSRF Protection
| Variable | Value | Description |
|----------|-------|-------------|
| `CSRF_ENABLED` | `true` | Enable CSRF protection |
| `CSRF_TOKEN_LENGTH` | `32` | Token length |

### File Upload
| Variable | Value | Description |
|----------|-------|-------------|
| `MAX_FILE_SIZE_MB` | `10` | Max file size (MB) |
| `ALLOWED_FILE_TYPES` | `pdf,csv,xlsx,...` | Allowed extensions |
| `UPLOAD_PATH_FIELD_DATA` | `uploads/field_data/` | Field data path |
| `UPLOAD_PATH_REPORTS` | `uploads/reports/` | Reports path |

### Logging
| Variable | Dev Value | Prod Value | Description |
|----------|-----------|------------|-------------|
| `LOG_ENABLED` | `true` | `true` | Enable logging |
| `LOG_LEVEL` | `debug` | `error` | Log level |
| `LOG_FILE` | `logs/app.log` | `logs/app.log` | Log file path |
| `LOG_ERROR_FILE` | `logs/error.log` | `logs/error.log` | Error log path |
| `LOG_MAX_SIZE_MB` | `50` | `100` | Max log size |

### Maintenance Mode
| Variable | Value | Description |
|----------|-------|-------------|
| `MAINTENANCE_MODE` | `false` | Enable maintenance |
| `MAINTENANCE_MESSAGE` | `"Under maintenance..."` | Display message |
| `MAINTENANCE_WHITELIST_IPS` | `127.0.0.1,::1` | Allowed IPs |

---

## 🔧 New Configuration Methods

### Config Class Methods
```php
// Session configuration
$sessionConfig = Config::session();
// Returns: ['name', 'lifetime', 'secure', 'httponly', 'samesite', 'regenerate_interval']

// Database configuration (enhanced)
$dbConfig = Config::database();
// Returns: ['host', 'port', 'database', 'username', 'password', 'charset', 'timeout', 'max_retries', 'persistent']

// Check maintenance mode
if (Config::isMaintenance()) {
    // Show maintenance page
}

// Clear config cache
Config::clearCache();
```

### Database Connection Features
```php
// Connection with retry mechanism
// - Attempt 1: immediate
// - Attempt 2: wait 2 seconds
// - Attempt 3: wait 4 seconds
// - Max wait: 5 seconds

// Health check function
if (checkDatabaseHealth($pdo)) {
    echo "Database is healthy";
}
```

---

## 🚨 Important Notes

### Environment Switching
```bash
# ALWAYS verify which environment is active
cat .env | head -1

# Development
cp .env.development .env

# Production
cp .env.production .env
```

### Security Checklist
- [ ] `.env` file has proper permissions (600)
- [ ] `.env` is listed in `.gitignore`
- [ ] Production uses `APP_ENV=production`
- [ ] Production uses `APP_DEBUG=false`
- [ ] Production uses `SESSION_SECURE=true`
- [ ] Database credentials are strong
- [ ] `test_connection.php` is removed in production

### File Permissions
```bash
chmod 600 .env .env.development .env.production
chmod 755 logs/ cache/ uploads/
chmod 644 config/*.php
```

---

## 🐛 Troubleshooting Guide

### Database Connection Failed
**Symptoms:** Error message "Database connection failed"

**Solutions:**
1. Verify database server is running:
   ```bash
   systemctl status mysql
   # or
   service mysql status
   ```

2. Test with CLI:
   ```bash
   php test_connection.php
   ```

3. Check credentials in `.env`:
   ```bash
   cat .env | grep DB_
   ```

4. Check error logs:
   ```bash
   cat logs/db_error.log
   ```

5. Verify database user permissions:
   ```sql
   SHOW GRANTS FOR 'your_user'@'localhost';
   ```

### Headers Already Sent Warning
**Symptoms:** "Cannot modify header information - headers already sent"

**Solutions:**
- Normal in CLI test scripts
- If in web requests, check for output before `config.php`
- Remove any echo/print before including config files

### Session Issues
**Symptoms:** Not staying logged in, session data lost

**Solutions:**
1. Clear browser cookies
2. Check session directory permissions:
   ```bash
   ls -ld /var/lib/php/sessions
   ```
3. Verify `SESSION_NAME` is unique in `.env`
4. Check `SESSION_LIFETIME` value

### Maintenance Mode Stuck
**Symptoms:** Can't access site, maintenance page shows

**Solutions:**
1. Set `MAINTENANCE_MODE=false` in `.env`
2. Add your IP to whitelist:
   ```env
   MAINTENANCE_WHITELIST_IPS=127.0.0.1,192.168.1.100
   ```
3. Login as admin (admins bypass maintenance mode)

---

**Last Updated:** 2025-10-07
**Version:** 2.1 (Complete Enhancement)
**Status:** ✅ Tested & Verified
