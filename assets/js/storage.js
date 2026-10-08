const STORAGE_KEY = 'smartmed_app_state';
const AUTH_KEY = 'smartmed_current_user';

function generateId(prefix) {
  return prefix + '-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 8);
}

function getDefaultState() {
  return {
    settings: {
      expiryWarningDays: 90,
      theme: 'light',
      lowStockAlert: 'enabled'
    },
    users: [
      { id: 'USR-ADMIN', name: 'Admin User', username: 'admin', password: 'admin123', role: 'admin', status: 'active' },
      { id: 'USR-STAFF', name: 'Staff User', username: 'staff', password: 'staff123', role: 'staff', status: 'active' }
    ],
    suppliers: [
      { id: 'SUP-001', supplierId: 'SUP001', name: 'PrimeCare Pharma', phone: '9876543210', email: 'sales@primecare.com', address: 'MG Road, Bengaluru' },
      { id: 'SUP-002', supplierId: 'SUP002', name: 'MedLine Labs', phone: '9988776655', email: 'orders@medline.com', address: 'Koramangala, Bengaluru' },
      { id: 'SUP-003', supplierId: 'SUP003', name: 'Apex Healthcare', phone: '9123456780', email: 'support@apexhealthcare.com', address: 'Whitefield, Bengaluru' },
      { id: 'SUP-004', supplierId: 'SUP004', name: 'CarePlus Distribution', phone: '9012345678', email: 'care@careplusdist.com', address: 'HSR Layout, Bengaluru' },
      { id: 'SUP-005', supplierId: 'SUP005', name: 'HealthBridge Supply', phone: '9807654321', email: 'hello@healthbridge.com', address: 'Indiranagar, Bengaluru' }
    ],
    medicines: [
      { id: 'MED-001', medicineId: 'MED001', name: 'Paracetamol', category: 'Tablet', manufacturer: 'ABC Pharma', unit: 'Tablets', minimumStock: 50, description: 'Pain and fever relief', status: 'active' },
      { id: 'MED-002', medicineId: 'MED002', name: 'Amoxicillin', category: 'Capsule', manufacturer: 'Wellcare Labs', unit: 'Capsules', minimumStock: 40, description: 'Antibiotic capsules', status: 'active' },
      { id: 'MED-003', medicineId: 'MED003', name: 'Cough Syrup', category: 'Syrup', manufacturer: 'LifeMed', unit: 'Bottles', minimumStock: 25, description: 'Cough suppressant', status: 'active' },
      { id: 'MED-004', medicineId: 'MED004', name: 'Insulin Glargine', category: 'Injection', manufacturer: 'NovoCare', unit: 'vials', minimumStock: 30, description: 'Diabetes management injection', status: 'active' },
      { id: 'MED-005', medicineId: 'MED005', name: 'Vitamin C', category: 'Tablet', manufacturer: 'Sunrise Health', unit: 'Tablets', minimumStock: 60, description: 'Immunity support tablets', status: 'active' },
      { id: 'MED-006', medicineId: 'MED006', name: 'Salbutamol', category: 'Inhaler', manufacturer: 'BreathWell', unit: 'Inhalers', minimumStock: 20, description: 'Bronchodilator inhaler', status: 'active' },
      { id: 'MED-007', medicineId: 'MED007', name: 'Oral Rehydration Salts', category: 'Powder', manufacturer: 'HydraCare', unit: 'Packets', minimumStock: 35, description: 'Electrolyte replacement', status: 'active' },
      { id: 'MED-008', medicineId: 'MED008', name: 'Ibuprofen', category: 'Tablet', manufacturer: 'Rapid Relief', unit: 'Tablets', minimumStock: 45, description: 'Anti-inflammatory tablets', status: 'active' },
      { id: 'MED-009', medicineId: 'MED009', name: 'Amlodipine', category: 'Tablet', manufacturer: 'HeartCare', unit: 'Tablets', minimumStock: 30, description: 'Blood pressure medicine', status: 'active' },
      { id: 'MED-010', medicineId: 'MED010', name: 'Cetirizine', category: 'Tablet', manufacturer: 'AllergyCare', unit: 'Tablets', minimumStock: 55, description: 'Allergy relief tablets', status: 'active' }
    ],
    batches: [
      { id: 'BAT-001', batchId: 'B001', medicineId: 'MED-001', batchNumber: 'P101', quantity: 180, purchaseDate: '2024-01-15', expiryDate: '2026-12-15', supplierId: 'SUP-001', location: 'Rack A-03' },
      { id: 'BAT-002', batchId: 'B002', medicineId: 'MED-001', batchNumber: 'P102', quantity: 220, purchaseDate: '2025-02-10', expiryDate: '2028-08-20', supplierId: 'SUP-002', location: 'Rack A-04' },
      { id: 'BAT-003', batchId: 'B003', medicineId: 'MED-002', batchNumber: 'A110', quantity: 95, purchaseDate: '2024-06-05', expiryDate: '2026-07-22', supplierId: 'SUP-002', location: 'Rack B-01' },
      { id: 'BAT-004', batchId: 'B004', medicineId: 'MED-003', batchNumber: 'S220', quantity: 80, purchaseDate: '2025-01-12', expiryDate: '2027-05-18', supplierId: 'SUP-003', location: 'Rack C-02' },
      { id: 'BAT-005', batchId: 'B005', medicineId: 'MED-004', batchNumber: 'I340', quantity: 25, purchaseDate: '2024-11-01', expiryDate: '2026-04-10', supplierId: 'SUP-005', location: 'Cooler 1' },
      { id: 'BAT-006', batchId: 'B006', medicineId: 'MED-005', batchNumber: 'V150', quantity: 55, purchaseDate: '2025-03-05', expiryDate: '2028-09-12', supplierId: 'SUP-004', location: 'Rack D-03' },
      { id: 'BAT-007', batchId: 'B007', medicineId: 'MED-006', batchNumber: 'H095', quantity: 18, purchaseDate: '2024-12-15', expiryDate: '2026-11-20', supplierId: 'SUP-003', location: 'Rack E-01' },
      { id: 'BAT-008', batchId: 'B008', medicineId: 'MED-007', batchNumber: 'R320', quantity: 70, purchaseDate: '2025-02-25', expiryDate: '2026-06-14', supplierId: 'SUP-001', location: 'Rack F-02' },
      { id: 'BAT-009', batchId: 'B009', medicineId: 'MED-008', batchNumber: 'T405', quantity: 200, purchaseDate: '2025-04-10', expiryDate: '2027-03-03', supplierId: 'SUP-002', location: 'Rack A-07' },
      { id: 'BAT-010', batchId: 'B010', medicineId: 'MED-009', batchNumber: 'M901', quantity: 60, purchaseDate: '2024-03-20', expiryDate: '2025-10-28', supplierId: 'SUP-005', location: 'Rack G-01' },
      { id: 'BAT-011', batchId: 'B011', medicineId: 'MED-010', batchNumber: 'C990', quantity: 48, purchaseDate: '2024-08-11', expiryDate: '2026-02-18', supplierId: 'SUP-004', location: 'Rack H-02' }
    ],
    transactions: [
      { id: 'TXN-001', date: '2026-10-01T09:00:00', medicineId: 'MED-001', medicineName: 'Paracetamol', batchNumber: 'P101', type: 'STOCK IN', quantity: 150, user: 'admin', supplierId: 'SUP-001' },
      { id: 'TXN-002', date: '2026-10-02T15:30:00', medicineId: 'MED-003', medicineName: 'Cough Syrup', batchNumber: 'S220', type: 'STOCK OUT', quantity: 20, user: 'staff', supplierId: null },
      { id: 'TXN-003', date: '2026-10-03T11:15:00', medicineId: 'MED-005', medicineName: 'Vitamin C', batchNumber: 'V150', type: 'STOCK IN', quantity: 80, user: 'admin', supplierId: 'SUP-004' },
      { id: 'TXN-004', date: '2026-10-04T14:00:00', medicineId: 'MED-006', medicineName: 'Salbutamol', batchNumber: 'H095', type: 'STOCK OUT', quantity: 12, user: 'staff', supplierId: null },
      { id: 'TXN-005', date: '2026-10-05T09:45:00', medicineId: 'MED-004', medicineName: 'Insulin Glargine', batchNumber: 'I340', type: 'STOCK IN', quantity: 30, user: 'admin', supplierId: 'SUP-005' },
      { id: 'TXN-006', date: '2026-10-06T10:20:00', medicineId: 'MED-008', medicineName: 'Ibuprofen', batchNumber: 'T405', type: 'STOCK OUT', quantity: 25, user: 'staff', supplierId: null },
      { id: 'TXN-007', date: '2026-10-07T13:40:00', medicineId: 'MED-009', medicineName: 'Amlodipine', batchNumber: 'M901', type: 'STOCK IN', quantity: 55, user: 'admin', supplierId: 'SUP-005' },
      { id: 'TXN-008', date: '2026-10-08T09:10:00', medicineId: 'MED-010', medicineName: 'Cetirizine', batchNumber: 'C990', type: 'STOCK OUT', quantity: 18, user: 'staff', supplierId: null },
      { id: 'TXN-009', date: '2026-10-08T11:00:00', medicineId: 'MED-002', medicineName: 'Amoxicillin', batchNumber: 'A110', type: 'STOCK IN', quantity: 40, user: 'admin', supplierId: 'SUP-002' },
      { id: 'TXN-010', date: '2026-10-08T12:30:00', medicineId: 'MED-007', medicineName: 'Oral Rehydration Salts', batchNumber: 'R320', type: 'STOCK OUT', quantity: 15, user: 'staff', supplierId: null }
    ],
    auditLog: [
      { id: 'AUD-001', user: 'admin', action: 'Login', timestamp: new Date().toISOString() },
      { id: 'AUD-002', user: 'staff', action: 'Login', timestamp: new Date().toISOString() }
    ]
  };
}

