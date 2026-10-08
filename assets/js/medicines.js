function initMedicinesPage() {
  const searchInput = document.getElementById('medicineSearch');
  if (searchInput) {
    searchInput.addEventListener('input', renderMedicinesTable);
  }

  const filterIds = ['medicineCategoryFilter', 'medicineManufacturerFilter', 'medicineExpiryFilter', 'medicineStatusFilter', 'medicineSortBy'];
  filterIds.forEach((id) => {
    const element = document.getElementById(id);
    if (element) {
      element.addEventListener('change', renderMedicinesTable);
      element.addEventListener('input', renderMedicinesTable);
    }
  });

  populateMedicineFilterOptions();
  document.getElementById('medicineForm').addEventListener('submit', saveMedicine);
  document.getElementById('cancelMedicineEdit').addEventListener('click', resetMedicineForm);

  renderMedicinesTable();
}

function populateMedicineFilterOptions() {
  const state = getState();
  const categories = [...new Set(state.medicines.map((medicine) => medicine.category))].sort();
  const manufacturers = [...new Set(state.medicines.map((medicine) => medicine.manufacturer))].sort();

  const categoryFilter = document.getElementById('medicineCategoryFilter');
  const manufacturerFilter = document.getElementById('medicineManufacturerFilter');

  if (categoryFilter) {
    categoryFilter.innerHTML = '<option value="">All</option>' + categories.map((category) => `<option value="${escapeHtml(category)}">${escapeHtml(category)}</option>`).join('');
  }

  if (manufacturerFilter) {
    manufacturerFilter.innerHTML = '<option value="">All</option>' + manufacturers.map((manufacturer) => `<option value="${escapeHtml(manufacturer)}">${escapeHtml(manufacturer)}</option>`).join('');
  }
}

function getFilteredMedicineRows() {
  const state = getState();
  const query = document.getElementById('medicineSearch')?.value.trim().toLowerCase() || '';
  const category = document.getElementById('medicineCategoryFilter')?.value || '';
  const manufacturer = document.getElementById('medicineManufacturerFilter')?.value || '';
  const expiryStatus = document.getElementById('medicineExpiryFilter')?.value || '';
  const stockStatus = document.getElementById('medicineStatusFilter')?.value || '';
  const sortBy = document.getElementById('medicineSortBy')?.value || 'name';

  const rows = state.medicines.map((medicine) => {
    const totalStock = calculateMedicineStock(medicine.id);
    const stockTag = totalStock < Number(medicine.minimumStock || 0) ? 'LOW STOCK' : 'OK';
    const earliestBatch = getRecommendedBatch(medicine.id);
    const earliestExpiry = earliestBatch ? calculateExpiryStatus(earliestBatch.expiryDate) : { label: 'SAFE', className: 'success', daysRemaining: Infinity };
    return {
      medicine,
      totalStock,
      stockTag,
      earliestBatch,
      earliestExpiry
    };
  }).filter(({ medicine, totalStock, stockTag, earliestExpiry }) => {
    const matchesSearch = !query || `${medicine.medicineId} ${medicine.name} ${medicine.category} ${medicine.manufacturer}`.toLowerCase().includes(query);
    const matchesCategory = !category || medicine.category === category;
    const matchesManufacturer = !manufacturer || medicine.manufacturer === manufacturer;
    const matchesExpiry = !expiryStatus || earliestExpiry.label === expiryStatus;
    const matchesStock = !stockStatus || stockTag === stockStatus;
    return matchesSearch && matchesCategory && matchesManufacturer && matchesExpiry && matchesStock;
  });

  rows.sort((a, b) => {
    if (sortBy === 'quantity') return b.totalStock - a.totalStock;
    if (sortBy === 'expiry') {
      const aDate = a.earliestBatch ? new Date(a.earliestBatch.expiryDate).getTime() : Number.MAX_SAFE_INTEGER;
      const bDate = b.earliestBatch ? new Date(b.earliestBatch.expiryDate).getTime() : Number.MAX_SAFE_INTEGER;
      return aDate - bDate;
    }
    return a.medicine.name.localeCompare(b.medicine.name);
  });

  return rows;
}

