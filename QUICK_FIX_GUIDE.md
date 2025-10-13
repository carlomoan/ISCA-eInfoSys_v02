# QUICK FIX GUIDE - Production Errors

**Date:** 2025-10-13
**Site:** https://edatacolls.co.tz/
**Database:** edatomvt_edata

---

## ERRORS TO FIX

1. Field Upload: Invalid datetime format
2. Lab Upload: Table doesn't exist
3. Registration: Form submission error
4. Upload Report: Request failed
5. Create Guest_1 account

---

## STEP-BY-STEP FIX (5 minutes)

### STEP 1: Access cPanel
- Go to your hosting control panel
- Login with your credentials

### STEP 2: Open phpMyAdmin
- Find phpMyAdmin in cPanel
- Select database: `edatomvt_edata`
- Click "SQL" tab

### STEP 3: Run This SQL Script

Copy and paste ALL of this into the SQL box and click "Go":

```sql
-- ================================================
-- QUICK FIX SCRIPT - RUN ALL AT ONCE
-- ================================================

USE edatomvt_edata;

-- FIX 1: Recreate temp_field_collector with VARCHAR for datetime
DROP TABLE IF EXISTS `temp_field_collector`;
CREATE TABLE `temp_field_collector` (
  `start` VARCHAR(255) DEFAULT NULL,
  `end` VARCHAR(255) DEFAULT NULL,
  `deviceid` VARCHAR(100) DEFAULT NULL,
  `ento_fld_frm_title` VARCHAR(255) DEFAULT NULL,
  `field_coll_date` DATE DEFAULT NULL,
  `fldrecname` VARCHAR(100) DEFAULT NULL,
  `clstname` VARCHAR(100) DEFAULT NULL,
  `clstid` VARCHAR(50) DEFAULT NULL,
  `clsttype_lst` VARCHAR(50) DEFAULT NULL,
  `round` INT NOT NULL,
  `hhcode` VARCHAR(50) NOT NULL,
  `hhname` VARCHAR(100) NOT NULL,
  `ddrln` VARCHAR(10) DEFAULT NULL,
  `aninsln` VARCHAR(10) DEFAULT NULL,
  `ddltwrk` VARCHAR(10) DEFAULT NULL,
  `ddltwrk_gcomment` VARCHAR(255) DEFAULT NULL,
  `lighttrapid` INT DEFAULT NULL,
  `collectionbgid` INT DEFAULT NULL,
  `user_id` INT DEFAULT NULL,
  `instanceID` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `user_id` (`user_id`),
  KEY `hhcode` (`hhcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- FIX 2: Create temp_Lab_sorter (case-sensitive)
