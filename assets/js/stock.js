function initStockPage() {
  populateStockSelectors();
  const stockInForm = document.getElementById('stockInForm');
  const stockOutForm = document.getElementById('stockOutForm');

  if (stockInForm) {
    stockInForm.addEventListener('submit', submitStockIn);
    document.getElementById('stockInMedicine').addEventListener('change', updateStockInBatchOptions);
  }

  if (stockOutForm) {
    stockOutForm.addEventListener('submit', submitStockOut);
    document.getElementById('stockOutMedicine').addEventListener('change', updateStockOutBatchOptions);
  }
}

function populateStockSelectors() {
  const state = getState();
  const stockInMedicine = document.getElementById('stockInMedicine');
  const stockOutMedicine = document.getElementById('stockOutMedicine');
  const stockInSupplier = document.getElementById('stockInSupplier');

  if (stockInMedicine) {
    stockInMedicine.innerHTML = state.medicines.map((medicine) => `<option value="${medicine.id}">${medicine.name}</option>`).join('');
  }
  if (stockOutMedicine) {
    stockOutMedicine.innerHTML = state.medicines.map((medicine) => `<option value="${medicine.id}">${medicine.name}</option>`).join('');
  }
  if (stockInSupplier) {
    stockInSupplier.innerHTML = state.suppliers.map((supplier) => `<option value="${supplier.id}">${supplier.name}</option>`).join('');
  }

  updateStockInBatchOptions();
  updateStockOutBatchOptions();
  updateFEFOHint();
}

function updateFEFOHint() {
  const stockOutMedicine = document.getElementById('stockOutMedicine');
  const fefoHint = document.getElementById('fefoHint');
  if (!stockOutMedicine || !fefoHint) return;

  const medicineId = stockOutMedicine.value;
  const recommended = getRecommendedBatch(medicineId);

  if (!recommended) {
    fefoHint.textContent = 'No batch available for FEFO recommendation.';
    return;
  }

  fefoHint.innerHTML = `<strong>FEFO recommendation:</strong> Recommended batch: ${recommended.batchNumber} because it expires earlier. This is only an inventory-management recommendation and not medical advice.`;
}

function updateStockInBatchOptions() {
  const medicineSelect = document.getElementById('stockInMedicine');
  const batchSelect = document.getElementById('stockInBatch');
  if (!medicineSelect || !batchSelect) return;
  const medicineId = medicineSelect.value;
  const batches = getState().batches.filter((batch) => batch.medicineId === medicineId);
  batchSelect.innerHTML = batches.length ? batches.map((batch) => `<option value="${batch.id}">${batch.batchNumber} (${batch.quantity})</option>`).join('') : '<option value="">No batch available</option>';
}

function updateStockOutBatchOptions() {
  const medicineSelect = document.getElementById('stockOutMedicine');
  const batchSelect = document.getElementById('stockOutBatch');
  if (!medicineSelect || !batchSelect) return;
  const medicineId = medicineSelect.value;
  const batches = getState().batches.filter((batch) => batch.medicineId === medicineId);
  batchSelect.innerHTML = batches.length ? batches.map((batch) => `<option value="${batch.id}">${batch.batchNumber} (${batch.quantity})</option>`).join('') : '<option value="">No batch available</option>';
  updateFEFOHint();
}

function submitStockIn(event) {
  event.preventDefault();
  const state = getState();
  const medicineId = document.getElementById('stockInMedicine').value;
  const batchId = document.getElementById('stockInBatch').value;
  const supplierId = document.getElementById('stockInSupplier').value;
  const quantity = Number(document.getElementById('stockInQuantity').value);
  const purchaseDate = document.getElementById('stockInDate').value;

  if (!medicineId || !supplierId || !purchaseDate || Number.isNaN(quantity) || quantity <= 0) {
    showToast('Please enter valid stock-in details.', 'danger');
    return;
  }

  let batch = state.batches.find((item) => item.id === batchId);
  if (!batch) {
    const medicine = getMedicineById(medicineId);
    batch = {
      id: generateId('BAT'),
      batchId: 'B' + (state.batches.length + 1).toString().padStart(3, '0'),
      medicineId,
      batchNumber: `AUTO-${Date.now().toString().slice(-4)}`,
      quantity: 0,
      purchaseDate,
      expiryDate: new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10),
      supplierId,
      location: 'New Arrival'
    };
    state.batches.push(batch);
  }

  batch.quantity += quantity;
  batch.purchaseDate = purchaseDate;
  batch.supplierId = supplierId;

  const transaction = {
    id: generateId('TXN'),
    date: new Date().toISOString(),
    medicineId,
    medicineName: getMedicineNameById(medicineId),
    batchNumber: batch.batchNumber,
    type: 'STOCK IN',
    quantity,
    user: getCurrentUser()?.username || 'system',
    supplierId
  };

  state.transactions.unshift(transaction);
  saveState(state);
  addAuditLog('Stock In', getCurrentUser()?.username || 'system');
  showToast('Stock updated successfully.', 'success');
  document.getElementById('stockInForm').reset();
  populateStockSelectors();
}

function submitStockOut(event) {
  event.preventDefault();
  const state = getState();
  const medicineId = document.getElementById('stockOutMedicine').value;
  const batchId = document.getElementById('stockOutBatch').value;
  const quantity = Number(document.getElementById('stockOutQuantity').value);

  if (!medicineId || !batchId || Number.isNaN(quantity) || quantity <= 0) {
    showToast('Please provide valid stock-out details.', 'danger');
    return;
  }

  const batch = state.batches.find((item) => item.id === batchId);
  if (!batch) {
    showToast('Batch not found.', 'danger');
    return;
  }

  if (quantity > Number(batch.quantity)) {
    showToast('Insufficient stock available.', 'danger');
    return;
  }

  batch.quantity = Number(batch.quantity) - quantity;

  const transaction = {
    id: generateId('TXN'),
    date: new Date().toISOString(),
    medicineId,
    medicineName: getMedicineNameById(medicineId),
    batchNumber: batch.batchNumber,
    type: 'STOCK OUT',
    quantity,
    user: getCurrentUser()?.username || 'system',
    supplierId: null
  };

  state.transactions.unshift(transaction);
  saveState(state);
  addAuditLog('Stock Out', getCurrentUser()?.username || 'system');
  showToast('Stock out recorded successfully.', 'success');
  document.getElementById('stockOutForm').reset();
  populateStockSelectors();
}