function renderMedicinesTable() {
  const tableBody = document.getElementById('medicineTableBody');
  if (!tableBody) return;

  const filtered = getFilteredMedicineRows();

  if (!filtered.length) {
    tableBody.innerHTML = '<tr><td colspan="9" class="empty-state">No medicines match your filters.</td></tr>';
    return;
  }

  tableBody.innerHTML = filtered.map(({ medicine, totalStock, stockTag, earliestBatch }) => {
    const recommendedText = earliestBatch ? earliestBatch.batchNumber : '—';
    return `
      <tr>
        <td>${escapeHtml(medicine.medicineId)}</td>
        <td>${escapeHtml(medicine.name)}</td>
        <td>${escapeHtml(medicine.category)}</td>
        <td>${escapeHtml(medicine.manufacturer)}</td>
        <td>${totalStock}</td>
        <td>${medicine.minimumStock}</td>
        <td>${escapeHtml(recommendedText)}</td>
        <td><span class="badge-status ${stockTag === 'LOW STOCK' ? 'low-stock' : 'safe'}">${stockTag}</span></td>
        <td>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-primary" data-edit-medicine="${medicine.id}">Edit</button>
            <button class="btn btn-sm btn-outline-secondary" data-details-medicine="${medicine.id}">Details</button>
            <button class="btn btn-sm btn-outline-danger" data-delete-medicine="${medicine.id}">Delete</button>
          </div>
        </td>
      </tr>
    `;
  }).join('');

  document.querySelectorAll('[data-edit-medicine]').forEach((button) => {
    button.addEventListener('click', () => populateMedicineForm(button.dataset.editMedicine));
  });

  document.querySelectorAll('[data-details-medicine]').forEach((button) => {
    button.addEventListener('click', () => showMedicineDetails(button.dataset.detailsMedicine));
  });

  document.querySelectorAll('[data-delete-medicine]').forEach((button) => {
    button.addEventListener('click', () => deleteMedicine(button.dataset.deleteMedicine));
  });
}

function showMedicineDetails(id) {
  const medicine = getMedicineById(id);
  if (!medicine) return;

  const batches = getState().batches.filter((batch) => batch.medicineId === id);
  const modalBody = document.getElementById('medicineDetailsBody');
  if (!modalBody) return;

  const batchRows = batches.length ? batches.map((batch) => `
    <tr>
      <td>${escapeHtml(batch.batchNumber)}</td>
      <td>${batch.quantity}</td>
      <td>${formatDate(batch.expiryDate)}</td>
      <td>${escapeHtml(batch.location)}</td>
      <td><span class="badge-status ${calculateExpiryStatus(batch.expiryDate).label === 'EXPIRED' ? 'danger' : calculateExpiryStatus(batch.expiryDate).label === 'EXPIRING SOON' ? 'warning' : 'safe'}">${calculateExpiryStatus(batch.expiryDate).label}</span></td>
    </tr>
  `).join('') : '<tr><td colspan="5" class="empty-state">No batches available.</td></tr>';

  modalBody.innerHTML = `
    <div class="row mb-3">
      <div class="col-md-6"><strong>Medicine ID:</strong> ${escapeHtml(medicine.medicineId)}</div>
      <div class="col-md-6"><strong>Category:</strong> ${escapeHtml(medicine.category)}</div>
      <div class="col-md-6"><strong>Manufacturer:</strong> ${escapeHtml(medicine.manufacturer)}</div>
      <div class="col-md-6"><strong>Unit:</strong> ${escapeHtml(medicine.unit)}</div>
      <div class="col-md-6"><strong>Minimum Stock:</strong> ${medicine.minimumStock}</div>
      <div class="col-md-6"><strong>Current Stock:</strong> ${calculateMedicineStock(medicine.id)}</div>
    </div>
    <p><strong>Description:</strong> ${escapeHtml(medicine.description || 'No description provided.')}</p>
    <div class="table-responsive">
      <table class="table table-sm">
        <thead>
          <tr>
            <th>Batch</th>
            <th>Quantity</th>
            <th>Expiry</th>
            <th>Location</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>${batchRows}</tbody>
      </table>
    </div>
  `;

  const modal = new bootstrap.Modal(document.getElementById('medicineDetailsModal'));
  modal.show();
}

