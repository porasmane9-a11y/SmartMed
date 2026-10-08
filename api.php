<?php
/**
 * SmartMed – Unified Backend API Gateway
 * Bridges frontend JavaScript application with MySQL database smartmed_db.
 * Provides real-time state synchronization, dashboard statistics,
 * and handles inventory updates via PDO prepared statements.
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$db = getDB();

try {
    switch ($action) {
        case 'get_state':
            // 1. Settings
            $sRows = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
            $settings = [
                'expiryWarningDays' => 90,
                'theme'             => 'light',
                'lowStockAlert'     => 'enabled'
            ];
            foreach ($sRows as $row) {
                $settings[$row['setting_key']] = is_numeric($row['setting_value']) ? (int)$row['setting_value'] : $row['setting_value'];
            }

            // 2. Users (Safe representation without password hashes)
            $users = $db->query("
                SELECT 
                    user_id AS id, 
                    user_id AS userId, 
                    name, 
                    username, 
                    role, 
                    status 
                FROM users 
                ORDER BY id ASC
            ")->fetchAll();

            // 3. Suppliers
            $suppliers = $db->query("
                SELECT 
                    supplier_record_id AS id, 
                    supplier_id AS supplierId, 
                    name, 
                    phone, 
                    email, 
                    address 
                FROM suppliers 
                ORDER BY name ASC
            ")->fetchAll();

            // 4. Medicines
            $medicines = $db->query("
                SELECT 
                    med_record_id AS id, 
                    medicine_id AS medicineId, 
                    name, 
                    category, 
                    manufacturer, 
                    unit, 
                    minimum_stock AS minimumStock, 
                    description, 
                    status 
                FROM medicines 
                ORDER BY name ASC
            ")->fetchAll();

            // 5. Batches
            $batches = $db->query("
                SELECT 
                    batch_record_id AS id, 
                    batch_id AS batchId, 
                    medicine_record_id AS medicineId, 
                    batch_number AS batchNumber, 
                    quantity, 
                    purchase_date AS purchaseDate, 
                    expiry_date AS expiryDate, 
                    supplier_record_id AS supplierId, 
                    location 
                FROM batches 
                ORDER BY expiry_date ASC
            ")->fetchAll();

            // 6. Transactions
            $transactions = $db->query("
                SELECT 
                    txn_record_id AS id, 
                    DATE_FORMAT(date, '%Y-%m-%dT%H:%i:%s') AS date, 
                    medicine_record_id AS medicineId, 
                    medicine_name AS medicineName, 
                    batch_number AS batchNumber, 
                    type, 
                    quantity, 
                    user, 
                    supplier_record_id AS supplierId, 
                    notes 
                FROM transactions 
                ORDER BY date DESC, id DESC 
                LIMIT 100
            ")->fetchAll();

            // 7. Audit Log
            $auditLog = $db->query("
                SELECT 
                    CONCAT('AUD-', id) AS id, 
                    user, 
                    action, 
                    DATE_FORMAT(timestamp, '%Y-%m-%dT%H:%i:%s.000Z') AS timestamp, 
                    details AS meta 
                FROM audit_logs 
                ORDER BY timestamp DESC, id DESC 
                LIMIT 50
            ")->fetchAll();

            jsonResponse([
                'success' => true,
                'state'   => [
                    'settings'     => $settings,
                    'users'        => $users,
                    'suppliers'    => $suppliers,
                    'medicines'    => $medicines,
                    'batches'      => $batches,
                    'transactions' => $transactions,
                    'auditLog'     => $auditLog
                ]
            ]);
            break;

        case 'stats':
            $warningDays = (int)($db->query("SELECT setting_value FROM settings WHERE setting_key = 'expiryWarningDays'")->fetchColumn() ?: 90);

            $totalMedicines = (int)$db->query("SELECT COUNT(*) FROM medicines")->fetchColumn();
            $totalStock = (int)$db->query("SELECT COALESCE(SUM(quantity), 0) FROM batches")->fetchColumn();
            $lowStockMedicines = (int)$db->query("
                SELECT COUNT(*) FROM (
                    SELECT m.id 
                    FROM medicines m 
                    LEFT JOIN batches b ON m.med_record_id = b.medicine_record_id 
                    GROUP BY m.id, m.minimum_stock 
                    HAVING COALESCE(SUM(b.quantity), 0) < m.minimum_stock
                ) AS low_items
            ")->fetchColumn();

            $expiringSoon = (int)$db->query("
                SELECT COUNT(*) FROM batches 
                WHERE expiry_date >= CURDATE() 
                  AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL {$warningDays} DAY)
                  AND quantity > 0
            ")->fetchColumn();

            $expired = (int)$db->query("
                SELECT COUNT(*) FROM batches 
                WHERE expiry_date < CURDATE() 
                  AND quantity > 0
            ")->fetchColumn();

            $suppliersCount = (int)$db->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
            $todayIn = (int)$db->query("SELECT COALESCE(SUM(quantity), 0) FROM transactions WHERE type = 'STOCK IN' AND DATE(date) = CURDATE()")->fetchColumn();
            $todayOut = (int)$db->query("SELECT COALESCE(SUM(quantity), 0) FROM transactions WHERE type = 'STOCK OUT' AND DATE(date) = CURDATE()")->fetchColumn();

            jsonResponse([
                'success' => true,
                'stats'   => [
                    'totalMedicines'      => $totalMedicines,
                    'totalAvailableStock' => $totalStock,
                    'lowStockMedicines'   => $lowStockMedicines,
                    'expiringSoon'        => $expiringSoon,
                    'expired'             => $expired,
                    'suppliers'           => $suppliersCount,
                    'todaysStockIn'       => $todayIn,
                    'todaysStockOut'      => $todayOut
                ]
            ]);
            break;

        case 'sync_state':
            requireLogin();
            $payload = getRequestData();
            $state = $payload['state'] ?? $payload;

            if (empty($state) || !is_array($state)) {
                jsonResponse(['success' => false, 'error' => 'Invalid state payload.'], 400);
            }

            $db->beginTransaction();

            // Sync settings
            if (!empty($state['settings']) && is_array($state['settings'])) {
                $stStmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                foreach ($state['settings'] as $k => $v) {
                    $stStmt->execute([$k, (string)$v]);
                }
            }

            // Sync medicines
            if (!empty($state['medicines']) && is_array($state['medicines'])) {
                $mStmt = $db->prepare("
                    INSERT INTO medicines (med_record_id, medicine_id, name, category, manufacturer, unit, minimum_stock, description, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        name = VALUES(name),
                        category = VALUES(category),
                        manufacturer = VALUES(manufacturer),
                        unit = VALUES(unit),
                        minimum_stock = VALUES(minimum_stock),
                        description = VALUES(description),
                        status = VALUES(status)
                ");
                foreach ($state['medicines'] as $m) {
                    $mStmt->execute([
                        $m['id'],
                        $m['medicineId'],
                        $m['name'],
                        $m['category'],
                        $m['manufacturer'] ?? '',
                        $m['unit'],
                        (int)($m['minimumStock'] ?? 10),
                        $m['description'] ?? '',
                        $m['status'] ?? 'active'
                    ]);
                }
            }

            // Sync suppliers
            if (!empty($state['suppliers']) && is_array($state['suppliers'])) {
                $sStmt = $db->prepare("
                    INSERT INTO suppliers (supplier_record_id, supplier_id, name, phone, email, address)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        name = VALUES(name),
                        phone = VALUES(phone),
                        email = VALUES(email),
                        address = VALUES(address)
                ");
                foreach ($state['suppliers'] as $s) {
                    $sStmt->execute([
                        $s['id'],
                        $s['supplierId'] ?? $s['id'],
                        $s['name'],
                        $s['phone'] ?? '',
                        $s['email'] ?? '',
                        $s['address'] ?? ''
                    ]);
                }
            }

            // Sync batches
            if (!empty($state['batches']) && is_array($state['batches'])) {
                $bStmt = $db->prepare("
                    INSERT INTO batches (batch_record_id, batch_id, medicine_record_id, batch_number, quantity, purchase_date, expiry_date, supplier_record_id, location)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        quantity = VALUES(quantity),
                        purchase_date = VALUES(purchase_date),
                        expiry_date = VALUES(expiry_date),
                        supplier_record_id = VALUES(supplier_record_id),
                        location = VALUES(location)
                ");
                foreach ($state['batches'] as $b) {
                    $bStmt->execute([
                        $b['id'],
                        $b['batchId'] ?? $b['id'],
                        $b['medicineId'],
                        $b['batchNumber'],
                        (int)$b['quantity'],
                        $b['purchaseDate'] ?? date('Y-m-d'),
                        $b['expiryDate'],
                        !empty($b['supplierId']) ? $b['supplierId'] : null,
                        $b['location'] ?? 'Main Storage'
                    ]);
                }
            }

            $db->commit();
            jsonResponse(['success' => true, 'message' => 'State synchronized with MySQL successfully.']);
            break;

        default:
            jsonResponse(['success' => false, 'error' => "Action '{$action}' not recognized."], 400);
            break;
    }
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
}
