# 🔐 Modern Permission System - Complete Guide

## Overview
This system provides automatic permission generation from pages, role-based access control, and a modern management interface.

---

## ✅ What's Been Implemented

### 1. **Permission Class** (`classes/Permission.php`)
- Auto-discovers pages and creates permissions
- Full CRUD operations
- Role-permission management
- Caching for performance
- Activity logging

### 2. **Database Updates**
- Added `description` column to permissions
- Added `category` column for grouping
- Added timestamps (`created_at`, `updated_at`)
- Auto-synced 12 page permissions

### 3. **API Endpoints** (`api/permissions/manage_role_permissions.php`)
- `get_role_permissions` - Get permissions for a role
- `save_role_permissions` - Bulk update role permissions
- `sync_permissions` - Auto-discover and sync page permissions
- `get_all_permissions` - Get all permissions grouped by category
- `create_permission` - Create custom permission
- `delete_permission` - Delete permission (admin only)

**Security Features:**
- ✅ Session validation
- ✅ Permission checks (`manage_roles`)
- ✅ Activity logging
- ✅ Input validation
- ✅ Error handling

### 4. **Role-Permission Management UI** (`pages/views/role_permissions.php`)
**Features:**
- Tab-based interface (one tab per role)
- Permissions grouped by category
- Real-time search/filter
- Select All / Deselect All
- One-click save
- Sync button for auto-discovery
- Modern responsive design

### 5. **Page-Level Access Control** (`index.php`)
```php
// Auto-checks permission for each page
$pagePermission = "view_" . $page;
if (!checkPermission($pagePermission)) {
    $page = 'access_denied';
}
```

**Public Pages** (no permission required):
- dashboard
- profile
- change_password
- awaiting_approval
- access_denied
- 404
- under_construction

### 6. **Access Denied Page** (`pages/views/access_denied.php`)
- Shows required permission
- Shows user's role
- Helpful messages
- Quick navigation buttons

---

## 📋 Permission Naming Convention

### **Page Permissions**
Format: `view_[page_name]`

Examples:
- `view_field_collection`
- `view_lab_sorting`
- `view_user_permissions`
- `view_data_collection`

### **Action Permissions**
Format: `[action]_[resource]`

Examples:
- `add_field_data`
- `edit_user`
- `delete_report`
- `manage_roles`
- `export_data`

---

## 🚀 How to Use

### **1. Auto-Sync Page Permissions**

**Via Command Line:**
```bash
php scripts/sync_permissions.php
```

**Via UI:**
1. Go to Role Permissions page
2. Click "Sync Page Permissions" button
3. Confirms added/existing permissions

**When to Run:**
- After adding new pages
- After deployment
- When permissions seem out of sync

### **2. Assign Permissions to Role**

1. Navigate to **User Permissions → Role Permissions**
2. Click on a role tab (e.g., "Field Worker", "Lab Technician")
3. Check/uncheck permissions you want to assign
4. Click "Save Permissions"

### **3. Create Custom Permission**

**Via API:**
```javascript
fetch('/api/permissions/manage_role_permissions.php', {
    method: 'POST',
    body: new URLSearchParams({
        action: 'create_permission',
        name: 'export_reports',
        description: 'Export Reports',
        category: 'data'
    })
});
```

### **4. Check Permission in Code**

**PHP:**
```php
if (checkPermission('view_field_collection')) {
    // User has permission
}

// Or with admin bypass
if (checkPermission('add_field_data') || ($_SESSION['is_admin'] ?? false)) {
    // User has permission or is admin
}
```

**JavaScript:**
```javascript
// Check if user can access a feature
if (userPermissions.includes('export_data')) {
    showExportButton();
}
```

---

## 🗂️ Permission Categories

| Category | Description | Examples |
|----------|-------------|----------|
| **page** | Page access permissions | view_dashboard, view_reports |
| **data** | Data manipulation | add_field_data, edit_survey |
| **admin** | Administrative tasks | manage_roles, manage_users |
| **custom** | Custom permissions | export_data, approve_entries |

---

## 🔄 Workflow Example

### **Scenario: Adding a New Page**

1. **Create the page file:**
   ```
   pages/views/mosquito_analysis.php
   ```

2. **Run sync script:**
   ```bash
   php scripts/sync_permissions.php
   ```

   Output:
   ```
   ✅ Added Permissions:
      ✓ view_mosquito_analysis
   ```

