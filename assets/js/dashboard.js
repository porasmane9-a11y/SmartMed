function initDashboardPage() {
  renderDashboardStats();
  renderAlerts();
  renderRecentTransactions();
  renderDashboardCharts();
}

function renderDashboardStats() {
  const counts = getDashboardCounts();
  const stats = [
    { label: 'Total Medicines', value: counts.totalMedicines, icon: 'bi-capsule', tone: 'blue' },
    { label: 'Total Available Stock', value: counts.totalAvailableStock, icon: 'bi-boxes', tone: 'green' },
    { label: 'Low Stock Medicines', value: counts.lowStockMedicines, icon: 'bi-exclamation-triangle', tone: 'orange' },
    { label: 'Expiring Soon', value: counts.expiringSoon, icon: 'bi-alarm', tone: 'warning' },
    { label: 'Expired Medicines', value: counts.expired, icon: 'bi-x-circle', tone: 'red' },
    { label: 'Total Suppliers', value: counts.suppliers, icon: 'bi-truck', tone: 'teal' },
    { label: "Today's Stock In", value: counts.todaysStockIn, icon: 'bi-arrow-down-circle', tone: 'green' },
    { label: "Today's Stock Out", value: counts.todaysStockOut, icon: 'bi-arrow-up-circle', tone: 'red' }
  ];

  const container = document.getElementById('statsGrid');
  if (!container) return;

  container.innerHTML = stats.map((stat) => `
    <div class="stat-card">
      <div class="kpi-row">
        <div class="stat-icon ${stat.tone}"><i class="bi ${stat.icon}"></i></div>
        <div>
          <div class="stat-value">${stat.value}</div>
          <div class="stat-label">${stat.label}</div>
        </div>
      </div>
    </div>
  `).join('');
}

function renderAlerts() {
  const alertList = document.getElementById('alertList');
  if (!alertList) return;

  const alerts = [];
  const expiryAlerts = getExpiryAlerts();
  const lowStockItems = getLowStockMedicines();

  if (expiryAlerts.length) {
    const expired = expiryAlerts.filter((item) => item.status === 'EXPIRED').length;
    const expiring = expiryAlerts.filter((item) => item.status === 'EXPIRING SOON').length;

    if (expired > 0) {
      alerts.push({
        title: `${expired} medicines are expired`,
        type: 'danger',
        href: 'expiry-alerts.html'
      });
    }

    if (expiring > 0) {
      alerts.push({
        title: `${expiring} medicines are expiring within ${getState().settings.expiryWarningDays || 90} days`,
        type: 'warning',
        href: 'expiry-alerts.html'
      });
    }
  }

  if (lowStockItems.length) {
    alerts.push({
      title: `${lowStockItems.length} medicines are low in stock`,
      type: 'orange',
      href: 'low-stock.html'
    });
  }

  if (!alerts.length) {
    alertList.innerHTML = '<div class="empty-state">No active alerts.</div>';
    return;
  }

  alertList.innerHTML = alerts.map((alert) => `
    <a href="${alert.href}" class="alert-item">
      <div class="text">
        <i class="bi ${alert.type === 'danger' ? 'bi-exclamation-circle-fill text-danger' : alert.type === 'orange' ? 'bi-exclamation-triangle-fill text-warning' : 'bi-info-circle-fill text-warning'}"></i>
        <span>${alert.title}</span>
      </div>
      <span class="alert-badge bg-${alert.type === 'danger' ? 'danger-soft' : alert.type === 'orange' ? 'orange-soft' : 'warning-soft'}">${alert.type}</span>
    </a>
  `).join('');
}

function renderRecentTransactions() {
  const table = document.getElementById('recentTransactionsTable');
  if (!table) return;

  const transactions = [...getState().transactions].sort((a, b) => new Date(b.date) - new Date(a.date)).slice(0, 6);
  table.innerHTML = transactions.map((tx) => `
    <tr>
      <td>${formatDateTime(tx.date)}</td>
      <td>${escapeHtml(tx.medicineName || getMedicineNameById(tx.medicineId))}</td>
      <td>${escapeHtml(tx.batchNumber || '—')}</td>
      <td><span class="badge-status ${tx.type === 'STOCK IN' ? 'stock-in' : 'stock-out'}">${tx.type}</span></td>
      <td>${tx.quantity}</td>
      <td>${escapeHtml(tx.user)}</td>
    </tr>
  `).join('');
}

