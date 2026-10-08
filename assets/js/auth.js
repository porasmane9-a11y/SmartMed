function ensureAuthenticated() {
  const user = getCurrentUser();
  if (!user) {
    window.location.href = 'login.html';
    return false;
  }
  return user;
}

function ensureRoleAccess(requiredRole) {
  const user = ensureAuthenticated();
  if (!user) return false;
  if (requiredRole && user.role !== requiredRole) {
    window.location.href = 'dashboard.html';
    return false;
  }
  return true;
}

function logoutUser() {
  const user = getCurrentUser();
  if (user) {
    addAuditLog('Logout', user.username);
  }
  clearCurrentUser();
  if (typeof fetch === 'function' && window.location.protocol.startsWith('http')) {
    fetch('logout.php').finally(() => {
      window.location.href = 'login.html';
    });
  } else {
    window.location.href = 'login.html';
  }
}

function bindLogoutButton() {
  const logoutButton = document.getElementById('logoutButton');
  if (logoutButton) {
    logoutButton.addEventListener('click', logoutUser);
  }
}

if (document.body && document.body.dataset.page) {
  const protectedPages = ['dashboard', 'medicines', 'batches', 'stock-in', 'stock-out', 'expiry-alerts', 'low-stock', 'suppliers', 'transactions', 'reports', 'users', 'profile', 'settings'];
  const page = document.body.dataset.page;
  if (protectedPages.includes(page)) {
    ensureAuthenticated();
  }
  if (page === 'users') {
    ensureRoleAccess('admin');
  }
}
