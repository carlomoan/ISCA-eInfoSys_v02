# ⚡ Quick Deployment - ISCA e-InfoSys

## 🌐 Namecheap Shared Hosting (15 minutes)

**Using Namecheap?** → [Jump to Namecheap Guide](#namecheap-shared-hosting-15-minutes)

---

## 🚀 One-Command Deployment (VPS/Dedicated Only)

```bash
chmod +x deploy.sh && ./deploy.sh production
```

---

## 📋 Pre-Flight Checklist (2 minutes)

1. **Database Ready?**
   ```bash
   mysql -u edatomvt_admin -p edatomvt_edata -e "SELECT 1"
   ```
   ✅ Connection successful → Continue
   ❌ Connection failed → Fix credentials in `.env.production`

2. **Files Uploaded?**
   ```bash
   ls -la | grep -E "(config|api|pages)"
   ```
   ✅ All directories present → Continue
   ❌ Missing directories → Upload all files

3. **SSL Certificate?**
   ```bash
   curl -I https://edatacolls.co.tz 2>&1 | grep -E "(200|301|302)"
   ```
   ✅ HTTPS working → Continue
   ❌ SSL error → Install SSL certificate first

---

## 🎯 Deployment Steps (5 minutes)

### Step 1: Deploy (30 seconds)
```bash
./deploy.sh production
```

### Step 2: Database (2 minutes)
```bash
mysql -u edatomvt_admin -p edatomvt_edata < database_optimizations.sql
```

### Step 3: Verify (1 minute)
```bash
# Test database connection
php -r "require 'config/config.php'; require 'config/db_connect.php'; echo 'DB: OK';"

# Check active environment
grep APP_ENV .env
```

### Step 4: Test Application (1 minute)
- Visit: `https://edatacolls.co.tz`
- Login with admin credentials
- Check dashboard shows **13,403 total records**
- Test Excel export

---

## ✅ Success Indicators

After deployment, verify these:

| Check | Expected Result | Command |
|-------|----------------|---------|
| Environment | `APP_ENV=production` | `grep APP_ENV .env` |
| Database | Connection OK | `php -r "require 'config/db_connect.php';"` |
| HTTPS | Redirects to HTTPS | `curl -I http://edatacolls.co.tz` |
| Login Page | Loads without errors | `curl -s https://edatacolls.co.tz | grep "Login"` |
| Logs | No fatal errors | `tail -20 logs/error.log` |

---

## 🔧 Common Issues & Quick Fixes

### Issue: Database connection failed
```bash
# Fix: Update credentials in .env
nano .env
# Change DB_USER, DB_PASS, DB_NAME
```

### Issue: Permission denied
```bash
# Fix: Set proper permissions
sudo chown -R www-data:www-data .
chmod 755 uploads logs cache
```

### Issue: Session errors
```bash
# Fix: Clear old sessions
rm -rf /tmp/php_sessions/*
```

### Issue: Charts not loading
```bash
# Fix: Clear cache
rm -rf cache/*
```

---

## 🔄 Switch Environments

**To Production:**
```bash
cp .env.production .env && rm -rf cache/*
```

**To Development:**
```bash
cp .env.development .env && rm -rf cache/*
```

**Verify Current Environment:**
```bash
grep -E "(APP_ENV|BASE_URL|DB_NAME)" .env
```

---

## 📱 Production Health Check

Run this after deployment:

```bash
echo "=== PRODUCTION HEALTH CHECK ==="
echo "Environment: $(grep APP_ENV .env | cut -d'=' -f2)"
echo "Base URL: $(grep BASE_URL .env | cut -d'=' -f2)"
echo "Database: $(grep DB_NAME .env | cut -d'=' -f2)"
echo ""
echo "Checking database connection..."
php -r "require 'config/db_connect.php'; echo 'Database: ✓ Connected\n';" 2>&1
echo ""
echo "Checking permissions..."
[ -w uploads ] && echo "Uploads: ✓ Writable" || echo "Uploads: ✗ Not writable"
[ -w logs ] && echo "Logs: ✓ Writable" || echo "Logs: ✗ Not writable"
[ -w cache ] && echo "Cache: ✓ Writable" || echo "Cache: ✗ Not writable"
echo ""
echo "Recent errors:"
tail -5 logs/error.log 2>/dev/null || echo "No errors found"
```

---

## 🆘 Emergency Rollback

If something goes wrong:

```bash
# Restore previous configuration
cp .env.backup.* .env

# Clear cache
rm -rf cache/*

# Restart web server
sudo systemctl restart apache2
```

---

## 📞 Quick Commands Reference

```bash
# View error logs
tail -f logs/error.log

# Clear all cache
rm -rf cache/*

# Test database
mysql -u edatomvt_admin -p edatomvt_edata -e "SELECT COUNT(*) FROM users"

# Check disk space
df -h

# Monitor web server
sudo systemctl status apache2

# View access logs
tail -f /var/log/apache2/access.log
```

---

## 🎓 Environment Variables Quick Reference

**Production (.env.production):**
- `APP_ENV=production`
- `APP_DEBUG=false`
- `BASE_URL=https://edatacolls.co.tz`
- `DB_NAME=edatomvt_edata`
- `SESSION_SECURE=true`
- `LOG_LEVEL=error`

**Development (.env.development):**
- `APP_ENV=development`
- `APP_DEBUG=true`
- `BASE_URL=http://localhost:8000`
- `DB_NAME=survey_amrc_db`
- `SESSION_SECURE=false`
- `LOG_LEVEL=debug`

---

## 🌐 Namecheap Shared Hosting (15 minutes)

### Overview
Deploying to **edatacolls.co.tz** on Namecheap shared hosting requires a different approach since you don't have command-line access.

---

### ⚡ Quick Steps

#### 1. Access cPanel (1 minute)
```
Login: https://www.namecheap.com
→ Dashboard → Hosting List
→ Manage edatacolls.co.tz
→ Go to cPanel
```

#### 2. Upload Files (5 minutes)
```
cPanel → File Manager → public_html/
→ Upload → ISCA-eInfoSys_v02.zip
→ Right-click ZIP → Extract
→ Move files to root if needed
```

**Alternative: FTP Upload**
```
Host: ftp.edatacolls.co.tz
Port: 21
Use cPanel credentials
Upload all files to /public_html/
```

#### 3. Create Database (3 minutes)
```
cPanel → MySQL Databases
→ Create Database: edatomvt_edata
→ Create User: edatomvt_admin
→ Password: EDataColls@2025
→ Add User to Database (ALL PRIVILEGES)

⚠️ Note the actual names (with prefix):
username_edatomvt_edata
username_edatomvt_admin
```

#### 4. Import Database (2 minutes)
```
cPanel → phpMyAdmin
→ Select: username_edatomvt_edata
→ Import tab
→ Choose file: database.sql
→ Click Go
→ Repeat with: database_optimizations.sql
```

#### 5. Configure Environment (2 minutes)
```
File Manager → public_html/.env.production
→ Right-click → Edit
→ Update:
   DB_NAME=username_edatomvt_edata
   DB_USER=username_edatomvt_admin
   BASE_URL=https://edatacolls.co.tz
→ Save
→ Copy .env.production to .env
```

#### 6. Set Permissions (1 minute)
```
Select: uploads/, logs/, cache/
→ Change Permissions → 755
→ Check "Recurse into subdirectories"

Select: .env
→ Change Permissions → 600
```

#### 7. Install Dependencies (1 minute)

**Option A: Upload vendor/ folder**
```
- Install locally: composer install --no-dev
- Upload entire vendor/ folder via FTP
```

**Option B: SSH (if enabled)**
```bash
ssh username@edatacolls.co.tz
cd public_html
/opt/cpanel/composer/bin/composer install --no-dev
```

#### 8. Enable SSL (if not already) (1 minute)
```
cPanel → Let's Encrypt SSL
→ Select: edatacolls.co.tz
→ Issue
```

#### 9. Configure PHP (1 minute)
```
cPanel → Select PHP Version
→ Choose: PHP 8.1 or higher
→ Enable extensions:
   ☑ pdo_mysql
   ☑ mbstring
   ☑ curl
   ☑ zip
```

#### 10. Test (1 minute)
```
Visit: https://edatacolls.co.tz
→ Should show login page
→ Test login with admin credentials
```

---

### 🔧 Common Namecheap Issues

| Issue | Solution |
|-------|----------|
| **Database connection failed** | Use full names: `username_edatomvt_edata` |
| **500 Error** | Check PHP version (must be 8.1+) |
| **Permission denied** | Set directories to 755, files to 644 |
| **Composer not found** | Upload vendor/ folder manually |
| **Sessions not working** | Contact Namecheap support |

---

### 📁 Important: Database Prefix

Namecheap adds your username as prefix:

```
What you enter:     edatomvt_edata
Actual name:        username_edatomvt_edata

What you enter:     edatomvt_admin
Actual username:    username_edatomvt_admin
```

**Check actual names in:**
```
cPanel → MySQL Databases
Look under "Current Databases" and "Current Users"
```

---

### ✅ Namecheap Deployment Checklist

```
✅ Files uploaded to /public_html/
✅ Database created (note the prefix!)
✅ Database user created with ALL PRIVILEGES
✅ database.sql imported
✅ database_optimizations.sql imported
✅ .env.production edited with correct DB credentials
✅ .env.production copied to .env
✅ Permissions set (755 for dirs, 644 for files)
✅ vendor/ folder uploaded or composer install run
✅ SSL enabled (Let's Encrypt)
✅ PHP 8.1+ selected with required extensions
✅ Test: https://edatacolls.co.tz loads correctly
```

---

### 🆘 Namecheap Support

**24/7 Live Chat:**
https://www.namecheap.com/support/live-chat/

**Knowledge Base:**
https://www.namecheap.com/support/knowledgebase/

**Submit Ticket:**
Dashboard → Support → Submit Ticket → Shared Hosting

---

## 📚 Full Documentation

For detailed information:
- 📖 Full Guide: [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) **(See Namecheap Section)**
- 🛠️ Technical Docs: [FIXES_AND_IMPROVEMENTS_DOCUMENTATION.md](FIXES_AND_IMPROVEMENTS_DOCUMENTATION.md)
- 📋 Navigation: [README_V2.md](README_V2.md)

---

**Need Help?**
- Namecheap: Check cPanel → File Manager → logs/error.log
- VPS/Dedicated: `tail -50 logs/error.log`

**Last Updated:** 2025-10-12