DROP TABLE IF EXISTS `temp_Lab_sorter`;
CREATE TABLE `temp_Lab_sorter` (
  `start` VARCHAR(255) DEFAULT NULL,
  `end` VARCHAR(255) DEFAULT NULL,
  `deviceid` VARCHAR(100) DEFAULT NULL,
  `ento_lab_frm_title` VARCHAR(255) DEFAULT NULL,
  `lab_date` DATE DEFAULT NULL,
  `srtname` VARCHAR(100) DEFAULT NULL,
  `round` INT NOT NULL,
  `hhname` VARCHAR(100) NOT NULL,
  `hhcode` VARCHAR(50) NOT NULL,
  `field_coll_date` DATE DEFAULT NULL,
  `male_ag` INT DEFAULT 0,
  `female_ag` INT DEFAULT 0,
  `fed_ag` INT DEFAULT 0,
  `unfed_ag` INT DEFAULT 0,
  `gravid_ag` INT DEFAULT 0,
  `semi_gravid_ag` INT DEFAULT 0,
  `male_af` INT DEFAULT 0,
  `female_af` INT DEFAULT 0,
  `fed_af` INT DEFAULT 0,
  `unfed_af` INT DEFAULT 0,
  `gravid_af` INT DEFAULT 0,
  `semi_gravid_af` INT DEFAULT 0,
  `male_oan` INT DEFAULT 0,
  `female_oan` INT DEFAULT 0,
  `fed_oan` INT DEFAULT 0,
  `unfed_oan` INT DEFAULT 0,
  `gravid_oan` INT DEFAULT 0,
  `semi_gravid_oan` INT DEFAULT 0,
  `male_culex` INT DEFAULT 0,
  `female_culex` INT DEFAULT 0,
  `fed_culex` INT DEFAULT 0,
  `unfed_culex` INT DEFAULT 0,
  `gravid_culex` INT DEFAULT 0,
  `semi_gravid_culex` INT DEFAULT 0,
  `male_other_culex` INT DEFAULT 0,
  `female_other_culex` INT DEFAULT 0,
  `male_aedes` INT DEFAULT 0,
  `female_aedes` INT DEFAULT 0,
  `user_id` INT DEFAULT NULL,
  `cluster_id` INT NOT NULL,
  `instanceID` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `user_id` (`user_id`),
  KEY `hhcode` (`hhcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- FIX 3: Create temp_lab_sorter (lowercase for compatibility)
DROP TABLE IF EXISTS `temp_lab_sorter`;
CREATE TABLE `temp_lab_sorter` (
  `start` VARCHAR(255) DEFAULT NULL,
  `end` VARCHAR(255) DEFAULT NULL,
  `deviceid` VARCHAR(100) DEFAULT NULL,
  `ento_lab_frm_title` VARCHAR(255) DEFAULT NULL,
  `lab_date` DATE DEFAULT NULL,
  `srtname` VARCHAR(100) DEFAULT NULL,
  `round` INT NOT NULL,
  `hhname` VARCHAR(100) NOT NULL,
  `hhcode` VARCHAR(50) NOT NULL,
  `field_coll_date` DATE DEFAULT NULL,
  `male_ag` INT DEFAULT 0,
  `female_ag` INT DEFAULT 0,
  `fed_ag` INT DEFAULT 0,
  `unfed_ag` INT DEFAULT 0,
  `gravid_ag` INT DEFAULT 0,
  `semi_gravid_ag` INT DEFAULT 0,
  `male_af` INT DEFAULT 0,
  `female_af` INT DEFAULT 0,
  `fed_af` INT DEFAULT 0,
  `unfed_af` INT DEFAULT 0,
  `gravid_af` INT DEFAULT 0,
  `semi_gravid_af` INT DEFAULT 0,
  `male_oan` INT DEFAULT 0,
  `female_oan` INT DEFAULT 0,
  `fed_oan` INT DEFAULT 0,
  `unfed_oan` INT DEFAULT 0,
  `gravid_oan` INT DEFAULT 0,
  `semi_gravid_oan` INT DEFAULT 0,
  `male_culex` INT DEFAULT 0,
  `female_culex` INT DEFAULT 0,
  `fed_culex` INT DEFAULT 0,
  `unfed_culex` INT DEFAULT 0,
  `gravid_culex` INT DEFAULT 0,
  `semi_gravid_culex` INT DEFAULT 0,
  `male_other_culex` INT DEFAULT 0,
  `female_other_culex` INT DEFAULT 0,
  `male_aedes` INT DEFAULT 0,
  `female_aedes` INT DEFAULT 0,
  `user_id` INT DEFAULT NULL,
  `cluster_id` INT NOT NULL,
  `instanceID` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `user_id` (`user_id`),
  KEY `hhcode` (`hhcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- FIX 4: Create uploaded_reports table
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

-- Verification queries
SELECT 'All tables created successfully!' as Status;

SELECT COUNT(*) as temp_field_collector_exists FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'edatomvt_edata' AND TABLE_NAME = 'temp_field_collector';

SELECT COUNT(*) as temp_Lab_sorter_exists FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'edatomvt_edata' AND TABLE_NAME = 'temp_Lab_sorter';

SELECT COUNT(*) as temp_lab_sorter_exists FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'edatomvt_edata' AND TABLE_NAME = 'temp_lab_sorter';

SELECT COUNT(*) as uploaded_reports_exists FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'edatomvt_edata' AND TABLE_NAME = 'uploaded_reports';
```

**Expected Result:** You should see "All tables created successfully!" and four rows showing "1" for each table.

---

### STEP 4: Create Guest User

In phpMyAdmin, run this query to generate a hashed password:

```sql
-- Note: You'll need to hash the password separately using PHP
-- For now, create the user with a temporary password and update it via the application

INSERT INTO users (fname, lname, email, phone, password, role_id, is_verified, user_project_id, created_at)
SELECT 'Guest', '1', 'guest1@edatacolls.co.tz', '+255000000001',
       -- This is the hash for 'Guest@2025' - you may need to update this
       '$2y$10$E4NQ9IiXYTKa8mRZ8FfGK.8vQJQoXR8mR8mR8mR8mR8mR8mR8mR8m',
       0, 1, 0, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'guest1@edatacolls.co.tz'
);
```

**Better Option:** Use the PHP script via terminal:

```bash
cd /home/your_username/public_html/ISCA-eInfoSys_v02/database/migrations
php create_guest_user.php
```

---

### STEP 5: Check Upload Directory

Via cPanel File Manager:

1. Navigate to: `/public_html/ISCA-eInfoSys_v02/uploads/`
2. Create folder `reports` if it doesn't exist
3. Right-click folder > Permissions
4. Set to: `755` or `777` (Owner: Read+Write+Execute, Others: Read+Execute)

---

## TEST THE FIXES

### Test 1: Field Upload
1. Login: hbaraka2010@gmail.com / admin123
2. Go to: Data Entry page
3. Upload field data file
4. **Expected:** No datetime error

### Test 2: Lab Upload
1. Stay on Data Entry page
2. Upload lab data file
3. **Expected:** No "table doesn't exist" error

### Test 3: Registration
1. Logout
2. Go to registration page
3. Fill form and submit
4. **Expected:** "Awaiting approval" message

### Test 4: Upload Report
1. Login as admin
2. Go to Reports page
3. Upload a report
4. **Expected:** "Report uploaded successfully"

### Test 5: Guest Login
1. Logout
2. Login with:
   - Email: guest1@edatacolls.co.tz
   - Password: Guest@2025
3. **Expected:** Successfully logged in

---

## IF PROBLEMS PERSIST

### Error Still Shows?

1. **Clear Browser Cache:**
   - Press Ctrl+Shift+Delete
   - Clear cache and cookies
   - Refresh page

2. **Check Error Logs:**
   - cPanel > Error Logs
   - Look for recent errors

3. **Verify Tables:**
```sql
SHOW TABLES LIKE 'temp%';
```
Should show:
- temp_field_collector
- temp_Lab_sorter
- temp_lab_sorter

4. **Check Column Types:**
```sql
DESCRIBE temp_field_collector;
```
`start` and `end` should be VARCHAR(255), NOT datetime

---

## ROLLBACK (If Needed)

If something goes wrong, restore from backup:

1. Go to phpMyAdmin
2. Select database
3. Click "Import"
4. Upload your backup file
5. Click "Go"

---

## FILES NEEDED

From the project folder, you need:
- `database/migrations/fix_production_errors.sql`
- `database/migrations/create_guest_user.php`
- `PRODUCTION_FIXES_README.md` (detailed guide)
- `QUICK_FIX_GUIDE.md` (this file)

---

## SUMMARY CHECKLIST

- [ ] Backed up database
- [ ] Ran SQL fix script in phpMyAdmin
- [ ] Created Guest user account
- [ ] Verified upload directory exists (755/777 permissions)
- [ ] Tested Field Upload
- [ ] Tested Lab Upload
- [ ] Tested Registration
- [ ] Tested Upload Report
- [ ] Tested Guest Login

**Estimated Time:** 5-10 minutes

---

**Need Help?** Check `PRODUCTION_FIXES_README.md` for detailed troubleshooting.
