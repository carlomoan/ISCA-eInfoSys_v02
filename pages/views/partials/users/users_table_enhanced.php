<div id="views-users-container">
  <div class="table-info-banner">
    <i class="fas fa-info-circle"></i>
    <div>
      <strong>User Management</strong>
      <span>Manage user accounts, assign roles, projects, and permissions. Use "No Role" for users without specific permissions.</span>
    </div>
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
      <tr class="loading-row"><td colspan="9">Loading users...</td></tr>
    </tbody>
  </table>


<!-- Enhanced Manage User Modal -->
<div id="manageUserModal" class="modal hidden">
  <div class="modal-content">
    <span class="close-btn" onclick="closeManageUserModal()">&times;</span>
    <div class="modal-header-gradient">
      <h4>
        <i class="fas fa-user-edit"></i> Manage User: <span id="modalUserName"></span>
      </h4>
      <div class="modal-user-info">
        <span><i class="fas fa-id-badge"></i> ID: <span id="modalUserId"></span></span>
        <span><i class="fas fa-envelope"></i> <span id="modalUserEmail"></span></span>
        <span><i class="fas fa-phone"></i> <span id="modalUserPhone"></span></span>
      </div>
    </div>

    <!-- Enhanced Grid Layout -->
    <div class="grid-3">

      <!-- Reset Password -->
      <div class="form-block">
        <h5><i class="fas fa-key"></i> Reset Password</h5>
        <p class="current-status">Current: <span>*********</span></p>
        <input type="password" id="resetPassword" placeholder="Enter new password" />
      </div>

      <!-- Verification -->
      <div class="form-block">
        <h5><i class="fas fa-check-circle"></i> Verification Status</h5>
        <p class="current-status">Current: <span id="currentVerify"></span></p>
        <select id="toggleVerify">
          <option value="1">Verified</option>
          <option value="0">Unverified</option>
        </select>
      </div>

      <!-- Admin -->
      <div class="form-block">
        <h5><i class="fas fa-user-shield"></i> Admin Status</h5>
        <p class="current-status">Current: <span id="currentAdmin"></span></p>
        <select id="toggleAdmin">
          <option value="1">Admin</option>
          <option value="0">Not Admin</option>
        </select>
      </div>

      <!-- Projects -->
      <div class="form-block">
        <h5><i class="fas fa-project-diagram"></i> Assign Project</h5>
        <p class="current-status">Current: <span id="currentProjectsText"></span></p>
        <select id="assignProjectsSelect">
          <option value="">-- Select Project --</option>
        </select>
        <select id="currentProjectsList" multiple style="display: none;"></select>
      </div>

      <!-- Roles -->
      <div class="form-block">
        <h5><i class="fas fa-user-tag"></i> Assign Role</h5>
        <p class="current-status">Current: <span id="currentRolesText"></span></p>
        <select id="assignRolesSelect">
          <option value="">-- Select Role --</option>
        </select>
        <small>
          <i class="fas fa-info-circle"></i> Select "No Role" to remove all permissions
        </small>
        <select id="currentRolesList" multiple style="display: none;"></select>
      </div>

      <!-- Clusters -->
      <div class="form-block">
        <h5><i class="fas fa-map-marked-alt"></i> Assign Cluster</h5>
        <p class="current-status">Current: <span id="currentClustersText"></span></p>
        <select id="assignClustersSelect">
          <option value="">-- Select Cluster --</option>
        </select>
        <select id="currentClustersList" multiple style="display: none;"></select>
      </div>

      <!-- Lab Technician -->
      <div class="form-block">
        <h5><i class="fas fa-flask"></i> Lab Technician</h5>
        <p class="current-status">Current: <span id="currentLabTechsText"></span></p>
        <select id="assignLabTechsSelect">
          <option value="">-- Select Action --</option>
          <option value="assign">Assign Lab Duty</option>
          <option value="revoke">Revoke Lab Duty</option>
        </select>
        <select id="currentLabTechsList" multiple style="display: none;"></select>
      </div>
    </div>

    <!-- Enhanced Footer -->
    <div class="form-footer">
      <button onclick="closeManageUserModal()">
        <i class="fas fa-times"></i> Cancel
      </button>
      <button id="saveUserChanges">
        <i class="fas fa-save"></i> Save Changes
      </button>
    </div>
  </div>
</div>

  <!-- Enhanced Live Activity Bell -->
  <div id="liveActivityBell" onclick="document.getElementById('liveActivityModal').classList.remove('hidden')">
    🔔 <span id="liveActivityCount">0</span>
  </div>

  <div id="liveActivityModal" class="modal hidden">
    <div class="modal-content">
      <span class="close-btn" onclick="closeLiveActivityModal()">&times;</span>
      <div class="modal-header-gradient">
        <h4><i class="fas fa-bell"></i> New Activities</h4>
      </div>
      <ul id="liveActivityList" style="padding: 20px; margin: 0; list-style: none;"></ul>
    </div>
  </div>
</div>