function renderDashboardCharts() {
  if (typeof Chart === 'undefined') return;

  const categoryData = getCategoryDistribution();
  const stockMovement = getStockMovementData();
  const expiryData = getExpiryOverviewData();

  new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
      labels: categoryData.labels,
      datasets: [{ data: categoryData.values, backgroundColor: ['#0f6cbd', '#32b5b3', '#f5a623', '#1ea97d', '#e55d5d', '#7aa3ff', '#6ec6ca'] }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
  });

  new Chart(document.getElementById('stockMovementChart'), {
    type: 'bar',
    data: {
      labels: ['This Week'],
      datasets: [
        { label: 'Stock In', data: [stockMovement.stockIn], backgroundColor: '#1ea97d' },
        { label: 'Stock Out', data: [stockMovement.stockOut], backgroundColor: '#f5a623' }
      ]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
  });

  new Chart(document.getElementById('expiryChart'), {
    type: 'polarArea',
    data: {
      labels: ['Safe', 'Expiring Soon', 'Expired'],
      datasets: [{ data: [expiryData.safe, expiryData.expiringSoon, expiryData.expired], backgroundColor: ['#1ea97d', '#f5a623', '#e55d5d'] }]
    },
    options: { responsive: true }
  });
}

function getCategoryDistribution() {
  const state = getState();
  const map = {};
  state.medicines.forEach((medicine) => {
    map[medicine.category] = (map[medicine.category] || 0) + 1;
  });
  return {
    labels: Object.keys(map),
    values: Object.values(map)
  };
}

function getStockMovementData() {
  const state = getState();
  const stockIn = state.transactions.filter((tx) => tx.type === 'STOCK IN').reduce((sum, item) => sum + Number(item.quantity || 0), 0);
  const stockOut = state.transactions.filter((tx) => tx.type === 'STOCK OUT').reduce((sum, item) => sum + Number(item.quantity || 0), 0);
  return { stockIn, stockOut };
}

function getExpiryOverviewData() {
  const expiry = getExpiryAlerts();
  const safe = getState().batches.filter((batch) => {
    const status = calculateExpiryStatus(batch.expiryDate).label;
    return status === 'SAFE';
  }).length;

  return {
    safe,
    expiringSoon: expiry.filter((item) => item.status === 'EXPIRING SOON').length,
    expired: expiry.filter((item) => item.status === 'EXPIRED').length
  };
}

function renderExpiryAlertsTable() {
  const table = document.getElementById('expiryAlertTable');
  if (!table) return;

  const rows = getExpiryAlerts();
  if (!rows.length) {
    table.innerHTML = '<tr><td colspan="7" class="empty-state">No expiry alerts for the configured threshold.</td></tr>';
    return;
  }

  table.innerHTML = rows.map((row) => `
    <tr>
      <td>${escapeHtml(row.medicineName)}</td>
      <td>${escapeHtml(row.batchNumber)}</td>
      <td>${formatDate(row.expiryDate)}</td>
      <td>${row.daysRemaining}</td>
      <td><span class="badge-status ${row.status === 'EXPIRING SOON' ? 'warning' : 'danger'}">${row.status}</span></td>
      <td>${row.quantity}</td>
      <td>${escapeHtml(row.location)}</td>
    </tr>
  `).join('');
}

function renderLowStockTable() {
  const table = document.getElementById('lowStockTable');
  if (!table) return;

  const rows = getLowStockMedicines();
  if (!rows.length) {
    table.innerHTML = '<tr><td colspan="4" class="empty-state">No medicines are currently low in stock.</td></tr>';
    return;
  }

  table.innerHTML = rows.map((row) => `
    <tr>
      <td>${escapeHtml(row.name)}</td>
      <td>${row.currentStock}</td>
      <td>${row.minimumStock}</td>
      <td><span class="badge-status low-stock">LOW STOCK</span></td>
    </tr>
  `).join('');
}

