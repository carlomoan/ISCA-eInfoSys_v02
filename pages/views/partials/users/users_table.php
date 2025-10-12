<div id="views-users-container">
  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Status</td>
        <th>Position</th>
        <th>Laboratory Duty</th>
        <th>User Access</th>
        <th>User Mapping</th>
      </tr>
    </thead>
    <tbody id="usersBody">
      <tr><td colspan="8">Loading users...</td></tr>
    </tbody>
  </table>


<!-- Manage User Modal -->
<div id="manageUserModal" class="modal hidden">
  <div class="modal-content">
    <span class="close-btn" onclick="closeManageUserModal()">&times;</span>
    <h4 style="margin-bottom:2px;">Staff name: <span id="modalUserName"></span></h4>

    <!-- Basic info -->
    <div class="user-info">
    <p><b>Staff Numer:</b> <span id="modalUserId"></span> | <b>Email:</b> <span id="modalUserEmail"></span> | <b>Phone:</b> <span id="modalUserPhone"></span></p>
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
  <h5>Assign Role</h5>
  <p class="current-status">Current: <span id="currentRolesText"></span></p>
  <select id="assignRolesSelect">
    <option value="">-- Select Role --</option>
  </select>
  <select id="currentRolesList" multiple></select>
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
<div class="form-footer">
  <button id="saveUserChanges">Save Changes</button>
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