function ensureDemoData() {
  const existing = localStorage.getItem(STORAGE_KEY);
  if (!existing) {
    const initialState = getDefaultState();
    saveState(initialState);
  }
}

function getState() {
  const raw = localStorage.getItem(STORAGE_KEY);
  if (!raw) {
    ensureDemoData();
    return JSON.parse(localStorage.getItem(STORAGE_KEY));
  }
  try {
    return JSON.parse(raw);
  } catch (error) {
    const fresh = getDefaultState();
    saveState(fresh);
    return fresh;
  }
}

function saveState(state) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
  if (typeof fetch === 'function') {
    fetch('api.php?action=sync_state', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ state })
    }).catch(() => {});
  }
}

// Background sync from MySQL database
(function syncWithMySQL() {
  if (typeof fetch === 'function' && window.location.protocol.startsWith('http')) {
    fetch('api.php?action=get_state')
      .then((res) => (res.ok ? res.json() : null))
      .then((data) => {
        if (data && data.success && data.state) {
          localStorage.setItem(STORAGE_KEY, JSON.stringify(data.state));
        }
      })
      .catch(() => {});
  }
})();

function getCurrentUser() {
  const raw = sessionStorage.getItem(AUTH_KEY);
  if (!raw) return null;
  try {
    return JSON.parse(raw);
  } catch (error) {
    return null;
  }
}

