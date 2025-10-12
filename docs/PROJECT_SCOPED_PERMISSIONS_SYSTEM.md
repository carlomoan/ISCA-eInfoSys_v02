# Project-Scoped Permission System

## Overview

The ISCA e-InfoSys v02 system now includes a comprehensive, modern permission management system with **project-level granularity**. This allows administrators to assign permissions to users and roles for specific projects or globally across all projects.

## Key Features

### 1. **Project-Scoped Permissions**
- Users can have different permissions for different projects/surveys
- Global permissions (project_id = 0) apply to all projects
- Project-specific permissions (project_id > 0) apply only to that project
- Permissions are inherited through roles and can be augmented with direct user permissions

### 2. **Dual Permission Assignment**
- **Role-Based**: Assign permissions to roles, users inherit from their role
- **User-Direct**: Grant specific permissions directly to users, bypassing roles

### 3. **Modern UI**
- Beautiful, responsive interface with modern design patterns
- Project selector dropdown for filtering permissions by project
- Real-time search and filtering
- Tab-based navigation for easy management

### 4. **Audit Trail**
- All permission changes logged to `permission_audit_log` table
- Tracks who granted/revoked permissions and when
- Includes IP address and notes for compliance

### 5. **Superadmin-Only Access**
- Only users with role "Superuser" can access the full permission management system
- Regular admins can still manage basic role permissions
- Prevents unauthorized permission escalation

## Database Schema

### New/Modified Tables

#### 1. **role_permissions**
```sql
- role_id (INT)
- permission_id (INT)
- project_id (INT) NOT NULL DEFAULT 0  -- NEW: 0 = global, >0 = project-specific
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
- PRIMARY KEY (role_id, permission_id, project_id)
```

#### 2. **user_permissions** (NEW)
```sql
- id (INT) AUTO_INCREMENT PRIMARY KEY
- user_id (INT) NOT NULL
- permission_id (INT) NOT NULL
- project_id (INT) NOT NULL DEFAULT 0
- granted_by (INT) NULL
- granted_at (TIMESTAMP)
- expires_at (TIMESTAMP) NULL  -- Optional expiration
- is_active (BOOLEAN)
- notes (TEXT)
- UNIQUE (user_id, permission_id, project_id)
```

#### 3. **permissions**
```sql
-- Existing columns:
- id (INT)
- name (VARCHAR 100)
- description (VARCHAR 255)
- category (VARCHAR 50)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

-- NEW columns:
- is_project_specific (BOOLEAN) DEFAULT FALSE
- applies_to ENUM('global', 'project', 'both') DEFAULT 'global'
```

#### 4. **permission_audit_log** (NEW)
```sql
- id (INT) AUTO_INCREMENT PRIMARY KEY
- action_type ENUM('grant', 'revoke', 'update', 'sync')
- target_type ENUM('user', 'role')
- target_id (INT)
- permission_id (INT)
- project_id (INT) NULL
- performed_by (INT)
- performed_at (TIMESTAMP)
- old_value (TEXT)
- new_value (TEXT)
- notes (TEXT)
- ip_address (VARCHAR 45)
```

## Files Created/Modified

### Backend (PHP)

1. **classes/Permission.php** (Enhanced)
   - Added project-scoped methods:
     - `syncRolePermissionsWithProject()`
     - `getRolePermissionsByProject()`
     - `grantUserPermission()`
     - `revokeUserPermission()`
     - `getUserEffectivePermissions()`
     - `getUserDirectPermissions()`
     - `userHasPermissionForProject()`
     - `getAllProjects()`
     - `getUsersList()`

2. **api/permissions/manage_role_permissions.php** (Updated)
   - Enhanced to support project filtering
   - Added `get_projects` endpoint
   - Modified `save_role_permissions` to accept project_id

3. **api/permissions/manage_user_permissions.php** (NEW)
   - `get_users` - List all verified users
   - `get_user_permissions` - Get effective and direct permissions for a user
   - `grant_permission` - Grant permission to user
   - `revoke_permission` - Revoke permission from user
   - `sync_user_permissions` - Bulk update user permissions
   - `get_projects` - List all projects
   - `get_all_permissions` - List all available permissions
   - `get_audit_log` - View permission change history

