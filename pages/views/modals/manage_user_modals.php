<!-- Add User Modal -->
<div id="addUserModal" class="modal">
  <div class="modal-content">
    <span class="close" data-modal="addUserModal">&times;</span>
    <h2>Add User</h2>
    <form id="addUserForm">
      <label>First Name</label><input type="text" name="fname" required>
      <label>Last Name</label><input type="text" name="lname" required>
      <label>Email</label><input type="email" name="email" required>
      <label>Password</label><input type="password" name="password" required>
      <button type="submit" class="btn btn-primary">Add User</button>
    </form>
  </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal">
  <div class="modal-content">
    <span class="close" data-modal="editUserModal">&times;</span>
    <h2>Edit User</h2>
    <form id="editUserForm">
      <input type="hidden" name="user_id" id="editUserId">
      <label>First Name</label><input type="text" name="fname" id="editFname" required>
      <label>Last Name</label><input type="text" name="lname" id="editLname" required>
      <label>Email</label><input type="email" name="email" id="editEmail" required>
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
  </div>
</div>

<!-- Assign Role Modal -->
<div id="assignRoleModal" class="modal">
  <div class="modal-content">
    <span class="close" data-modal="assignRoleModal">&times;</span>
    <h2>Assign Role</h2>
    <form id="assignRoleForm">
      <input type="hidden" name="user_id" id="roleUserId">
      <label for="roleSelect">Select Role</label>
      <select id="roleSelect" name="role_id" required>
        <option value="">--Select Role--</option>
        <?php foreach ($roles as $role): ?>
          <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['role_name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary">Assign Role</button>
    </form>
  </div>
</div>