function setCurrentUser(user) {
  sessionStorage.setItem(AUTH_KEY, JSON.stringify(user));
}

function clearCurrentUser() {
  sessionStorage.removeItem(AUTH_KEY);
}

function addAuditLog(action, userName, meta = {}) {
  const state = getState();
  const logEntry = {
    id: 'AUD-' + Date.now(),
    user: userName || 'system',
    action,
    timestamp: new Date().toISOString(),
    meta
  };
  state.auditLog.unshift(logEntry);
  saveState(state);
}

function getMedicineById(id) {
  const state = getState();
  return state.medicines.find((item) => item.id === id);
}

function getSupplierById(id) {
  const state = getState();
  return state.suppliers.find((item) => item.id === id);
}

function getBatchById(id) {
  const state = getState();
  return state.batches.find((item) => item.id === id);
}

function getMedicineNameById(medicineId) {
  const medicine = getMedicineById(medicineId);
  return medicine ? medicine.name : 'Unknown Medicine';
}

function getSupplierNameById(supplierId) {
  const supplier = getSupplierById(supplierId);
  return supplier ? supplier.name : 'Unknown Supplier';
}

function formatDate(dateString) {
  if (!dateString) return '—';
  const date = new Date(dateString);
  return isNaN(date.getTime()) ? dateString : date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatDateTime(dateString) {
  if (!dateString) return '—';
  const date = new Date(dateString);
  return isNaN(date.getTime()) ? dateString : date.toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' });
}

function calculateMedicineStock(medicineId) {
  const state = getState();
  return state.batches
    .filter((batch) => batch.medicineId === medicineId)
    .reduce((total, batch) => total + Number(batch.quantity || 0), 0);
}

function calculateExpiryStatus(expiryDate) {
  const state = getState();
  const warningDays = Number(state.settings?.expiryWarningDays || 90);
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const expiry = new Date(expiryDate);
  expiry.setHours(0, 0, 0, 0);

  const diffDays = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));

  if (diffDays < 0) return { label: 'EXPIRED', className: 'danger', daysRemaining: diffDays };
  if (diffDays <= warningDays) return { label: 'EXPIRING SOON', className: 'warning', daysRemaining: diffDays };
  return { label: 'SAFE', className: 'success', daysRemaining: diffDays };
}

