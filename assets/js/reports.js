function initReportsPage() {
  renderInventoryReport();
  renderExpiryReport();
  renderLowStockReport();
  renderTransactionReport();

  document.getElementById('printReportButton').addEventListener('click', () => window.print());
  document.querySelectorAll('[data-export]').forEach((button) => {
    button.addEventListener('click', () => exportReport(button.dataset.export));
  });
}

function renderInventoryReport() {
  const tbody = document.getElementById('inventoryReportBody');
  if (!tbody) return;
  const rows = getState().batches.map((batch) => ({
    medicine: getMedicineNameById(batch.medicineId),
    batch: batch.batchNumber,
    quantity: batch.quantity,
    expiry: formatDate(batch.expiryDate),
    location: batch.location
  }));

  tbody.innerHTML = rows.map((row) => `
    <tr>
      <td>${escapeHtml(row.medicine)}</td>
      <td>${escapeHtml(row.batch)}</td>
      <td>${row.quantity}</td>
      <td>${escapeHtml(row.expiry)}</td>
      <td>${escapeHtml(row.location)}</td>
    </tr>
  `).join('');
}

function renderExpiryReport() {
  const tbody = document.getElementById('expiryReportBody');
  if (!tbody) return;
  const rows = getExpiryAlerts().map((row) => ({
    medicine: row.medicineName,
    batch: row.batchNumber,
    expiry: formatDate(row.expiryDate),
    days: row.daysRemaining,
    status: row.status
  }));

  tbody.innerHTML = rows.map((row) => `
    <tr>
      <td>${escapeHtml(row.medicine)}</td>
      <td>${escapeHtml(row.batch)}</td>
      <td>${escapeHtml(row.expiry)}</td>
      <td>${row.days}</td>
      <td><span class="badge-status ${row.status === 'EXPIRING SOON' ? 'warning' : 'danger'}">${row.status}</span></td>
    </tr>
  `).join('');
}

function renderLowStockReport() {
  const tbody = document.getElementById('lowStockReportBody');
  if (!tbody) return;
  const rows = getLowStockMedicines().map((item) => ({
    medicine: item.name,
    current: item.currentStock,
    minimum: item.minimumStock
  }));

  tbody.innerHTML = rows.map((row) => `
    <tr>
      <td>${escapeHtml(row.medicine)}</td>
      <td>${row.current}</td>
      <td>${row.minimum}</td>
    </tr>
  `).join('');
}

function renderTransactionReport() {
  const tbody = document.getElementById('transactionReportBody');
  if (!tbody) return;
  const rows = getState().transactions.slice(0, 20).map((txn) => ({
    date: formatDateTime(txn.date),
    medicine: txn.medicineName || getMedicineNameById(txn.medicineId),
    batch: txn.batchNumber,
    stockIn: txn.type === 'STOCK IN' ? txn.quantity : '',
    stockOut: txn.type === 'STOCK OUT' ? txn.quantity : '',
    user: txn.user
  }));

  tbody.innerHTML = rows.map((row) => `
    <tr>
      <td>${escapeHtml(row.date)}</td>
      <td>${escapeHtml(row.medicine)}</td>
      <td>${escapeHtml(row.batch)}</td>
      <td>${row.stockIn}</td>
      <td>${row.stockOut}</td>
      <td>${escapeHtml(row.user)}</td>
    </tr>
  `).join('');
}

function exportReport(kind) {
  const state = getState();
  let rows = [];
  if (kind === 'inventory') {
    rows = state.batches.map((batch) => ({ Medicine: getMedicineNameById(batch.medicineId), Batch: batch.batchNumber, Quantity: batch.quantity, Expiry: formatDate(batch.expiryDate), Location: batch.location }));
  }
  if (kind === 'expiry') {
    rows = getExpiryAlerts().map((row) => ({ Medicine: row.medicineName, Batch: row.batchNumber, ExpiryDate: formatDate(row.expiryDate), DaysRemaining: row.daysRemaining, Status: row.status }));
  }
  if (kind === 'lowstock') {
    rows = getLowStockMedicines().map((item) => ({ Medicine: item.name, CurrentStock: item.currentStock, MinimumStock: item.minimumStock }));
  }
  if (kind === 'transactions') {
    rows = state.transactions.map((txn) => ({ Date: formatDateTime(txn.date), Medicine: txn.medicineName || getMedicineNameById(txn.medicineId), Batch: txn.batchNumber || '', StockIn: txn.type === 'STOCK IN' ? txn.quantity : '', StockOut: txn.type === 'STOCK OUT' ? txn.quantity : '', User: txn.user }));
  }
  if (!rows.length) {
    showToast('No rows available to export.', 'warning');
    return;
  }

  exportCsv(kind + '-report.csv', rows);
  showToast('CSV exported successfully.', 'success');
}

