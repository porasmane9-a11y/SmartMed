function initTransactionsPage() {
  const filters = ['transactionDateFilter', 'transactionMedicineFilter', 'transactionTypeFilter', 'transactionUserFilter'];
  filters.forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', renderTransactionTable);
    if (el) el.addEventListener('change', renderTransactionTable);
  });

  renderTransactionTable();
}

function renderTransactionTable() {
  const tableBody = document.getElementById('transactionTableBody');
  if (!tableBody) return;

  const state = getState();
  const dateFilter = document.getElementById('transactionDateFilter').value;
  const medicineFilter = document.getElementById('transactionMedicineFilter').value.trim().toLowerCase();
  const typeFilter = document.getElementById('transactionTypeFilter').value;
  const userFilter = document.getElementById('transactionUserFilter').value.trim().toLowerCase();

  const filtered = state.transactions.filter((txn) => {
    const matchesDate = !dateFilter || txn.date.slice(0, 10) === dateFilter;
    const matchesMedicine = !medicineFilter || (txn.medicineName || '').toLowerCase().includes(medicineFilter);
    const matchesType = !typeFilter || txn.type === typeFilter;
    const matchesUser = !userFilter || (txn.user || '').toLowerCase().includes(userFilter);
    return matchesDate && matchesMedicine && matchesType && matchesUser;
  }).sort((a, b) => new Date(b.date) - new Date(a.date));

  if (!filtered.length) {
    tableBody.innerHTML = '<tr><td colspan="6" class="empty-state">No transactions found.</td></tr>';
    return;
  }

  tableBody.innerHTML = filtered.map((txn) => `
    <tr>
      <td>${formatDateTime(txn.date)}</td>
      <td>${escapeHtml(txn.medicineName || getMedicineNameById(txn.medicineId))}</td>
      <td>${escapeHtml(txn.batchNumber || '—')}</td>
      <td><span class="badge-status ${txn.type === 'STOCK IN' ? 'stock-in' : 'stock-out'}">${txn.type}</span></td>
      <td>${txn.quantity}</td>
      <td>${escapeHtml(txn.user)}</td>
    </tr>
  `).join('');
}

