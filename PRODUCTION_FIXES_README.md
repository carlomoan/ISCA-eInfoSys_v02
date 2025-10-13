# Production Fixes - eDataColls System
**Date:** 2025-10-13
**Production URL:** https://edatacolls.co.tz/
**Database:** edatomvt_edata

## Issues Reported by Client

1. **Field Upload Error** - Invalid datetime format: `'Thu Sep 25 08:01:28 UTC 2025'`
2. **Lab Upload Error** - Table `temp_Lab_sorter` doesn't exist
3. **Registration Form Error** - Error submitting form
4. **Upload Report Error** - Request failed error
5. **Create Guest Account** - Username: Guest_1, Password: Guest@2025

---

## Root Cause Analysis

### Issue 1: Field Upload DateTime Error
**Error:** `SQLSTATE[22007]: Invalid datetime format: 1292 Incorrect datetime value: 'Thu Sep 25 08:01:28 UTC 2025' for column start at row 1`

**Root Cause:**
- The CSV/Excel file contains datetime values in string format like `'Thu Sep 25 08:01:28 UTC 2025'`
- MySQL cannot parse this format into a DATETIME column
- The `temp_field_collector` table has `start` and `end` columns defined as `DATETIME`

**Solution:** Change the `start` and `end` columns from `DATETIME` to `VARCHAR(255)` to accept any datetime string format.

### Issue 2: Lab Upload Table Missing
**Error:** `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'edatomvt_edata.temp_Lab_sorter' doesn't exist`

**Root Cause:**
- The code references `temp_Lab_sorter` (with capital L)
- MySQL on Linux is case-sensitive for table names
- The table may have been created as `temp_lab_sorter` (lowercase)

**Solution:** Create both `temp_Lab_sorter` (capital L) and `temp_lab_sorter` (lowercase) for compatibility.

### Issue 3: Registration Form Error
**Root Cause:**
- The JavaScript makes a fetch request to `/api/auth/register_user_pub_api.php`
- The BASE_URL may not be correctly set in JavaScript context
- Network/CORS issues or server-side validation errors

**Solution:** Ensure BASE_URL is properly defined and check server-side validation.

### Issue 4: Upload Report Error
**Root Cause:**
- Permission check may be failing
- Database table `uploaded_reports` may not exist or have wrong structure
- File upload directory permissions issue

**Solution:** Verify table exists, check permissions, and ensure upload directory is writable.

---

## FIXES TO APPLY

### Fix 1: Update Database Tables (CRITICAL)

**Run this SQL script on production database:**

```bash
# Access cPanel > phpMyAdmin > Select database: edatomvt_edata
# Go to SQL tab and run the script: database/migrations/fix_production_errors.sql
```

The script will:
- Recreate `temp_field_collector` with VARCHAR columns for datetime fields
- Create `temp_Lab_sorter` table (case-sensitive)
- Create `temp_lab_sorter` table (lowercase) for compatibility
- Create indexes for better performance

### Fix 2: Create Guest User Account

**Option A: Run PHP Script (Recommended)**

```bash
# Via SSH or cPanel Terminal
cd /path/to/ISCA-eInfoSys_v02/database/migrations
php create_guest_user.php
```

**Option B: Run SQL Script**

If you can't run PHP, manually insert with a properly hashed password:

```sql
-- First, hash the password using PHP
-- password_hash('Guest@2025', PASSWORD_DEFAULT)
-- Then insert:

INSERT INTO users (fname, lname, email, phone, password, role_id, is_verified, user_project_id, created_at)
VALUES ('Guest', '1', 'guest1@edatacolls.co.tz', '+255000000001',
        '$2y$10$yourHashedPasswordHere',
        0, 1, 0, NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password);
```

**Login Credentials:**
- Email: `guest1@edatacolls.co.tz`
- Password: `Guest@2025`
- Phone: `+255000000001`

### Fix 3: Verify Upload Reports Table

Run this SQL to ensure the table exists:

