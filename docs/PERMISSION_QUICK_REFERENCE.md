# 🔐 Permission System - Quick Reference Card

## 🚀 Common Commands

```bash
# Sync page permissions
php scripts/sync_permissions.php

# Run database migration
php scripts/run_permission_migration.php
```

---

## 📝 Check Permission in Code

### PHP
```php
// Check if user has permission
if (checkPermission('view_field_collection')) {
    // Allowed
}

// Check with admin bypass
if (checkPermission('add_data') || ($_SESSION['is_admin'] ?? false)) {
    // Allowed
}
```

### JavaScript
```javascript
// In role_permissions.js
if (currentPermissions.includes('export_data')) {
    showFeature();
}
```

---

## 🎯 Permission Naming

| Type | Format | Example |
|------|--------|---------|
| Page | `view_[page]` | `view_field_collection` |
| Action | `[action]_[resource]` | `add_field_data` |

---

## 📁 Key Files

| File | Purpose |
|------|---------|
| `classes/Permission.php` | Permission class with all methods |
| `api/permissions/manage_role_permissions.php` | API endpoints |
| `assets/js/role_permissions.js` | UI JavaScript |
| `assets/css/role_permissions.css` | UI styles |
| `pages/views/role_permissions.php` | Management UI |
| `scripts/sync_permissions.php` | Sync script |

---

## 🔄 Common Workflows

### Add New Page
1. Create `pages/views/new_page.php`
2. Run `php scripts/sync_permissions.php`
3. Go to Role Permissions UI
4. Assign `view_new_page` to roles
5. Test access

### Grant User Access
1. Identify user's role
2. Go to Role Permissions
3. Select role tab
4. Check required permission
5. Save

### Remove Access
1. Go to Role Permissions
2. Select role tab
3. Uncheck permission
4. Save

---

## 🗂️ Categories

- **page** - Page access permissions
- **data** - Data operations
- **admin** - Administrative tasks
- **custom** - Custom permissions

---

## 🛡️ Public Pages (No Permission Required)

- `dashboard`
- `profile`
- `change_password`
- `awaiting_approval`
- `access_denied`
- `404`
- `under_construction`

---

## 🔧 API Endpoints

| Endpoint | Method | Action |
|----------|--------|--------|
| `?action=get_role_permissions&role_id=X` | GET | Get permissions for role |
| `?action=save_role_permissions` | POST | Update role permissions |
| `?action=sync_permissions` | GET | Sync page permissions |
| `?action=get_all_permissions` | GET | Get all permissions |
| `?action=create_permission` | POST | Create new permission |
| `?action=delete_permission` | POST | Delete permission |

---

## 🐛 Quick Troubleshooting

| Problem | Solution |
|---------|----------|
| User can't access page | Check role permissions in UI |
| Permission not found | Run sync script |
| Changes not working | Clear cache (logout/login) |
| Sync fails | Check page file exists |
| API error 403 | User needs `manage_roles` permission |

---

## 📊 Permission Check Flow

```
User requests page
    ↓
Check if public page? → YES → Allow
    ↓ NO
Check if admin? → YES → Allow
    ↓ NO
Check permission → YES → Allow
    ↓ NO
Show Access Denied
```

---

## 💡 Tips

- **Admin has all permissions** - No need to assign individually
- **Cache lasts 10 minutes** - Logout/login to refresh immediately
- **Activity logged** - All permission changes are tracked
- **Bulk updates** - Use Sync Role Permissions for efficiency
- **Search works** - Use search box to find permissions quickly

---

## 🎨 UI Features

- ✅ Tab-based role selection
- ✅ Category grouping
- ✅ Real-time search
- ✅ Select All / Deselect All
- ✅ Permission counter
- ✅ One-click save
- ✅ Sync button
- ✅ Responsive design

---

## 📱 Mobile Support

All features work on mobile:
- Horizontal scrolling tabs
- Touch-friendly checkboxes
- Full-width buttons
- Optimized layouts

---

**Quick Links:**
- [Full Documentation](PERMISSION_SYSTEM_GUIDE.md)
- Role Permissions UI: `?page=role_permissions`
- Sync Script: `scripts/sync_permissions.php`

---

**Version:** 1.0.0 | **Last Updated:** 2025-01-10
