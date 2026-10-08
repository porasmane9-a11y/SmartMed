document.addEventListener('DOMContentLoaded', function () {
  const state = getState();
  document.body.dataset.theme = state.settings.theme || 'light';

  const sidebarHost = document.getElementById('sidebar');
  if (sidebarHost) {
    renderSidebar();
  }

  const userName = document.getElementById('currentUserName');
  const userRole = document.getElementById('currentUserRole');
  const userInitials = document.getElementById('currentUserInitials');
  const currentUser = getCurrentUser();

  if (userName) userName.textContent = currentUser ? currentUser.name : 'Guest';
  if (userRole) userRole.textContent = currentUser ? currentUser.role.toUpperCase() : 'GUEST';
  if (userInitials) {
    const displayName = currentUser ? currentUser.name : 'Guest';
    userInitials.textContent = displayName.split(' ').map((part) => part[0]).slice(0, 2).join('').toUpperCase();
  }

  bindLogoutButton();
  initMobileSidebar();
  initializePage();
});

function renderSidebar() {
  const currentUser = getCurrentUser();
  const page = document.body.dataset.page;
  const sidebar = document.getElementById('sidebar');
  if (!sidebar) return;

  const isAdmin = currentUser && currentUser.role === 'admin';
  const adminLinks = isAdmin ? `
    <li class="nav-item">
      <a class="nav-link ${page === 'users' ? 'active' : ''}" href="users.html">
        <i class="bi bi-people"></i>
        <span>Users</span>
      </a>
    </li>
  ` : '';

  sidebar.innerHTML = `
    <div class="sidebar-brand">
      <div class="brand-mark"><i class="bi bi-heart-pulse"></i></div>
      <div>
        <h4>SmartMed</h4>
      </div>
    </div>

    <nav class="nav flex-column sidebar-nav">
      <a class="nav-link ${page === 'dashboard' ? 'active' : ''}" href="dashboard.html">
        <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
      </a>

      <div class="nav-section">Inventory</div>
      <a class="nav-link ${page === 'medicines' ? 'active' : ''}" href="medicines.html">
        <i class="bi bi-capsule"></i> <span>Medicines</span>
      </a>
      <a class="nav-link ${page === 'batches' ? 'active' : ''}" href="batches.html">
        <i class="bi bi-archive"></i> <span>Batches</span>
      </a>
      <a class="nav-link ${page === 'stock-in' ? 'active' : ''}" href="stock-in.html">
        <i class="bi bi-plus-circle"></i> <span>Stock In</span>
      </a>
      <a class="nav-link ${page === 'stock-out' ? 'active' : ''}" href="stock-out.html">
        <i class="bi bi-dash-circle"></i> <span>Stock Out</span>
      </a>

      <div class="nav-section">Alerts</div>
      <a class="nav-link ${page === 'expiry-alerts' ? 'active' : ''}" href="expiry-alerts.html">
        <i class="bi bi-alarm"></i> <span>Expiry Alerts</span>
      </a>
      <a class="nav-link ${page === 'low-stock' ? 'active' : ''}" href="low-stock.html">
        <i class="bi bi-exclamation-triangle"></i> <span>Low Stock</span>
      </a>

      <a class="nav-link ${page === 'suppliers' ? 'active' : ''}" href="suppliers.html">
        <i class="bi bi-truck"></i> <span>Suppliers</span>
      </a>
      <a class="nav-link ${page === 'transactions' ? 'active' : ''}" href="transactions.html">
        <i class="bi bi-clock-history"></i> <span>Transactions</span>
      </a>
      <a class="nav-link ${page === 'reports' ? 'active' : ''}" href="reports.html">
        <i class="bi bi-file-earmark-text"></i> <span>Reports</span>
      </a>

      ${adminLinks}

      <a class="nav-link ${page === 'profile' ? 'active' : ''}" href="profile.html">
        <i class="bi bi-person-circle"></i> <span>Profile</span>
      </a>
      <a class="nav-link ${page === 'settings' ? 'active' : ''}" href="settings.html">
        <i class="bi bi-gear"></i> <span>Settings</span>
      </a>
      <button type="button" id="logoutButton" class="nav-link btn btn-link text-start">
        <i class="bi bi-box-arrow-right"></i> <span>Logout</span>
      </button>
    </nav>
  `;
}

function initMobileSidebar() {
  const toggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  if (!toggle || !sidebar) return;

  toggle.addEventListener('click', function () {
    sidebar.classList.toggle('collapsed');
  });
}

function initializePage() {
  const page = document.body.dataset.page;
  if (page === 'dashboard') {
    if (typeof initDashboardPage === 'function') initDashboardPage();
  }
  if (page === 'expiry-alerts') {
    if (typeof renderExpiryAlertsTable === 'function') renderExpiryAlertsTable();
  }
  if (page === 'low-stock') {
    if (typeof renderLowStockTable === 'function') renderLowStockTable();
  }
  if (page === 'medicines') {
    if (typeof initMedicinesPage === 'function') initMedicinesPage();
  }
  if (page === 'batches') {
    if (typeof initBatchesPage === 'function') initBatchesPage();
  }
  if (page === 'stock-in' || page === 'stock-out') {
    if (typeof initStockPage === 'function') initStockPage();
  }
  if (page === 'suppliers') {
    if (typeof initSuppliersPage === 'function') initSuppliersPage();
  }
  if (page === 'transactions') {
    if (typeof initTransactionsPage === 'function') initTransactionsPage();
  }
  if (page === 'reports') {
    if (typeof initReportsPage === 'function') initReportsPage();
  }
  if (page === 'users') {
    if (typeof initUsersPage === 'function') initUsersPage();
  }
  if (page === 'profile') {
    if (typeof initProfilePage === 'function') initProfilePage();
  }
  if (page === 'settings') {
    if (typeof initSettingsPage === 'function') initSettingsPage();
  }
}

function showToast(message, variant = 'success') {
  const existing = document.getElementById('globalToast');
  if (existing) {
    const toastBody = existing.querySelector('.toast-body');
    if (toastBody) toastBody.textContent = message;
    existing.className = 'toast align-items-center border-0 text-bg-' + variant + ' show';
    const bsToast = bootstrap.Toast.getOrCreateInstance(existing);
    bsToast.show();
    return;
  }

  const container = document.querySelector('.toast-container') || document.body;
  const toast = document.createElement('div');
  toast.id = 'globalToast';
  toast.className = 'toast align-items-center border-0 text-bg-' + variant;
  toast.setAttribute('role', 'alert');
  toast.setAttribute('aria-live', 'assertive');
  toast.setAttribute('aria-atomic', 'true');
  toast.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">${message}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  `;
  container.appendChild(toast);
  const bsToast = bootstrap.Toast.getOrCreateInstance(toast);
  bsToast.show();
}

function updateGlobalTheme() {
  const current = getState();
  document.body.dataset.theme = current.settings.theme || 'light';
}