4. **pages/views/manage_permissions.php** (NEW)
   - Comprehensive permission management interface
   - Two tabs: Role Permissions & User Permissions
   - Project filtering for both roles and users
   - Modern, responsive design
   - Superadmin-only access

### Frontend (CSS/JS)

5. **assets/css/manage_permissions.css** (NEW)
   - Modern, responsive design
   - CSS variables for theming
   - Smooth animations and transitions
   - Mobile-optimized layout

6. **assets/js/manage_permissions.js** (NEW)
   - Tab switching logic
   - Project filtering
   - AJAX API calls
   - Real-time search
   - Toast notifications
   - Permission grant/revoke functionality

### Database

7. **database/migrations/create_project_scoped_permissions.sql** (NEW)
   - Complete migration script
   - Adds project_id to role_permissions
   - Creates user_permissions table
   - Creates permission_audit_log table
   - Updates permissions table structure

### Documentation

8. **docs/PROJECT_SCOPED_PERMISSIONS_SYSTEM.md** (This file)

## Usage Guide

### Accessing the System

1. Login as a Superuser
2. Navigate to the sidebar → **"Manage Permissions"** (only visible to Superuser)

### Managing Role Permissions

1. **Select Project Scope**
   - Choose "Global Permissions (All Projects)" to assign permissions across all projects
   - OR select a specific project to assign permissions only for that project

2. **Select Role**
   - Click on any role tab to view/edit its permissions

3. **Assign Permissions**
   - Check/uncheck permissions as needed
   - Use "Select All" / "Deselect All" for bulk operations
   - Use search box to filter permissions

4. **Save Changes**
   - Click "Save Permissions" to apply changes
   - Changes are logged to audit trail

### Managing User Permissions

1. **Select User**
   - Choose a user from the dropdown

2. **Choose Project Scope**
   - Select "Global (All Projects)" or a specific project

3. **View Permissions**
   - **Effective Permissions**: Shows all permissions (from roles + direct grants)
   - **Direct Permissions**: Shows only permissions granted directly to the user

4. **Grant New Permission**
   - Go to "Grant New Permission" tab
   - Select permission from dropdown
   - Optionally set expiration date
   - Add notes explaining why permission is granted
   - Click "Grant Permission"

5. **Revoke Permission**
   - Go to "Direct Permissions" tab
   - Click "Revoke" on any permission
   - Confirm the action

## Permission Checking in Code

### For Page Access (Already Implemented)

The system automatically checks `view_[page_name]` permissions for all pages:

```php
// In index.php
$pagePermission = "view_" . $page;
if (!checkPermission($pagePermission)) {
    $page = 'access_denied';
}
```

### For Project-Specific Operations (New Pattern)

When checking permissions for project-specific actions:

```php
require_once ROOT_PATH . 'classes/Permission.php';

$permission = new Permission($pdo);
$userId = $_SESSION['user_id'];
$projectId = $_GET['project_id'] ?? 0;

// Check if user has permission for this specific project
if (!$permission->userHasPermissionForProject($userId, 'add_field_data', $projectId)) {
    http_response_code(403);
    echo json_encode(['error' => 'You do not have permission for this project']);
    exit;
}

// Proceed with action...
```

### For Admin-Only Features

```php
$isSuperAdmin = ($_SESSION['is_admin'] ?? false) &&
                ($_SESSION['role_name'] ?? '') === 'Superuser';

if (!$isSuperAdmin) {
    // Deny access
    http_response_code(403);
    exit('Access denied');
}
```

## Project-Specific Permissions

The following permissions are marked as project-specific:

- `add_field_data`
- `edit_field_data`
- `delete_field_data`
- `view_field_data`
- `add_lab_data`
- `edit_lab_data`
- `delete_lab_data`
- `view_lab_data`
- `view_field_collection`
- `view_data_collection`
- `view_lab_sorting`
- `view_data_entry`
- `view_report`

## Security Considerations

### 1. **Authorization Checks**
- All API endpoints verify user session
- Superadmin role check before allowing permission modifications
- Activity logging for all permission changes

