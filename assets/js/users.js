function initUsersPage() {
  renderUserTable();
  document.getElementById('userForm').addEventListener('submit', saveUser);
  document.getElementById('cancelUserEdit').addEventListener('click', resetUserForm);
}

function renderUserTable() {
  const tableBody = document.getElementById('userTableBody');
  if (!tableBody) return;

  const state = getState();
  tableBody.innerHTML = state.users.map((user) => `
    <tr>
      <td>${escapeHtml(user.id)}</td>
      <td>${escapeHtml(user.name)}</td>
      <td>${escapeHtml(user.username)}</td>
      <td>${escapeHtml(user.role)}</td>
      <td><span class="badge-status ${user.status === 'active' ? 'safe' : 'warning'}">${user.status}</span></td>
      <td>
        <div class="d-flex gap-2">
          <button class="btn btn-sm btn-outline-primary" data-edit-user="${user.id}">Edit</button>
          <button class="btn btn-sm btn-outline-danger" data-delete-user="${user.id}">Delete</button>
        </div>
      </td>
    </tr>
  `).join('');

  document.querySelectorAll('[data-edit-user]').forEach((button) => {
    button.addEventListener('click', () => populateUserForm(button.dataset.editUser));
  });

  document.querySelectorAll('[data-delete-user]').forEach((button) => {
    button.addEventListener('click', () => deleteUser(button.dataset.deleteUser));
  });
}

function saveUser(event) {
  event.preventDefault();
  const state = getState();
  const userName = document.getElementById('userNameInput').value.trim();
  const username = document.getElementById('userUsernameInput').value.trim();
  const password = document.getElementById('userPasswordInput').value.trim();
  const role = document.getElementById('userRoleInput').value;
  const status = document.getElementById('userStatusInput').value;
  const recordId = document.getElementById('userRecordId').value;

  if (!userName || !username || !password || !role || !status) {
    showToast('Please complete all user fields.', 'danger');
    return;
  }

  if (recordId) {
    const existing = state.users.find((item) => item.id === recordId);
    if (!existing) {
      showToast('User not found.', 'danger');
      return;
    }
    const duplicate = state.users.some((item) => item.username === username && item.id !== recordId);
    if (duplicate) {
      showToast('Username already exists.', 'danger');
      return;
    }
    existing.name = userName;
    existing.username = username;
    existing.password = password;
    existing.role = role;
    existing.status = status;
    addAuditLog('User edited', getCurrentUser()?.username || 'system');
    showToast('User updated successfully.', 'success');
  } else {
    const duplicate = state.users.some((item) => item.username === username);
    if (duplicate) {
      showToast('Username already exists.', 'danger');
      return;
    }
    state.users.push({
      id: generateId('USR'),
      name: userName,
      username,
      password,
      role,
      status
    });
    addAuditLog('User added', getCurrentUser()?.username || 'system');
    showToast('User added successfully.', 'success');
  }

  saveState(state);
  renderUserTable();
  resetUserForm();
}

function populateUserForm(id) {
  const user = getState().users.find((item) => item.id === id);
  if (!user) return;

  document.getElementById('userRecordId').value = user.id;
  document.getElementById('userNameInput').value = user.name;
  document.getElementById('userUsernameInput').value = user.username;
  document.getElementById('userPasswordInput').value = user.password;
  document.getElementById('userRoleInput').value = user.role;
  document.getElementById('userStatusInput').value = user.status;
  document.getElementById('userFormTitle').textContent = 'Edit User';
}

function resetUserForm() {
  document.getElementById('userForm').reset();
  document.getElementById('userRecordId').value = '';
  document.getElementById('userFormTitle').textContent = 'Add User';
}

function deleteUser(id) {
  const state = getState();
  const user = state.users.find((item) => item.id === id);
  if (!user) return;
  if (user.username === getCurrentUser()?.username) {
    showToast('You cannot delete your own account.', 'danger');
    return;
  }
  const confirmed = window.confirm(`Delete user ${user.name}?`);
  if (!confirmed) return;
  state.users = state.users.filter((item) => item.id !== id);
  saveState(state);
  addAuditLog('User deleted', getCurrentUser()?.username || 'system');
  showToast('User deleted successfully.', 'success');
  renderUserTable();
}

function initProfilePage() {
  const currentUser = getCurrentUser();
  if (!currentUser) return;
  const state = getState();
  const user = state.users.find((item) => item.username === currentUser.username);
  if (!user) return;

  document.getElementById('profileName').value = user.name;
  document.getElementById('profileUsername').value = user.username;
  document.getElementById('profileRole').value = user.role;

  document.getElementById('profileForm').addEventListener('submit', (event) => {
    event.preventDefault();
    const name = document.getElementById('profileName').value.trim();
    const username = document.getElementById('profileUsername').value.trim();

    if (!name || !username) {
      showToast('Please enter valid profile data.', 'danger');
      return;
    }

    const duplicate = state.users.some((item) => item.username === username && item.id !== user.id);
    if (duplicate) {
      showToast('Username already exists.', 'danger');
      return;
    }

    user.name = name;
    user.username = username;
    saveState(state);
    setCurrentUser({ id: user.id, name: user.name, username: user.username, role: user.role });
    addAuditLog('Profile updated', getCurrentUser()?.username || 'system');
    showToast('Profile updated successfully.', 'success');
  });
}

function renderAuditLogTable() {
  const tableBody = document.getElementById('auditLogTable');
  if (!tableBody) return;
  const state = getState();
  const logs = state.auditLog.slice(0, 10);

  if (!logs.length) {
    tableBody.innerHTML = '<tr><td colspan="3" class="empty-state">No audit history available.</td></tr>';
    return;
  }

  tableBody.innerHTML = logs.map((entry) => `
    <tr>
      <td>${escapeHtml(entry.user || 'system')}</td>
      <td>${escapeHtml(entry.action)}</td>
      <td>${formatDateTime(entry.timestamp)}</td>
    </tr>
  `).join('');
}

function initSettingsPage() {
  const state = getState();
  const settings = state.settings;
  document.getElementById('expiryWarningDays').value = settings.expiryWarningDays || 90;
  document.getElementById('themePreference').value = settings.theme || 'light';
  document.getElementById('lowStockPreference').value = settings.lowStockAlert || 'enabled';
  renderAuditLogTable();

  document.getElementById('settingsForm').addEventListener('submit', (event) => {
    event.preventDefault();
    state.settings.expiryWarningDays = Number(document.getElementById('expiryWarningDays').value);
    state.settings.theme = document.getElementById('themePreference').value;
    state.settings.lowStockAlert = document.getElementById('lowStockPreference').value;
    saveState(state);
    updateGlobalTheme();
    showToast('Settings saved successfully.', 'success');
  });

  document.getElementById('resetDemoDataButton').addEventListener('click', () => {
    const confirmed = window.confirm('Reset all demo data?');
    if (!confirmed) return;
    const fresh = resetDemoData();
    saveState(fresh);
    window.location.reload();
  });

  document.getElementById('clearDataButton').addEventListener('click', () => {
    const confirmed = window.confirm('Clear all application data?');
    if (!confirmed) return;
    clearAllData();
    window.location.href = 'login.html';
  });
}