3. **Assign to roles:**
   - Go to Role Permissions UI
   - Select "Lab Technician" role
   - Check "view_mosquito_analysis"
   - Save

4. **Test:**
   - Login as Lab Technician
   - Access: `/?page=mosquito_analysis`
   - ✅ Page loads successfully

### **Scenario: User Can't Access a Page**

**User sees:**
```
Access Denied
You don't have permission to access this page

Required Permission: view_field_collection
Your Role: Data Entry Clerk

Contact your administrator to request access.
```

**Admin fixes:**
1. Go to Role Permissions
2. Select "Data Entry Clerk" role
3. Check "view_field_collection"
4. Save
5. User can now access the page

---

## 📊 Database Schema

### **permissions Table**
```sql
id (INT) - Primary key
name (VARCHAR) - Permission name (unique)
description (VARCHAR) - Human-readable description
category (VARCHAR) - Category (page/data/admin/custom)
created_at (TIMESTAMP) - Creation timestamp
updated_at (TIMESTAMP) - Last update timestamp
```

### **role_permissions Table**
```sql
role_id (INT) - Foreign key to roles
permission_id (INT) - Foreign key to permissions
PRIMARY KEY (role_id, permission_id)
```

### **user_roles Table**
```sql
id (INT) - Primary key
user_id (INT) - Foreign key to users
role_id (INT) - Foreign key to roles
```

---

## 🎯 Benefits

1. ✅ **Zero Manual Work** - Pages auto-create permissions
2. ✅ **Type-Safe** - Class-based with proper types
3. ✅ **Cached** - 10-minute cache for performance
4. ✅ **Organized** - Categories and descriptions
5. ✅ **Flexible** - Supports role AND user-level permissions
6. ✅ **Audit Ready** - Timestamps and activity logs
7. ✅ **Modern UI** - Beautiful interface for admins
8. ✅ **Secure** - Session management and validation
9. ✅ **Scalable** - Handles hundreds of permissions
10. ✅ **Developer Friendly** - Simple API and helpers

---

## 🔧 Maintenance Tasks

### **Weekly:**
- Review activity logs for permission changes
- Check for orphaned permissions

### **After Updates:**
- Run sync script: `php scripts/sync_permissions.php`
- Verify role permissions still correct

### **Periodically:**
- Review and update permission descriptions
- Archive unused permissions
- Audit user access levels

---

## 🐛 Troubleshooting

### **Problem: User can't access a page they should access**

**Solution:**
1. Check user's role: `SELECT role_id FROM users WHERE id = ?`
2. Check role permissions: `SELECT * FROM role_permissions WHERE role_id = ?`
3. Verify permission exists: `SELECT * FROM permissions WHERE name = 'view_[page]'`
4. Check session: `var_dump($_SESSION['permissions'])`

### **Problem: Permission not auto-created**

**Solution:**
1. Run sync manually: `php scripts/sync_permissions.php`
2. Check page file exists in `pages/views/`
3. Check file not in skip list (404, access_denied, etc.)
4. Check database connection

### **Problem: Changes not taking effect**

**Solution:**
1. Clear permission cache:
   ```php
   unset($_SESSION['permissions']);
   unset($_SESSION['permissions_last_update']);
   ```
2. Logout and login again
3. Check cache duration (10 minutes by default)

---

## 📝 Next Steps / Future Enhancements

- [ ] User-specific permissions (override role)
- [ ] Permission groups/presets
- [ ] Permission dependencies (e.g., "add" requires "view")
- [ ] Time-based permissions (temporary access)
- [ ] IP-based restrictions
- [ ] Two-factor authentication for sensitive permissions
- [ ] Permission request workflow
- [ ] Detailed audit trail with user actions

---

## 🎓 Training Users

### **For Administrators:**
1. Show Role Permissions page
2. Explain categories and naming
3. Demo assigning/revoking permissions
4. Show sync feature
5. Practice with test role

### **For Regular Users:**
1. Show what Access Denied looks like
2. Explain how to request access
3. Show profile to see their role
4. Explain permission limitations

---

## 📞 Support

For issues or questions:
1. Check this guide first
2. Review activity logs
3. Test with admin account
4. Check browser console for errors
5. Review PHP error logs

---

**Last Updated:** 2025-01-10
**Version:** 1.0.0
**Status:** Production Ready ✅