### 2. **SQL Injection Prevention**
- All database queries use PDO prepared statements
- Input sanitization on all user inputs

### 3. **Audit Trail**
- Every permission change is logged with:
  - Who performed the action
  - When it was performed
  - What permission was changed
  - IP address of the user
  - Optional notes

### 4. **Permission Expiration**
- Direct user permissions can have expiration dates
- System automatically ignores expired permissions
- Useful for temporary access grants

## Best Practices

### 1. **Use Roles for Common Permissions**
- Assign permissions to roles for standard access patterns
- E.g., "Field Worker" role gets field_collection permissions

### 2. **Use Direct Permissions for Exceptions**
- Grant direct permissions for one-off cases
- E.g., Temporary project access for a consultant

### 3. **Project Scoping**
- Use global permissions (project_id = 0) for admin operations
- Use project-specific permissions for data operations

### 4. **Regular Audits**
- Review the audit log periodically
- Check for unusual permission grants
- Remove expired or unnecessary permissions

## Troubleshooting

### Issue: User can't see a page they should have access to

**Solution**:
1. Check if permission exists: `php scripts/sync_permissions.php`
2. Verify user's role has the permission (or grant directly)
3. Check project_id matches (0 for global, or specific project ID)
4. Ask user to logout and login again to refresh session cache

### Issue: Permission changes not taking effect

**Solution**:
- User needs to logout and login for permission cache to refresh
- Cache duration is 10 minutes (`CACHE_DURATION` in Permission.php)
- Alternatively, modify `helpers/permission_helper.php` to reduce cache time

### Issue: Can't access Manage Permissions page

**Solution**:
- Only Superuser role can access this page
- Verify user has role "Superuser" (not just is_admin = 1)
- Check `role_name` in session: `print_r($_SESSION);`

## Testing the System

### 1. **Test Role Permissions**
```bash
# Login as Superuser
# Go to: http://localhost:8000/index.php?page=manage_permissions

# Steps:
1. Select "Global Permissions"
2. Select a role (e.g., "Field Worker")
3. Assign some permissions
4. Click "Save Permissions"
5. Login as a user with that role
6. Verify they can access assigned pages
```

### 2. **Test Project-Specific Permissions**
```bash
# Steps:
1. Select a specific project from dropdown
2. Assign project-specific permissions (e.g., add_field_data)
3. Save
4. Login as that user
5. Try to add field data for that project → Should work
6. Try to add field data for a different project → Should fail
```

### 3. **Test Direct User Permissions**
```bash
# Steps:
1. Go to "User Permissions" tab
2. Select a user
3. Go to "Grant New Permission" tab
4. Grant a permission with expiration date
5. Add notes
6. Click "Grant Permission"
7. Verify it appears in "Direct Permissions" tab
8. Login as that user and test access
```

## Future Enhancements

### Potential Improvements:
1. **Permission Groups/Templates**
   - Create permission bundles for common roles
   - Quick-assign multiple permissions at once

2. **Permission Dependencies**
   - Auto-grant related permissions
   - E.g., "add" permission requires "view" permission

3. **Bulk Operations**
   - Assign permissions to multiple users at once
   - Copy permissions from one project to another

4. **Enhanced Audit Log Viewer**
   - Filterable, searchable audit log interface
   - Export audit logs to CSV/PDF

5. **Permission Request Workflow**
   - Users can request permissions
   - Admins approve/deny requests

6. **Time-Limited Access**
   - Auto-revoke permissions after set period
   - Notifications before expiration

## Support

For issues or questions about the permission system:
1. Check the audit log for permission changes
2. Review user's role assignments
3. Verify database migration completed successfully
4. Check browser console for JavaScript errors
5. Check PHP error logs for API errors

## Summary

The project-scoped permission system provides:
- ✅ Fine-grained access control per project
- ✅ Dual assignment (role + direct)
- ✅ Modern, user-friendly interface
- ✅ Complete audit trail
- ✅ Superadmin protection
- ✅ Temporal access (expiration)
- ✅ Scalable architecture

This system ensures that users have appropriate access to data and features based on their role and project assignments, while maintaining security and compliance through comprehensive audit logging.
