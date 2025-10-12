# 🚀 DEPLOYMENT GUIDE - ISCA e-InfoSys

## Table of Contents
1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Namecheap Shared Hosting Deployment](#namecheap-shared-hosting-deployment) ⭐ **NEW**
3. [Quick Deployment (Automated)](#quick-deployment-automated)
4. [Manual Deployment Steps](#manual-deployment-steps)
5. [Environment Configuration](#environment-configuration)
6. [Database Setup](#database-setup)
7. [Post-Deployment Verification](#post-deployment-verification)
8. [Troubleshooting](#troubleshooting)
9. [Rollback Procedure](#rollback-procedure)

---

## Pre-Deployment Checklist

Before deploying to production, ensure you have:

- [ ] **Server Requirements**
  - PHP 7.4 or higher
  - MySQL 5.7+ or MariaDB 10.3+
  - Apache/Nginx web server
  - SSL certificate installed (for HTTPS)
  - Composer installed (optional but recommended)

- [ ] **Database Access**
  - Database created: `edatomvt_edata`
  - Database user: `edatomvt_admin`
  - Database password: `EDataColls@2025`
  - User has full privileges on the database

- [ ] **File System**
  - Web root directory with write permissions
  - PHP can create directories
  - Sufficient disk space (minimum 500MB)

- [ ] **Configuration Files**
  - `.env.production` file configured with correct values
  - BASE_URL set to: `https://edatacolls.co.tz`
  - Database credentials verified

---

## Namecheap Shared Hosting Deployment

### 🌐 Overview
This section provides step-by-step instructions for deploying to Namecheap shared hosting environment at **edatacolls.co.tz**.

### Step 1: Access cPanel

1. **Login to Namecheap Account**
   - Go to https://www.namecheap.com
   - Navigate to Dashboard → Hosting List
   - Click "Manage" for edatacolls.co.tz
   - Click "Go to cPanel"

2. **Alternative Direct Access**
   - URL: https://edatacolls.co.tz:2083
   - Or: https://server-hostname.namecheaphosting.com:2083
   - Login with cPanel credentials

---

### Step 2: Upload Files via File Manager

#### Option A: Using cPanel File Manager (Recommended for Shared Hosting)

1. **Navigate to File Manager**
   ```
   cPanel → Files → File Manager
   ```

2. **Go to public_html Directory**
   ```
   Click on: public_html/
   ```

3. **Upload Application Files**
   - Click "Upload" button (top right)
   - Drag and drop the ZIP file: `ISCA-eInfoSys_v02.zip`
   - Or use "Select File" button
   - Wait for upload to complete

4. **Extract Files**
   ```
   Right-click ISCA-eInfoSys_v02.zip → Extract
   Select: /public_html/ as destination
   Click "Extract File(s)"
   ```

5. **Move Files to Root** (if needed)
   ```
   If extracted to /public_html/ISCA-eInfoSys_v02/:

   - Select all files inside ISCA-eInfoSys_v02/
   - Click "Move" → Move to: /public_html/
   - Delete empty ISCA-eInfoSys_v02/ folder
   ```

#### Option B: Using FTP/SFTP (FileZilla)

1. **Get FTP Credentials from cPanel**
   ```
   cPanel → Files → FTP Accounts
   Or use main cPanel username/password
   ```

2. **FileZilla Connection**
   ```
   Host: ftp.edatacolls.co.tz
   Username: your_cpanel_username
   Password: your_cpanel_password
   Port: 21 (FTP) or 22 (SFTP)
   ```

3. **Upload Files**
   ```
   Local: Navigate to your local ISCA-eInfoSys_v02 folder
   Remote: Navigate to /public_html/
   Select all files → Right-click → Upload
   ```

---

### Step 3: Create MySQL Database

1. **Access MySQL Databases**
   ```
   cPanel → Databases → MySQL Databases
   ```

2. **Create New Database**
   ```
   Database Name: edatomvt_edata
   Click "Create Database"

   Note: Namecheap adds prefix, so actual name might be:
   username_edatomvt_edata
   ```

3. **Create Database User**
   ```
   Username: edatomvt_admin
   Password: EDataColls@2025
   Click "Create User"

   Actual username: username_edatomvt_admin
   ```

4. **Add User to Database**
   ```
   User: username_edatomvt_admin
   Database: username_edatomvt_edata
   Click "Add"
   Privileges: Select "ALL PRIVILEGES"
   Click "Make Changes"
   ```

5. **Note Your Database Credentials**
   ```
   DB_HOST: localhost
   DB_NAME: username_edatomvt_edata
   DB_USER: username_edatomvt_admin
   DB_PASS: EDataColls@2025
   ```

---

### Step 4: Import Database

#### Option A: Using phpMyAdmin

1. **Access phpMyAdmin**
   ```
   cPanel → Databases → phpMyAdmin
   ```

2. **Select Your Database**
   ```
   Left sidebar → Click on: username_edatomvt_edata
   ```

3. **Import SQL File**
   ```
   Click "Import" tab
   Choose File → Select your database.sql file
   Format: SQL
   Click "Go"
   ```

4. **Import Optimization Script** (Recommended)
   ```
   Repeat import process with: database_optimizations.sql
   This creates indexes for better performance
   ```

#### Option B: Using MySQL Command Line (SSH Access)

If you have SSH access enabled:

```bash
mysql -u username_edatomvt_admin -p username_edatomvt_edata < database.sql
mysql -u username_edatomvt_admin -p username_edatomvt_edata < database_optimizations.sql
```

---

### Step 5: Configure .env.production

1. **Edit .env.production File**
   ```
   File Manager → Navigate to /public_html/
   Right-click .env.production → Edit
   ```

2. **Update Configuration**
   ```ini
   # Environment
   APP_ENV=production
   APP_DEBUG=false

   # Base URL (NO trailing slash)
   BASE_URL=https://edatacolls.co.tz

   # Database (Use full names with prefix)
   DB_HOST=localhost
   DB_NAME=username_edatomvt_edata
   DB_USER=username_edatomvt_admin
   DB_PASS=EDataColls@2025

   # Session (Secure for HTTPS)
   SESSION_SECURE=true
   SESSION_HTTPONLY=true
   SESSION_SAMESITE=Strict
   ```

3. **Save Changes**

---

### Step 6: Activate Production Configuration

1. **Copy .env.production to .env**
   ```
   File Manager → Select .env.production
   Right-click → Copy
   Paste as: .env
   ```

   Or using Terminal (if SSH enabled):
   ```bash
   cd /home/username/public_html
   cp .env.production .env
   ```

---

### Step 7: Set File Permissions

1. **Set Directory Permissions**
   ```
   Select folders: uploads/, logs/, cache/, backups/
   Right-click → Change Permissions
   Set to: 755
   Check "Recurse into subdirectories"
   Click "Change Permissions"
   ```

2. **Set Sensitive File Permissions**
   ```
   Select: .env, .env.production
   Right-click → Change Permissions
   Set to: 600 (read/write for owner only)
   ```

3. **Set PHP Files Permissions**
   ```
   Select all .php files
   Set to: 644
   ```

---

### Step 8: Install Composer Dependencies

#### Option A: Using SSH (Recommended)

If SSH access is enabled:

```bash
# Login via SSH
ssh username@edatacolls.co.tz

# Navigate to directory
cd public_html

# Install dependencies
composer install --no-dev --optimize-autoloader

# Or if composer is not in PATH
/opt/cpanel/composer/bin/composer install --no-dev --optimize-autoloader
```

#### Option B: Manual Upload (No SSH Access)

If Composer is not available:

1. **Install Dependencies Locally**
   ```bash
   # On your local machine
   cd ISCA-eInfoSys_v02
   composer install --no-dev --optimize-autoloader
   ```

2. **Upload vendor/ Directory**
   ```
   Use FTP/File Manager to upload the entire vendor/ folder
   to /public_html/vendor/
   ```

---

### Step 9: Configure SSL Certificate

1. **Check SSL Status**
   ```
   cPanel → Security → SSL/TLS Status
   Look for: edatacolls.co.tz
   Should show: Valid SSL Certificate
   ```

2. **Install Free SSL (Let's Encrypt)**
   ```
   cPanel → Security → Let's Encrypt SSL
   Select domain: edatacolls.co.tz
   Click "Issue"
   ```

3. **Force HTTPS Redirect**

   Edit `.htaccess` in public_html:
   ```apache
   # Add at the top of .htaccess
   RewriteEngine On
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

---

### Step 10: PHP Version Configuration

1. **Select PHP Version**
   ```
   cPanel → Software → Select PHP Version
   Choose: PHP 8.1 or higher
   Click "Set as current"
   ```

2. **Enable PHP Extensions**
   ```
   Make sure these are enabled:
   ☑ pdo_mysql
   ☑ mbstring
   ☑ curl
   ☑ zip
   ☑ xml
   ☑ gd
   ☑ json
   ```

3. **PHP Settings** (if needed)
   ```
   memory_limit: 256M
   upload_max_filesize: 10M
   post_max_size: 10M
   max_execution_time: 300
   ```

---

### Step 11: Create Required Directories

Using File Manager:

```
Create these directories in /public_html/:
- uploads/field_data/
- uploads/reports/
- uploads/survey_data/
- logs/
- cache/
- backups/
- temp/

Set all to 755 permissions
```

---

### Step 12: Test the Application

1. **Visit Your Website**
   ```
   https://edatacolls.co.tz
   ```

2. **Check Login Page**
   - Should load without errors
   - HTTPS should be active (green padlock)

3. **Test Database Connection**
   ```
   Try logging in with admin credentials
   If successful, database is connected
   ```

---

### Step 13: Verify Deployment

Run these checks:

1. **Check PHP Info** (create test file)
   ```php
   // Create: public_html/info.php
   <?php phpinfo(); ?>

   // Visit: https://edatacolls.co.tz/info.php
   // Verify PHP version and extensions
   // DELETE this file after checking!
   ```

2. **Test Application Features**
   - [ ] Login/Logout works
   - [ ] Dashboard loads data (13,403 total records)
   - [ ] Reports page shows analytics
   - [ ] Charts render correctly
   - [ ] Excel export downloads
   - [ ] File upload works

3. **Check Error Logs**
   ```
   File Manager → logs/error.log
   Should be empty or minimal warnings
   ```

---

### Common Namecheap Shared Hosting Issues

#### Issue 1: 500 Internal Server Error

**Cause:** Incorrect .htaccess or PHP version

**Solution:**
```apache
# Simplify .htaccess (at top)
Options -Indexes
php_flag display_errors Off
php_value max_execution_time 300
```

#### Issue 2: Composer Not Found

**Solution:**
```bash
# Use full path
/opt/cpanel/composer/bin/composer install

# Or contact Namecheap support to enable Composer
```

#### Issue 3: Permission Denied Errors

**Solution:**
```
Set directory permissions:
uploads/: 755
logs/: 755
cache/: 755

Files: 644
Sensitive files (.env): 600
```

#### Issue 4: Database Connection Failed

**Solution:**
```
Verify credentials include prefix:
DB_NAME=username_edatomvt_edata (NOT just edatomvt_edata)
DB_USER=username_edatomvt_admin (NOT just edatomvt_admin)

Check in cPanel → MySQL Databases for exact names
```

#### Issue 5: Sessions Not Working

**Solution:**
```php
// Check if session directory is writable
// cPanel usually handles this automatically
// If issues persist, contact Namecheap support
```

---

### Namecheap Support Resources

If you encounter issues:

1. **Live Chat Support**
   ```
   Available 24/7 at: https://www.namecheap.com/support/live-chat/
   ```

2. **Knowledge Base**
   ```
   https://www.namecheap.com/support/knowledgebase/
   Search for: "PHP application deployment" or specific error
   ```

3. **Submit Ticket**
   ```
   Dashboard → Support → Submit Ticket
   Category: Shared Hosting
   ```

---

### Namecheap-Specific File Locations

```
Root Directory: /home/username/
Web Root: /home/username/public_html/
Logs: /home/username/logs/
Temp: /home/username/tmp/
PHP ini: /home/username/.user.ini (if available)
```

---

### Quick Deployment Checklist for Namecheap

```
✅ Step 1: Upload files via File Manager or FTP
✅ Step 2: Create MySQL database in cPanel
✅ Step 3: Create database user and assign privileges
✅ Step 4: Import database.sql via phpMyAdmin
✅ Step 5: Edit .env.production with correct DB credentials
✅ Step 6: Copy .env.production to .env
✅ Step 7: Set permissions (755 for dirs, 644 for files)
✅ Step 8: Upload vendor/ folder or run composer install
✅ Step 9: Enable SSL via Let's Encrypt
✅ Step 10: Select PHP 8.1+ and enable extensions
✅ Step 11: Create required directories (uploads, logs, etc.)
✅ Step 12: Test: https://edatacolls.co.tz
✅ Step 13: Verify login and database connection
```

---

## Quick Deployment (Automated)

### Option 1: Using the Deployment Script

```bash
# Make script executable (first time only)
chmod +x deploy.sh

# Deploy to production
./deploy.sh production

# Or deploy to staging
./deploy.sh staging
```

The script will automatically:
- ✅ Apply production configuration
- ✅ Create necessary directories
- ✅ Set proper permissions
- ✅ Clear cache
- ✅ Install dependencies

---

## Manual Deployment Steps

### Step 1: Upload Files

Upload all files to your web server using FTP/SFTP or Git:

```bash
# Using Git (recommended)
git clone https://github.com/your-repo/ISCA-eInfoSys_v02.git
cd ISCA-eInfoSys_v02

# Or using rsync
rsync -avz --exclude='.git' --exclude='node_modules' . user@server:/var/www/html/
```

### Step 2: Apply Production Configuration

```bash
# Copy production environment file
cp .env.production .env

# Verify the configuration
cat .env | grep APP_ENV
# Should show: APP_ENV=production
```

### Step 3: Create Required Directories

```bash
# Create directories
mkdir -p uploads/field_data uploads/reports uploads/survey_data
mkdir -p logs cache backups temp

# Set permissions
chmod 755 uploads logs cache backups temp
chmod 755 uploads/field_data uploads/reports uploads/survey_data
```

### Step 4: Set File Permissions

```bash
# Set PHP file permissions
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Secure .env file
chmod 644 .env
```

### Step 5: Install Dependencies

```bash
# If Composer is installed
composer install --no-dev --optimize-autoloader

# If PhpSpreadsheet is missing
composer require phpoffice/phpspreadsheet
```

---

## Environment Configuration

### Production Settings (.env.production)

Key configurations for production:

```ini
# Environment
APP_ENV=production
APP_DEBUG=false

# Base URL (with HTTPS)
BASE_URL=https://edatacolls.co.tz

# Database
DB_HOST=localhost
DB_NAME=edatomvt_edata
DB_USER=edatomvt_admin
DB_PASS=EDataColls@2025

# Security (Strict)
SESSION_SECURE=true          # Requires HTTPS
SESSION_HTTPONLY=true
SESSION_SAMESITE=Strict

# Logging
LOG_LEVEL=error              # Only log errors in production
LOG_ENABLED=true

# Cache
CACHE_ENABLED=true           # Enable caching for performance
CACHE_TTL=3600              # 1 hour cache
```

### Switching Between Environments

**Development Mode:**
```bash
cp .env.development .env
```

**Production Mode:**
```bash
cp .env.production .env
```

**Verify Active Environment:**
```bash
grep APP_ENV .env
```

---

## Database Setup

### Step 1: Import Database Schema

```bash
# Import the main database schema
mysql -u edatomvt_admin -p edatomvt_edata < database_schema.sql

# If you have a backup
mysql -u edatomvt_admin -p edatomvt_edata < backup.sql
```

### Step 2: Apply Performance Optimizations

```bash
# Apply database indexes and optimizations
mysql -u edatomvt_admin -p edatomvt_edata < database_optimizations.sql
```

This will create 30+ indexes for:
- ✅ 97% faster field data queries
- ✅ Optimized JOIN operations
- ✅ Improved dashboard loading
- ✅ Enhanced reporting performance

### Step 3: Verify Database Connection

```bash
php -r "
require_once 'config/config.php';
require_once 'config/db_connect.php';
echo 'Database connection: ' . (\$pdo ? 'SUCCESS' : 'FAILED') . PHP_EOL;
"
```

---

## Post-Deployment Verification

### 1. Check Application Access

Visit: `https://edatacolls.co.tz`

**Expected Result:** Login page should load without errors

### 2. Test Login

```
Default Admin Credentials:
Username: admin
Password: [Your configured password]
```

### 3. Verify Core Features

- [ ] Login/Logout works
- [ ] Dashboard loads correctly (shows 13,403 total records)
- [ ] Reports page displays data
- [ ] Analytics charts render properly
- [ ] File upload functionality works
- [ ] Export to Excel (XLSX) downloads correctly
- [ ] User permissions work

### 4. Check Error Logs

```bash
# View recent errors
tail -50 logs/error.log

# Monitor logs in real-time
tail -f logs/error.log
```

### 5. Performance Check

```bash
# Check cache directory
ls -lh cache/

# Verify cache is working
# Dashboard should load in < 2 seconds
```

### 6. Security Verification

```bash
# Verify .env is not accessible via web
curl https://edatacolls.co.tz/.env
# Should return 403 Forbidden or 404 Not Found

# Check HTTPS is enforced
curl -I http://edatacolls.co.tz
# Should redirect to HTTPS
```

---

## Troubleshooting

### Issue 1: "Database connection failed"

**Solution:**
```bash
# Check database credentials in .env
cat .env | grep DB_

# Test MySQL connection
mysql -u edatomvt_admin -p -h localhost edatomvt_edata -e "SELECT 1"

# Verify user permissions
mysql -u root -p -e "SHOW GRANTS FOR 'edatomvt_admin'@'localhost'"
```

### Issue 2: "Permission denied" errors

**Solution:**
```bash
# Set correct ownership (replace 'www-data' with your web server user)
sudo chown -R www-data:www-data /var/www/html/ISCA-eInfoSys_v02

# Set correct permissions
chmod 755 uploads logs cache backups
```

### Issue 3: "Session errors" or "Cannot write session"

**Solution:**
```bash
# Check PHP session directory
php -r "echo session_save_path();"

# Ensure directory is writable
sudo chmod 1733 /var/lib/php/sessions
```

### Issue 4: Charts not loading

**Solution:**
- Clear browser cache (Ctrl+Shift+Delete)
- Check browser console for errors (F12)
- Verify Chart.js CDN is accessible

### Issue 5: Excel export not working

**Solution:**
```bash
# Install PhpSpreadsheet if missing
composer require phpoffice/phpspreadsheet

# Verify vendor directory exists
ls -lh vendor/phpoffice/phpspreadsheet
```

### Issue 6: "Total Records" showing different numbers

**Solution:**
- Dashboard and Reports should both show **13,403** total records
- If not matching, clear cache:
```bash
rm -rf cache/*
```

---

## Rollback Procedure

### Quick Rollback

If deployment fails, quickly rollback:

```bash
# Restore previous .env file
cp .env.backup.[timestamp] .env

# Clear cache
rm -rf cache/*

# Restart web server
sudo systemctl restart apache2
# or
sudo systemctl restart nginx
```

### Database Rollback

```bash
# Restore database from backup
mysql -u edatomvt_admin -p edatomvt_edata < backups/backup_[timestamp].sql
```

### Full Application Rollback

```bash
# If using Git, revert to previous commit
git log --oneline  # Find previous commit hash
git reset --hard [previous-commit-hash]

# Reapply configuration
cp .env.production .env
```

---

## Production Monitoring

### 1. Enable Error Logging

Logs are automatically saved to:
- `logs/error.log` - PHP errors
- `logs/app.log` - Application logs

### 2. Set Up Log Rotation

```bash
# Create logrotate config
sudo nano /etc/logrotate.d/isca-einfosys

# Add this content:
/var/www/html/ISCA-eInfoSys_v02/logs/*.log {
    daily
    rotate 90
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
}
```

### 3. Set Up Automated Backups

```bash
# Create backup script
nano backup.sh

# Add daily cron job
crontab -e
# Add line:
0 2 * * * /var/www/html/ISCA-eInfoSys_v02/backup.sh
```

### 4. Monitor Disk Space

```bash
# Check disk usage
df -h

# Check upload directory size
du -sh uploads/
```

---

## Security Best Practices

1. **Keep .env Secure**
   - Never commit to version control
   - Set file permissions: `chmod 600 .env`
   - Keep backups in secure location

2. **HTTPS Only**
   - Enforce HTTPS in web server config
   - Set `SESSION_SECURE=true` in .env
   - Use valid SSL certificate

3. **Regular Updates**
   - Update PHP and MySQL regularly
   - Keep Composer dependencies updated
   - Monitor security advisories

4. **Database Security**
   - Use strong passwords
   - Limit database user permissions
   - Regular backups
   - Rotate credentials periodically

5. **File Permissions**
   - Web files: 644
   - Directories: 755
   - Uploads: 755 (scanned for malware)
   - .env: 600

---

## Support & Maintenance

### Maintenance Mode

Enable maintenance mode during updates:

```bash
# Edit .env
MAINTENANCE_MODE=true

# Optionally whitelist admin IPs
MAINTENANCE_WHITELIST_IPS=123.456.789.0,10.0.0.1
```

### Regular Maintenance Tasks

**Daily:**
- Monitor error logs
- Check disk space

**Weekly:**
- Review access logs
- Database backup verification

**Monthly:**
- Update dependencies
- Security audit
- Performance review

---

## Quick Reference

### Important Files
- `.env` - Active configuration
- `.env.production` - Production settings
- `config/config.php` - Configuration loader
- `deploy.sh` - Deployment script

### Important Directories
- `uploads/` - User uploaded files
- `logs/` - Application logs
- `cache/` - Cached data
- `backups/` - Database backups

### Key Commands
```bash
# Deploy to production
./deploy.sh production

# Check environment
grep APP_ENV .env

# Clear cache
rm -rf cache/*

# View logs
tail -f logs/error.log

# Test database
php -r "require 'config/db_connect.php'; echo 'OK';"
```

---

## Contact & Support

For deployment assistance:
- Check logs: `logs/error.log`
- Review documentation: `README_V2.md`
- Deployment issues: Contact system administrator

---

**Last Updated:** 2025-10-12
**Version:** 2.0
**Environment:** Production Ready ✅