function getLowStockMedicines() {
  const state = getState();
  return state.medicines
    .map((medicine) => {
      const currentStock = calculateMedicineStock(medicine.id);
      return {
        ...medicine,
        currentStock,
        isLow: currentStock < Number(medicine.minimumStock || 0)
      };
    })
    .filter((item) => item.isLow);
}

function getExpiryAlerts() {
  const state = getState();
  const pills = [];

  state.batches.forEach((batch) => {
    const expiryStatus = calculateExpiryStatus(batch.expiryDate);
    if (expiryStatus.label !== 'SAFE') {
      pills.push({
        medicineName: getMedicineNameById(batch.medicineId),
        batchNumber: batch.batchNumber,
        expiryDate: batch.expiryDate,
        daysRemaining: expiryStatus.daysRemaining,
        status: expiryStatus.label,
        quantity: batch.quantity,
        location: batch.location,
        medicineId: batch.medicineId
      });
    }
  });

  return pills.sort((a, b) => new Date(a.expiryDate) - new Date(b.expiryDate));
}

function getRecommendedBatch(medicineId) {
  const state = getState();
  const batches = state.batches.filter((batch) => batch.medicineId === medicineId);
  if (!batches.length) return null;
  const earliest = batches.slice().sort((a, b) => new Date(a.expiryDate) - new Date(b.expiryDate))[0];
  return earliest;
}

function exportCsv(filename, rows) {
  const csvContent = [
    rows[0] ? Object.keys(rows[0]).join(',') : '',
    ...rows.map((row) => Object.values(row).map((value) => '"' + String(value).replace(/"/g, '""') + '"').join(','))
  ].join('\n');

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.setAttribute('download', filename);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

function getDashboardCounts() {
  const state = getState();
  const totalMedicines = state.medicines.length;
  const totalAvailableStock = state.batches.reduce((sum, batch) => sum + Number(batch.quantity || 0), 0);
  const lowStockMedicines = getLowStockMedicines().length;
  const expiringSoon = getExpiryAlerts().filter((item) => item.status === 'EXPIRING SOON').length;
  const expired = getExpiryAlerts().filter((item) => item.status === 'EXPIRED').length;
  const suppliers = state.suppliers.length;

  const today = new Date().toISOString().slice(0, 10);
  const todaysStockIn = state.transactions.filter((txn) => txn.type === 'STOCK IN' && txn.date && txn.date.slice(0, 10) === today).reduce((sum, item) => sum + Number(item.quantity || 0), 0);
  const todaysStockOut = state.transactions.filter((txn) => txn.type === 'STOCK OUT' && txn.date && txn.date.slice(0, 10) === today).reduce((sum, item) => sum + Number(item.quantity || 0), 0);

  return {
    totalMedicines,
    totalAvailableStock,
    lowStockMedicines,
    expiringSoon,
    expired,
    suppliers,
    todaysStockIn,
    todaysStockOut
  };
}

function renderBootstrapBadge(status) {
  const map = {
    'SAFE': 'success',
    'EXPIRING SOON': 'warning',
    'EXPIRED': 'danger',
    'LOW STOCK': 'warning',
    'STOCK IN': 'success',
    'STOCK OUT': 'secondary'
  };
  return `badge bg-${map[status] || 'primary'}`;
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function resetDemoData() {
  const fresh = getDefaultState();
  saveState(fresh);
  addAuditLog('Reset demo data', getCurrentUser()?.username || 'system');
  return fresh;
}

function clearAllData() {
  localStorage.removeItem(STORAGE_KEY);
  sessionStorage.removeItem(AUTH_KEY);
}
