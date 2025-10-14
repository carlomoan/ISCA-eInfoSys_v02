<div id="views-users-container">
  <div style="margin-bottom: 10px; padding: 8px; background: #e7f3ff; border-left: 4px solid #0066cc; border-radius: 4px;">
    <i class="fas fa-info-circle" style="color: #0066cc;"></i>
    <strong>User Management:</strong> Manage user accounts, assign roles, projects, and permissions. Use "No Role" for users without specific permissions.
  </div>
  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Role</th>
        <th>Projects</th>
        <th>Status</th>
        <th>Lab Duty</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody id="usersBody">
      <tr><td colspan="9">Loading users...</td></tr>
    </tbody>
  </table>


<!-- Manage User Modal -->
<div id="manageUserModal" class="modal hidden">
  <div class="modal-content">
    <span class="close-btn" onclick="closeManageUserModal()">&times;</span>
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; margin: -15px -15px 15px -15px; border-radius: 8px 8px 0 0;">
      <h4 style="margin: 0; font-size: 18px;">
        <i class="fas fa-user-edit"></i> Manage User: <span id="modalUserName" style="font-weight: 600;"></span>
      </h4>
      <div style="margin-top: 8px; opacity: 0.95; font-size: 13px;">
        <span style="margin-right: 15px;"><i class="fas fa-id-badge"></i> ID: <span id="modalUserId"></span></span>
        <span style="margin-right: 15px;"><i class="fas fa-envelope"></i> <span id="modalUserEmail"></span></span>
        <span><i class="fas fa-phone"></i> <span id="modalUserPhone"></span></span>
      </div>
    </div>

    <!-- GRID LAYOUT -->
    <div class="grid-3">

      <!-- Reset Password -->
      <div class="form-block">
        <h5>Reset Password</h5>
        <p class="current-status">Current: <span id="">*********</span></p>
        <input type="password" id="resetPassword" placeholder="Enter new password" />
      </div>

      <!-- Verification -->
      <div class="form-block">
        <h5>Verification Status</h5>
        <p class="current-status">Current: <span id="currentVerify"></span></p>
        <select id="toggleVerify">
          <option value="1">Verified</option>
          <option value="0">Unverified</option>
        </select>
      </div>

      <!-- Admin -->
      <div class="form-block">
        <h5>Admin Status</h5>
        <p class="current-status">Current: <span id="currentAdmin"></span></p>
        <select id="toggleAdmin">
          <option value="1">Admin</option>
          <option value="0">Not Admin</option>
        </select>
      </div>


<!-- Projects -->
<div class="form-block">
  <h5>Assign Project</h5>
  <p class="current-status">Current: <span id="currentProjectsText"></span></p>
  <select id="assignProjectsSelect">
    <option value="">-- Select Project --</option>
  </select>
  <select id="currentProjectsList" multiple></select>
</div>

<!-- Roles -->
<div class="form-block">
  <h5><i class="fas fa-user-tag"></i> Assign Role</h5>
  <p class="current-status">Current: <span id="currentRolesText" style="font-weight: 600; color: #007bff;"></span></p>
  <select id="assignRolesSelect" style="padding: 10px; border: 2px solid #ddd; border-radius: 4px; width: 100%; font-size: 14px; background: white; cursor: pointer;">
    <option value="">-- Select Role --</option>
  </select>
  <small style="color: #666; margin-top: 4px; display: block;">
    <i class="fas fa-info-circle"></i> Select "No Role" to remove all permissions
  </small>
  <select id="currentRolesList" multiple style="display: none;"></select>
</div>


<!-- Clusters -->
<div class="form-block">
  <h5>Assign Cluster</h5>
  <p class="current-status">Current: <span id="currentClustersText"></span></p>
  <select id="assignClustersSelect">
    <option value="">-- Select Cluster --</option>
  </select>
  <select id="currentClustersList" multiple></select>
</div>

<!-- Lab Technician -->
<div class="form-block">
  <h5>Assign Lab Technician</h5>
  <p class="current-status">Current: <span id="currentLabTechsText"></span></p>
  <select id="assignLabTechsSelect">
    <option value="">-- Select Action --</option>
    <option value="assign">Assign Lab Duty</option>
    <option value="revoke">Revoke Lab Duty</option>
  </select>
  <select id="currentLabTechsList" multiple></select>
</div>
</div>


<!-- Save -->
<div class="form-footer" style="margin-top: 20px; padding-top: 15px; border-top: 2px solid #eee; display: flex; gap: 10px; justify-content: flex-end;">
  <button onclick="closeManageUserModal()" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 14px;">
    <i class="fas fa-times"></i> Cancel
  </button>
  <button id="saveUserChanges" style="background: #28a745; color: white; border: none; padding: 10px 30px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);">
    <i class="fas fa-save"></i> Save Changes
  </button>
</div>
</div>
</div>




  <!-- Live Activity Bell -->
  <div id="liveActivityBell" style="position:fixed;bottom:20px;right:20px;cursor:pointer;z-index:1000;">
    🔔 <span id="liveActivityCount" style="background:red;color:white;border-radius:50%;padding:2px 6px;font-size:10px;">0</span>
  </div>
  <div id="liveActivityModal" class="modal hidden">
    <div class="modal-content">
      <span class="close-btn" onclick="closeLiveActivityModal()">&times;</span>
      <h4>New Activities</h4>
      <ul id="liveActivityList"></ul>
    </div>
  </div>
</div>