function saveMedicine(event) {
  event.preventDefault();
  const state = getState();
  const medicineIdVal = document.getElementById('medicineIdInput').value.trim();
  const medicineName = document.getElementById('medicineName').value.trim();
  const medicineCategory = document.getElementById('medicineCategory').value.trim();
  const medicineManufacturer = document.getElementById('medicineManufacturer').value.trim();
  const medicineUnit = document.getElementById('medicineUnit').value.trim();
  const medicineMinimumStock = Number(document.getElementById('medicineMinimumStock').value);
  const medicineDescription = document.getElementById('medicineDescription').value.trim();
  const medicineRecordId = document.getElementById('medicineId').value;

  if (!medicineIdVal || !medicineName || !medicineCategory || !medicineManufacturer || !medicineUnit || Number.isNaN(medicineMinimumStock) || medicineMinimumStock < 0) {
    showToast('Please complete all required fields with valid values.', 'danger');
    return;
  }

  if (medicineRecordId) {
    const existing = state.medicines.find((item) => item.id === medicineRecordId);
    if (!existing) {
      showToast('Medicine not found.', 'danger');
      return;
    }
    const duplicate = state.medicines.some((item) => item.medicineId === medicineIdVal && item.id !== medicineRecordId);
    if (duplicate) {
      showToast('Medicine ID already exists.', 'danger');
      return;
    }

    existing.medicineId = medicineIdVal;
    existing.name = medicineName;
    existing.category = medicineCategory;
    existing.manufacturer = medicineManufacturer;
    existing.unit = medicineUnit;
    existing.minimumStock = medicineMinimumStock;
    existing.description = medicineDescription;
    existing.status = 'active';
    addAuditLog('Medicine edited', getCurrentUser()?.username || 'system');
    showToast('Medicine updated successfully.', 'success');
  } else {
    const duplicate = state.medicines.some((item) => item.medicineId === medicineIdVal);
    if (duplicate) {
      showToast('Medicine ID already exists.', 'danger');
      return;
    }

    state.medicines.push({
      id: generateId('MED'),
      medicineId: medicineIdVal,
      name: medicineName,
      category: medicineCategory,
      manufacturer: medicineManufacturer,
      unit: medicineUnit,
      minimumStock: medicineMinimumStock,
      description: medicineDescription,
      status: 'active'
    });
    addAuditLog('Medicine added', getCurrentUser()?.username || 'system');
    showToast('Medicine added successfully.', 'success');
  }

  saveState(state);
  document.getElementById('medicineForm').reset();
  resetMedicineForm();
  renderMedicinesTable();
}

function populateMedicineForm(id) {
  const state = getState();
  const medicine = state.medicines.find((item) => item.id === id);
  if (!medicine) return;

  document.getElementById('medicineId').value = medicine.id;
  document.getElementById('medicineIdInput').value = medicine.medicineId;
  document.getElementById('medicineName').value = medicine.name;
  document.getElementById('medicineCategory').value = medicine.category;
  document.getElementById('medicineManufacturer').value = medicine.manufacturer;
  document.getElementById('medicineUnit').value = medicine.unit;
  document.getElementById('medicineMinimumStock').value = medicine.minimumStock;
  document.getElementById('medicineDescription').value = medicine.description || '';
  document.getElementById('medicineFormTitle').textContent = 'Edit Medicine';
}

function resetMedicineForm() {
  document.getElementById('medicineForm').reset();
  document.getElementById('medicineId').value = '';
  document.getElementById('medicineFormTitle').textContent = 'Add Medicine';
}

function deleteMedicine(id) {
  const state = getState();
  const medicine = state.medicines.find((item) => item.id === id);
  if (!medicine) return;

  const confirmed = window.confirm(`Delete ${medicine.name}?`);
  if (!confirmed) return;

  state.medicines = state.medicines.filter((item) => item.id !== id);
  state.batches = state.batches.filter((batch) => batch.medicineId !== id);
  saveState(state);
  addAuditLog('Medicine deleted', getCurrentUser()?.username || 'system');
  showToast('Medicine deleted successfully.', 'success');
  renderMedicinesTable();
}

