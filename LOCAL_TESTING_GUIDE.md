# Local Testing Guide - eDataColls

**Date:** 2025-10-13
**Local Server:** http://localhost:8000

---

## Issues Fixed for Local Testing

### 1. Upload Report Error (500 Internal Server Error) ✅

**Problem:**
- Browser console showed: `500 Internal Server Error` on `upload_report.php`
- Also `404 Not Found` on `/api/reports/upload_report.php`

**Root Cause:**
- Hardcoded paths using `$_SERVER['DOCUMENT_ROOT'] . '/ISCA-eInfoSys_v02/'`
- This doesn't work when project is in different directory
- Missing `uploaded_reports` table in local database

**Fixed:**
- ✅ Changed all hardcoded paths to relative paths using `__DIR__`
- ✅ Updated `ajax/upload_report.php`
- ✅ Updated `controllers/upload_report.php`
- ✅ Added better error messages for missing table
- ✅ Created SQL script to setup local database

**Files Modified:**
- [ajax/upload_report.php](./ajax/upload_report.php)
- [controllers/upload_report.php](./controllers/upload_report.php)

---

## Setup Local Database

Run this SQL script in your local database to create necessary tables:

```bash
# If using MySQL command line:
mysql -u your_username -p your_database < database/migrations/setup_local_dev.sql

# Or use phpMyAdmin:
# 1. Open phpMyAdmin
# 2. Select your database
# 3. Click SQL tab
# 4. Copy/paste contents of: database/migrations/setup_local_dev.sql
# 5. Click Go
```

**File:** [database/migrations/setup_local_dev.sql](./database/migrations/setup_local_dev.sql)

**Tables Created:**
- `temp_field_collector` - For field data uploads
- `temp_Lab_sorter` - For lab data uploads (capital L)
- `temp_lab_sorter` - For lab data uploads (lowercase)
- `uploaded_reports` - For report uploads

---

## Test Upload Report (Localhost)

### Step 1: Ensure Tables Exist

Run this query in your local database:
```sql
SELECT COUNT(*) FROM information_schema.TABLES
WHERE TABLE_NAME = 'uploaded_reports';
```

**Expected:** Should return `1`

If returns `0`, run:
```sql
CREATE TABLE `uploaded_reports` (
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

### Step 2: Ensure Upload Directory Exists

```bash
# Create the directory if it doesn't exist
mkdir -p /home/andrew/PhProjects/eDataColls/ISCA-eInfoSys_v02/uploads/reports

# Set permissions
chmod 755 /home/andrew/PhProjects/eDataColls/ISCA-eInfoSys_v02/uploads/reports
```

### Step 3: Test Upload

1. **Open Browser:** http://localhost:8000
2. **Login:** Use your admin credentials
3. **Navigate:** Reports → Upload Report tab
4. **Upload:** Select an Excel/CSV file
5. **Check Console:** Should see `200 OK` (not 500 or 404)

---

## Browser Console Errors

### CSS Warnings (Safe to Ignore) ⚠️

These are just browser compatibility warnings, not actual errors:
```
Unknown property '-moz-osx-font-smoothing'. Declaration dropped.
Ruleset ignored due to bad selector.
```

**What they mean:**
- `-moz-osx-font-smoothing` is a Firefox-specific property for Mac
- Some CSS selectors might not be recognized by your browser
- **These don't affect functionality** - safe to ignore

---

## Common Issues & Solutions

### Issue 1: "Database table 'uploaded_reports' does not exist"

**Solution:**
```sql
-- Run this in your local database:
source /path/to/database/migrations/create_uploaded_reports_table.sql;
```

### Issue 2: "Failed to move uploaded file"

**Solution:**
```bash
# Check if directory exists
ls -la uploads/reports/

# If not, create it:
mkdir -p uploads/reports

# Set permissions:
chmod 755 uploads/reports
```

### Issue 3: Still getting 500 error

**Solution:**
1. Check PHP error log:
   ```bash
   tail -f /var/log/apache2/error.log
   # OR
   tail -f /tmp/php-error.log
   ```

2. Enable error display temporarily:
   - Edit `.env`
   - Set `APP_DEBUG=true`
   - Refresh page
   - Check detailed error message

---

## Testing Checklist

### Local Development Tests

- [ ] **Database Setup**
  ```sql
  -- Run in local database:
  SELECT TABLE_NAME FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('temp_field_collector', 'temp_Lab_sorter', 'temp_lab_sorter', 'uploaded_reports');
  -- Should return 4 rows
  ```

- [ ] **Upload Directory**
  ```bash
  ls -la uploads/reports/
  # Should show directory exists with 755 permissions
  ```

- [ ] **Field Upload Test**
  - Navigate to: Data Entry → Field Upload
  - Upload a test CSV file
  - Should see: Success message (no datetime error)

- [ ] **Lab Upload Test**
  - Navigate to: Data Entry → Lab Upload
  - Upload a test CSV file
  - Should see: Success message (no table missing error)

- [ ] **Upload Report Test**
  - Navigate to: Reports → Upload Report
  - Upload an Excel file
  - Should see: `200 OK` in browser console
  - Should see: "Report uploaded successfully" message

---

## File Paths Fixed

### Before (Hardcoded - Won't work):
```php
require_once($_SERVER['DOCUMENT_ROOT'] . '/ISCA-eInfoSys_v02/config/db_connect.php');
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/ISCA-eInfoSys_v02/uploads/reports/';
```

### After (Relative - Works everywhere):
```php
require_once __DIR__ . '/../config/db_connect.php';
$uploadDir = dirname(__DIR__) . '/uploads/reports/';
```

---

## Production vs Development

| Feature | Development (localhost) | Production (edatacolls.co.tz) |
|---------|------------------------|-------------------------------|
| Path Type | Relative (`__DIR__`) | Relative (`__DIR__`) |
| Database | Local MySQL | Production MySQL |
| File Uploads | `./uploads/reports/` | `./uploads/reports/` |
| Error Display | Enabled (APP_DEBUG=true) | Disabled (APP_DEBUG=false) |
| Permissions | 755 | 755 (or 777 if needed) |

**Note:** The path fixes work on **both** development and production!

---

## Next Steps

### For Local Development:
1. ✅ Run `setup_local_dev.sql` in local database
2. ✅ Create `uploads/reports/` directory
3. ✅ Test all upload functionalities
4. ✅ Verify no 500/404 errors in console

### For Production Deployment:
1. ⚠️ Backup production database
2. 📝 Follow [QUICK_FIX_GUIDE.md](./QUICK_FIX_GUIDE.md)
3. 🚀 Apply fixes in 5-10 minutes
4. ✅ Test production site

---

## Summary

**Fixed Issues:**
- ✅ Upload report 500 error (path issue)
- ✅ Upload report 404 error (wrong endpoint)
- ✅ Missing uploaded_reports table
- ✅ Hardcoded paths don't work on localhost
- ✅ Better error messages for debugging

**Files Updated:**
- `ajax/upload_report.php` - Fixed paths + error handling
- `controllers/upload_report.php` - Fixed paths + error handling

**Files Created:**
- `database/migrations/setup_local_dev.sql` - Local DB setup
- `LOCAL_TESTING_GUIDE.md` - This guide

**Status:** Ready for testing on localhost:8000

---

**Last Updated:** 2025-10-13
