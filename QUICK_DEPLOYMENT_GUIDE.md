# Quick Deployment Guide - eDataColls v2.0

## 🚀 Quick Start (5 Minutes)

### Step 1: Backup
```bash
cd /home/andrew/PhProjects/eDataColls/ISCA-eInfoSys_v02
mysqldump -u root -p survey_amrc_db > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Step 2: Apply Database Optimizations
```bash
mysql -u root -p survey_amrc_db < database_optimizations.sql
```

### Step 3: Create Cache Directory
```bash
mkdir -p cache
chmod 755 cache
chown www-data:www-data cache  # Or your web server user
```

### Step 4: Restart Web Server
```bash
# For Apache
sudo systemctl restart apache2

# For Nginx + PHP-FPM
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx
```

### Step 5: Test
Visit: `http://localhost:8000/?page=dashboard`

---

## 📋 Verification Checklist

After deployment, verify these critical functions:

### ✅ Authentication & Session
- [ ] Login works
- [ ] User session persists
- [ ] Logout works
- [ ] Public registration page loads correctly
- [ ] Password reset accessible

### ✅ Dashboard
- [ ] Dashboard loads < 1 second
- [ ] Statistics display correctly
- [ ] Charts render properly
- [ ] No console errors

### ✅ Data Collection
- [ ] Data tab loads
- [ ] Internal Field Data tab works
- [ ] Internal Lab Data tab works
- [ ] View Merged ODK Data tab works
- [ ] Append and Finalize tab works

### ✅ Data Entry
- [ ] Upload forms display properly
- [ ] File upload works (Field Data)
- [ ] File upload works (Lab Data)
- [ ] Merge functionality works

### ✅ Reports
- [ ] Report page loads with stats
- [ ] Analytics charts display
- [ ] Data views work
- [ ] Upload tab functions

### ✅ Export Functionality
- [ ] Single record export (CSV, XLSX, PDF)
- [ ] Multiple record selection works
- [ ] Bulk export successful
- [ ] No "Access denied" errors for superusers

### ✅ Settings
- [ ] Clusters load and display
- [ ] Database export works
- [ ] Permissions management works

### ✅ User Profile
- [ ] Profile page loads with modern UI
- [ ] Avatar upload works
- [ ] Profile update successful
- [ ] Activity log displays

---

## 🔧 Troubleshooting

### Issue: Cache not working

```bash
# Check permissions
ls -la cache/

# Fix permissions
chmod 755 cache/
chown -R www-data:www-data cache/

# Test write
sudo -u www-data touch cache/test.txt
```

### Issue: "Failed to load clusters"

```bash
# Check database
mysql -u root -p survey_amrc_db -e "SELECT COUNT(*) FROM clusters;"

# Check column names
mysql -u root -p survey_amrc_db -e "DESCRIBE clusters;"
```

### Issue: Session errors

```php
// Add to config/config.php for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check session directory permissions
ls -la /tmp/ | grep sess
```

### Issue: Slow queries

```bash
# Enable slow query log
mysql -u root -p -e "SET GLOBAL slow_query_log = 1;"
mysql -u root -p -e "SET GLOBAL long_query_time = 2;"

# Check slow query log
tail -f /var/log/mysql/mysql-slow.log
```

### Issue: 500 Internal Server Error

```bash
# Check PHP error log
tail -f /var/log/php8.1-fpm.log

# Check Apache error log
tail -f /var/log/apache2/error.log

# Check application logs
tail -f logs/db_error.log
tail -f logs/php_error.log
```

---

## 🎯 Performance Validation

### Expected Performance Metrics

Run these tests after deployment:

```bash
# Test dashboard load time
curl -w "@curl-format.txt" -o /dev/null -s http://localhost:8000/?page=dashboard

# curl-format.txt content:
# time_namelookup:  %{time_namelookup}\n
# time_connect:  %{time_connect}\n
# time_starttransfer:  %{time_starttransfer}\n
# time_total:  %{time_total}\n
```

**Expected Results:**
- Dashboard: < 1 second
- API endpoints: < 200ms (with cache)
- Database queries: < 50ms (indexed tables)

### Database Performance Check

```sql
-- Check index usage
SHOW INDEX FROM field_collector;
SHOW INDEX FROM lab_sorter;
SHOW INDEX FROM users;

-- Check table sizes
SELECT
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size (MB)"
FROM information_schema.TABLES
WHERE table_schema = 'survey_amrc_db'
ORDER BY (data_length + index_length) DESC;

-- Check query execution time
EXPLAIN SELECT * FROM vw_merged_field_lab_data WHERE hhcode = '10401125';
```

---

## 📊 Monitoring

### Cache Monitoring

```bash
# Check cache size
du -sh cache/

# Count cache files
ls -1 cache/*.cache | wc -l

# Clear cache if needed
rm -f cache/*.cache
```

### Database Monitoring

```sql
-- Active connections
SHOW PROCESSLIST;

-- Table locks
SHOW OPEN TABLES WHERE In_use > 0;

-- Query cache statistics (MySQL 5.7)
SHOW STATUS LIKE 'Qcache%';
```

### Application Health

```bash
# Check if web server is running
systemctl status apache2  # or nginx

# Check if PHP-FPM is running
systemctl status php8.1-fpm

# Check if MySQL is running
systemctl status mysql

# Monitor resource usage
htop
```

---

## 🔄 Rollback Plan

If something goes wrong:

### Quick Rollback

```bash
# 1. Restore database backup
mysql -u root -p survey_amrc_db < backup_YYYYMMDD_HHMMSS.sql

# 2. Revert code changes (if using git)
git reset --hard HEAD~1

# 3. Clear cache
rm -rf cache/*.cache

# 4. Restart services
sudo systemctl restart apache2
sudo systemctl restart php8.1-fpm
```

### Partial Rollback (Database Only)

```bash
# Drop new indexes if causing issues
mysql -u root -p survey_amrc_db << EOF
ALTER TABLE field_collector DROP INDEX idx_hhcode;
ALTER TABLE field_collector DROP INDEX idx_round;
-- Repeat for other indexes
EOF
```

---

## 📝 Post-Deployment Tasks

### Immediate (Day 1)

- [ ] Monitor error logs continuously
- [ ] Check cache hit rates
- [ ] Verify all user roles can access their features
- [ ] Test export functionality thoroughly
- [ ] Check mobile responsiveness

### Short Term (Week 1)

- [ ] Gather user feedback
- [ ] Monitor database performance
- [ ] Review slow query log
- [ ] Check cache storage growth
- [ ] Optimize remaining SELECT * queries

### Long Term (Month 1)

- [ ] Implement Redis cache (if needed)
- [ ] Add API rate limiting
- [ ] Enable gzip compression
- [ ] Implement service workers
- [ ] Consider database partitioning

---

## 🆘 Emergency Contacts

**Database Issues:**
- Check: `logs/db_error.log`
- MySQL docs: https://dev.mysql.com/doc/

**PHP Issues:**
- Check: `/var/log/php8.1-fpm.log`
- PHP docs: https://www.php.net/manual/

**Cache Issues:**
- Clear cache: `rm -rf cache/*.cache`
- Check permissions: `ls -la cache/`

---

## 📚 Additional Resources

- Full Documentation: `FIXES_AND_IMPROVEMENTS_DOCUMENTATION.md`
- Database Optimizations: `database_optimizations.sql`
- Cache Implementation: `config/cache.php`

---

**Deployment Date:** October 2025
**System:** eDataColls v2.0
**Status:** Production Ready ✅
