function initBatchesPage() {
  populateBatchSelectors();
  document.getElementById('batchForm').addEventListener('submit', saveBatch);
  document.getElementById('cancelBatchEdit').addEventListener('click', resetBatchForm);
  renderBatchTable();
}

function populateBatchSelectors() {
  const state = getState();
  const medicineSelect = document.getElementById('batchMedicine');
  const supplierSelect = document.getElementById('batchSupplier');

  if (medicineSelect) {
    medicineSelect.innerHTML = state.medicines.map((medicine) => `<option value="${medicine.id}">${medicine.name}</option>`).join('');
  }

  if (supplierSelect) {
    supplierSelect.innerHTML = state.suppliers.map((supplier) => `<option value="${supplier.id}">${supplier.name}</option>`).join('');
  }
}

function renderBatchTable() {
  const tableBody = document.getElementById('batchTableBody');
  if (!tableBody) return;

  const state = getState();
  const rows = state.batches.map((batch) => {
    const medicine = getMedicineById(batch.medicineId);
    const supplier = getSupplierById(batch.supplierId);
    const expiryState = calculateExpiryStatus(batch.expiryDate);
    return {
      ...batch,
      medicineName: medicine ? medicine.name : 'Unknown',
      supplierName: supplier ? supplier.name : 'Unknown',
      expiryState
    };
  }).sort((a, b) => new Date(a.expiryDate) - new Date(b.expiryDate));

  tableBody.innerHTML = rows.map((batch) => `
    <tr>
      <td>${escapeHtml(batch.medicineName)}</td>
      <td>${escapeHtml(batch.batchNumber)}</td>
      <td>${batch.quantity}</td>
      <td>${formatDate(batch.purchaseDate)}</td>
      <td>${formatDate(batch.expiryDate)}</td>
      <td>${escapeHtml(batch.supplierName)}</td>
      <td>${escapeHtml(batch.location)}</td>
      <td><span class="badge-status ${batch.expiryState.className}">${batch.expiryState.label}</span></td>
      <td>
        <div class="d-flex gap-2">
          <button class="btn btn-sm btn-outline-primary" data-edit-batch="${batch.id}">Edit</button>
          <button class="btn btn-sm btn-outline-danger" data-delete-batch="${batch.id}">Delete</button>
        </div>
      </td>
    </tr>
  `).join('');

  document.querySelectorAll('[data-edit-batch]').forEach((button) => {
    button.addEventListener('click', () => populateBatchForm(button.dataset.editBatch));
  });

  document.querySelectorAll('[data-delete-batch]').forEach((button) => {
    button.addEventListener('click', () => deleteBatch(button.dataset.deleteBatch));
  });
}

function saveBatch(event) {
  event.preventDefault();
  const state = getState();
  const medicineId = document.getElementById('batchMedicine').value;
  const batchNumber = document.getElementById('batchNumber').value.trim();
  const quantity = Number(document.getElementById('batchQuantity').value);
  const purchaseDate = document.getElementById('batchPurchaseDate').value;
  const expiryDate = document.getElementById('batchExpiryDate').value;
  const supplierId = document.getElementById('batchSupplier').value;
  const location = document.getElementById('batchLocation').value.trim();
  const batchRecordId = document.getElementById('batchId').value;

  if (!medicineId || !batchNumber || !purchaseDate || !expiryDate || !supplierId || !location || Number.isNaN(quantity) || quantity < 0) {
    showToast('Please enter valid batch details.', 'danger');
    return;
  }

  const duplicate = state.batches.some((batch) => batch.batchNumber === batchNumber && batch.id !== batchRecordId);
  if (duplicate) {
    showToast('Batch number already exists.', 'danger');
    return;
  }

  if (batchRecordId) {
    const target = state.batches.find((batch) => batch.id === batchRecordId);
    if (!target) {
      showToast('Batch not found.', 'danger');
      return;
    }
    target.medicineId = medicineId;
    target.batchNumber = batchNumber;
    target.quantity = quantity;
    target.purchaseDate = purchaseDate;
    target.expiryDate = expiryDate;
    target.supplierId = supplierId;
    target.location = location;
    addAuditLog('Batch edited', getCurrentUser()?.username || 'system');
    showToast('Batch updated successfully.', 'success');
  } else {
    state.batches.push({
      id: generateId('BAT'),
      batchId: 'B' + (state.batches.length + 1).toString().padStart(3, '0'),
      medicineId,
      batchNumber,
      quantity,
      purchaseDate,
      expiryDate,
      supplierId,
      location
    });
    addAuditLog('Batch added', getCurrentUser()?.username || 'system');
    showToast('Batch added successfully.', 'success');
  }

  saveState(state);
  renderBatchTable();
  resetBatchForm();
}

function populateBatchForm(id) {
  const batch = getBatchById(id);
  if (!batch) return;

  document.getElementById('batchId').value = batch.id;
  document.getElementById('batchMedicine').value = batch.medicineId;
  document.getElementById('batchNumber').value = batch.batchNumber;
  document.getElementById('batchQuantity').value = batch.quantity;
  document.getElementById('batchPurchaseDate').value = batch.purchaseDate;
  document.getElementById('batchExpiryDate').value = batch.expiryDate;
  document.getElementById('batchSupplier').value = batch.supplierId;
  document.getElementById('batchLocation').value = batch.location;
  document.getElementById('batchFormTitle').textContent = 'Edit Batch';
}

function resetBatchForm() {
  document.getElementById('batchForm').reset();
  document.getElementById('batchId').value = '';
  document.getElementById('batchFormTitle').textContent = 'Add Batch';
  populateBatchSelectors();
}

function deleteBatch(id) {
  const state = getState();
  const batch = state.batches.find((item) => item.id === id);
  if (!batch) return;

  const confirmed = window.confirm(`Delete batch ${batch.batchNumber}?`);
  if (!confirmed) return;

  state.batches = state.batches.filter((item) => item.id !== id);
  saveState(state);
  addAuditLog('Batch deleted', getCurrentUser()?.username || 'system');
  showToast('Batch deleted successfully.', 'success');
  renderBatchTable();
}