```sql
CREATE TABLE IF NOT EXISTS `uploaded_reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `uploaded_by` INT NOT NULL,
  `report_type` VARCHAR(50) DEFAULT 'Uploaded',
  `round` INT NOT NULL,
  `cluster_name` VARCHAR(100) DEFAULT 'all',
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `uploaded_by` (`uploaded_by`),
  KEY `round` (`round`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Fix 4: Check Upload Directory Permissions

Via cPanel File Manager or SSH:

```bash
# Ensure uploads directory exists and is writable
mkdir -p /path/to/ISCA-eInfoSys_v02/uploads/reports
chmod 755 /path/to/ISCA-eInfoSys_v02/uploads/reports
chown www-data:www-data /path/to/ISCA-eInfoSys_v02/uploads/reports
```

---

## DEPLOYMENT STEPS

### Step 1: Backup Production Database

```bash
# Via cPanel > phpMyAdmin > Export
# Or via SSH:
mysqldump -u edatomvt_edata -p edatomvt_edata > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Step 2: Apply Database Fixes

1. Access cPanel
2. Go to phpMyAdmin
3. Select database: `edatomvt_edata`
4. Click "SQL" tab
5. Copy and paste contents of `database/migrations/fix_production_errors.sql`
6. Click "Go" to execute

### Step 3: Create Guest User

1. Open cPanel File Manager
2. Navigate to `/ISCA-eInfoSys_v02/database/migrations/`
3. Right-click `create_guest_user.php` and select "Code Edit"
4. OR use Terminal: `php create_guest_user.php`

### Step 4: Verify Fixes

Test each functionality:

#### Test 1: Field Upload
1. Login as admin (hbaraka2010@gmail.com / admin123)
2. Go to Data Entry page
3. Upload a Field data CSV/Excel file
4. Should process without datetime errors

#### Test 2: Lab Upload
1. Go to Data Entry page
2. Upload a Lab data CSV/Excel file
3. Should process without table missing errors

#### Test 3: Registration
1. Logout
2. Go to registration page
3. Fill out form and submit
4. Should see "Awaiting approval" message

#### Test 4: Upload Report
1. Login as user with upload_report permission
2. Go to Reports page
3. Upload a report file
4. Should upload successfully

#### Test 5: Guest Login
1. Logout
2. Login with:
   - Email: `guest1@edatacolls.co.tz`
   - Password: `Guest@2025`
3. Should login successfully

---

## VERIFICATION QUERIES

Run these SQL queries to verify everything is working:

```sql
-- 1. Check temp_field_collector exists
SELECT COUNT(*) as temp_field_collector_exists
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'edatomvt_edata'
AND TABLE_NAME = 'temp_field_collector';

-- 2. Check temp_Lab_sorter exists (case-sensitive)
SELECT COUNT(*) as temp_Lab_sorter_exists
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'edatomvt_edata'
AND TABLE_NAME = 'temp_Lab_sorter';

-- 3. Check temp_lab_sorter exists (lowercase)
SELECT COUNT(*) as temp_lab_sorter_exists
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'edatomvt_edata'
AND TABLE_NAME = 'temp_lab_sorter';

-- 4. Check Guest user exists
SELECT id, fname, lname, email, phone, is_verified, role_id
FROM users
WHERE email = 'guest1@edatacolls.co.tz';

-- 5. Check uploaded_reports table
SELECT COUNT(*) as uploaded_reports_exists
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'edatomvt_edata'
AND TABLE_NAME = 'uploaded_reports';

-- 6. Check column types for temp_field_collector
DESCRIBE temp_field_collector;
```

Expected results:
1. temp_field_collector_exists: 1
2. temp_Lab_sorter_exists: 1
3. temp_lab_sorter_exists: 1
4. Guest user: 1 row returned
5. uploaded_reports_exists: 1
6. `start` and `end` columns should be VARCHAR(255)

---

## TROUBLESHOOTING

### If Field Upload Still Fails

1. Check error log: `/ISCA-eInfoSys_v02/logs/db_error.log`
2. Verify table structure:
   ```sql
   SHOW CREATE TABLE temp_field_collector;
   ```
3. Ensure `start` and `end` are VARCHAR, not DATETIME

### If Lab Upload Still Fails

1. Check exact table name case:
   ```sql
   SHOW TABLES LIKE 'temp%sorter';
   ```
2. If table doesn't exist, manually run:
   ```sql
   -- Copy CREATE TABLE statement from fix_production_errors.sql
   ```

### If Registration Fails

1. Check browser console for JavaScript errors
2. Verify BASE_URL in page source
3. Check `/api/auth/register_user_pub_api.php` file exists
4. Check server error logs

### If Upload Report Fails

1. Check upload directory exists and is writable:
   ```bash
   ls -la /path/to/ISCA-eInfoSys_v02/uploads/reports
   ```
2. Verify `uploaded_reports` table exists
3. Check user has `upload_report` permission

### If Guest Login Fails

1. Verify user exists:
   ```sql
   SELECT * FROM users WHERE email = 'guest1@edatacolls.co.tz';
   ```
2. Reset password if needed:
   ```php
   <?php
   echo password_hash('Guest@2025', PASSWORD_DEFAULT);
   // Copy the hash and run:
   // UPDATE users SET password = 'hash_here' WHERE email = 'guest1@edatacolls.co.tz';
   ?>
   ```

---

## FILES INCLUDED

1. **database/migrations/fix_production_errors.sql** - Main database fix script
2. **database/migrations/create_guest_user.php** - PHP script to create guest user
3. **PRODUCTION_FIXES_README.md** - This documentation file

---

## IMPORTANT NOTES

1. **Backup First:** Always backup the production database before making changes
2. **Test After Each Fix:** Test each functionality after applying fixes
3. **Monitor Logs:** Check error logs for any issues
4. **Case Sensitivity:** MySQL on Linux is case-sensitive for table names
5. **Permissions:** Ensure file upload directories have proper permissions

---

## SUPPORT

If issues persist after applying all fixes:

1. Check server error logs: `/var/log/apache2/error.log` or via cPanel
2. Check PHP error logs: Look in cPanel Error Logs
3. Enable debug mode temporarily:
   - Edit `.env` file
   - Set `APP_DEBUG=true`
   - Test the failing functionality
   - Check detailed error messages
   - Set `APP_DEBUG=false` when done

---

## SUMMARY

Priority fixes to apply immediately:

1. **CRITICAL:** Run `fix_production_errors.sql` to fix database tables
2. **CRITICAL:** Create Guest user via `create_guest_user.php`
3. **HIGH:** Verify upload directories exist and are writable
4. **MEDIUM:** Test all functionalities listed above

All fixes are backward compatible and won't break existing functionality.

---

**Last Updated:** 2025-10-13
**Contact:** System Administrator
