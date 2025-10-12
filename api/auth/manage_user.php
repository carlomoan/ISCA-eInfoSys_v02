<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db_connect.php';

// Permission check (assuming checkPermission helper exists)
if (!function_exists('checkPermission') || !checkPermission('manage_users')) {
    echo "<p class='text-danger'>You do not have permission to view this page.</p>";
    exit;
}
?>

<div class="page-user-management" style="padding:2rem;">
    <h2>Manage Users</h2>
    <div class="table-responsive">
        <table id="usersTable" class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Role</th>
                    <th>Project</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width:500px;margin:auto;padding:1rem;">
            <h4>Edit User</h4>
            <form id="editUserForm">
                <input type="hidden" name="user_id">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="fname" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="lname" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role_id" class="form-control"></select>
                </div>
                <div class="form-group">
                    <label>Project</label>
                    <select name="project_id" class="form-control"></select>
                </div>
                <div class="text-end">
                    <button type="button" id="cancelEdit" class="btn btn-light">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/manage_user.js"></script>
