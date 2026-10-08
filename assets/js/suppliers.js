function initSuppliersPage() {
  const searchInput = document.getElementById('supplierSearch');
  if (searchInput) {
    searchInput.addEventListener('input', renderSuppliersTable);
  }

  document.getElementById('supplierForm').addEventListener('submit', saveSupplier);
  document.getElementById('cancelSupplierEdit').addEventListener('click', resetSupplierForm);
  renderSuppliersTable();
}

function renderSuppliersTable() {
  const tableBody = document.getElementById('supplierTableBody');
  if (!tableBody) return;

  const state = getState();
  const query = document.getElementById('supplierSearch')?.value.trim().toLowerCase() || '';
  const filtered = state.suppliers.filter((supplier) => {
    const haystack = `${supplier.supplierId} ${supplier.name} ${supplier.phone} ${supplier.email} ${supplier.address}`.toLowerCase();
    return haystack.includes(query);
  });

  if (!filtered.length) {
    tableBody.innerHTML = '<tr><td colspan="6" class="empty-state">No suppliers found.</td></tr>';
    return;
  }

  tableBody.innerHTML = filtered.map((supplier) => `
    <tr>
      <td>${escapeHtml(supplier.supplierId)}</td>
      <td>${escapeHtml(supplier.name)}</td>
      <td>${escapeHtml(supplier.phone)}</td>
      <td>${escapeHtml(supplier.email)}</td>
      <td>${escapeHtml(supplier.address)}</td>
      <td>
        <div class="d-flex gap-2">
          <button class="btn btn-sm btn-outline-primary" data-edit-supplier="${supplier.id}">Edit</button>
          <button class="btn btn-sm btn-outline-danger" data-delete-supplier="${supplier.id}">Delete</button>
        </div>
      </td>
    </tr>
  `).join('');

  document.querySelectorAll('[data-edit-supplier]').forEach((button) => {
    button.addEventListener('click', () => populateSupplierForm(button.dataset.editSupplier));
  });

  document.querySelectorAll('[data-delete-supplier]').forEach((button) => {
    button.addEventListener('click', () => deleteSupplier(button.dataset.deleteSupplier));
  });
}

function saveSupplier(event) {
  event.preventDefault();
  const state = getState();
  const supplierIdInput = document.getElementById('supplierIdInput').value.trim();
  const supplierName = document.getElementById('supplierName').value.trim();
  const supplierPhone = document.getElementById('supplierPhone').value.trim();
  const supplierEmail = document.getElementById('supplierEmail').value.trim();
  const supplierAddress = document.getElementById('supplierAddress').value.trim();
  const supplierRecordId = document.getElementById('supplierId').value;

  if (!supplierIdInput || !supplierName || !supplierPhone || !supplierEmail || !supplierAddress) {
    showToast('Please fill in all supplier fields.', 'danger');
    return;
  }

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(supplierEmail)) {
    showToast('Please enter a valid email address.', 'danger');
    return;
  }

  if (supplierRecordId) {
    const supplier = state.suppliers.find((item) => item.id === supplierRecordId);
    if (!supplier) {
      showToast('Supplier not found.', 'danger');
      return;
    }
    const duplicate = state.suppliers.some((item) => item.supplierId === supplierIdInput && item.id !== supplierRecordId);
    if (duplicate) {
      showToast('Supplier ID already exists.', 'danger');
      return;
    }
    supplier.supplierId = supplierIdInput;
    supplier.name = supplierName;
    supplier.phone = supplierPhone;
    supplier.email = supplierEmail;
    supplier.address = supplierAddress;
    addAuditLog('Supplier edited', getCurrentUser()?.username || 'system');
    showToast('Supplier updated successfully.', 'success');
  } else {
    const duplicate = state.suppliers.some((item) => item.supplierId === supplierIdInput);
    if (duplicate) {
      showToast('Supplier ID already exists.', 'danger');
      return;
    }
    state.suppliers.push({
      id: generateId('SUP'),
      supplierId: supplierIdInput,
      name: supplierName,
      phone: supplierPhone,
      email: supplierEmail,
      address: supplierAddress
    });
    addAuditLog('Supplier added', getCurrentUser()?.username || 'system');
    showToast('Supplier added successfully.', 'success');
  }

  saveState(state);
  renderSuppliersTable();
  resetSupplierForm();
}

function populateSupplierForm(id) {
  const supplier = getSupplierById(id);
  if (!supplier) return;

  document.getElementById('supplierId').value = supplier.id;
  document.getElementById('supplierIdInput').value = supplier.supplierId;
  document.getElementById('supplierName').value = supplier.name;
  document.getElementById('supplierPhone').value = supplier.phone;
  document.getElementById('supplierEmail').value = supplier.email;
  document.getElementById('supplierAddress').value = supplier.address;
  document.getElementById('supplierFormTitle').textContent = 'Edit Supplier';
}

function resetSupplierForm() {
  document.getElementById('supplierForm').reset();
  document.getElementById('supplierId').value = '';
  document.getElementById('supplierFormTitle').textContent = 'Add Supplier';
}

function deleteSupplier(id) {
  const state = getState();
  const supplier = state.suppliers.find((item) => item.id === id);
  if (!supplier) return;

  const confirmed = window.confirm(`Delete supplier ${supplier.name}?`);
  if (!confirmed) return;

  state.suppliers = state.suppliers.filter((item) => item.id !== id);
  state.batches = state.batches.filter((batch) => batch.supplierId !== id);
  saveState(state);
  addAuditLog('Supplier deleted', getCurrentUser()?.username || 'system');
  showToast('Supplier deleted successfully.', 'success');
  renderSuppliersTable();
}

