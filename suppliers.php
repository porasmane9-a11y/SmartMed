<?php
/**
 * SmartMed – Suppliers Controller & API
 * Manages pharmaceutical suppliers (CRUD), calculates active batch associations,
 * records audit events, and serves suppliers.html.
 */

require_once __DIR__ . '/config.php';
requireLogin();

$db = getDB();
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
          (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
          isset($_GET['api']) || isset($_GET['action']) || $_SERVER['REQUEST_METHOD'] === 'POST';

if ($isAjax) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = getRequestData();
        $action = $data['action'] ?? $_GET['action'] ?? 'add';

        if ($action === 'add') {
            $name = trim($data['name'] ?? '');
            $phone = trim($data['phone'] ?? '');
            $email = trim($data['email'] ?? '');
            $address = trim($data['address'] ?? '');
            $supplierId = trim($data['supplierId'] ?? $data['supplier_id'] ?? '');

            if (empty($name) || empty($phone)) {
                jsonResponse(['success' => false, 'error' => 'Supplier name and phone number are required.'], 400);
            }

            if (empty($supplierId)) {
                $count = (int)$db->query("SELECT COUNT(*) FROM suppliers")->fetchColumn() + 1;
                $supplierId = 'SUP' . str_pad((string)$count, 3, '0', STR_PAD_LEFT);
            }

            $supplierRecordId = 'SUP-' . strtoupper(substr(uniqid(), -6));

            try {
                $stmt = $db->prepare("
                    INSERT INTO suppliers (supplier_record_id, supplier_id, name, phone, email, address) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$supplierRecordId, $supplierId, $name, $phone, $email, $address]);

                $user = getCurrentUser();
                logAudit('Supplier added', $user['username'] ?? 'system', "Added supplier '{$name}' ({$supplierId})");

                jsonResponse([
                    'success' => true,
                    'message' => "Supplier '{$name}' added successfully.",
                    'data'    => [
                        'id'                 => $db->lastInsertId(),
                        'supplier_record_id' => $supplierRecordId,
                        'supplier_id'        => $supplierId,
                        'name'               => $name,
                        'phone'              => $phone,
                        'email'              => $email,
                        'address'            => $address
                    ]
                ]);
            } catch (PDOException $e) {
                jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
            }
        }

        if ($action === 'edit') {
            $id = trim($data['id'] ?? $data['supplier_record_id'] ?? '');
            $name = trim($data['name'] ?? '');
            $phone = trim($data['phone'] ?? '');
            $email = trim($data['email'] ?? '');
            $address = trim($data['address'] ?? '');

            if (empty($id) || empty($name) || empty($phone)) {
                jsonResponse(['success' => false, 'error' => 'Supplier ID, name, and phone are required.'], 400);
            }

            try {
                $stmt = $db->prepare("
                    UPDATE suppliers 
                    SET name = ?, phone = ?, email = ?, address = ? 
                    WHERE supplier_record_id = ? OR id = ?
                ");
                $stmt->execute([$name, $phone, $email, $address, $id, $id]);

                $user = getCurrentUser();
                logAudit('Supplier edited', $user['username'] ?? 'system', "Updated supplier '{$name}'");

                jsonResponse(['success' => true, 'message' => "Supplier '{$name}' updated successfully."]);
            } catch (PDOException $e) {
                jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
            }
        }

        if ($action === 'delete') {
            requireAdmin();
            $id = trim($data['id'] ?? $data['supplier_record_id'] ?? '');

            if (empty($id)) {
                jsonResponse(['success' => false, 'error' => 'Supplier ID is required.'], 400);
            }

            try {
                $stmt = $db->prepare("SELECT name FROM suppliers WHERE supplier_record_id = ? OR id = ? LIMIT 1");
                $stmt->execute([$id, $id]);
                $supplier = $stmt->fetch();

                if (!$supplier) {
                    jsonResponse(['success' => false, 'error' => 'Supplier not found.'], 404);
                }

                $delStmt = $db->prepare("DELETE FROM suppliers WHERE supplier_record_id = ? OR id = ?");
                $delStmt->execute([$id, $id]);

                $user = getCurrentUser();
                logAudit('Supplier deleted', $user['username'] ?? 'admin', "Deleted supplier '{$supplier['name']}'");

                jsonResponse(['success' => true, 'message' => "Supplier '{$supplier['name']}' deleted successfully."]);
            } catch (PDOException $e) {
                jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
            }
        }
    }

    // GET request: list suppliers with active batch count
    try {
        $stmt = $db->query("
            SELECT 
                s.*,
                COUNT(b.id) AS batch_count,
                COALESCE(SUM(b.quantity), 0) AS total_supplied_stock
            FROM suppliers s
            LEFT JOIN batches b ON s.supplier_record_id = b.supplier_record_id
            GROUP BY s.id
            ORDER BY s.name ASC
        ");
        $suppliers = $stmt->fetchAll();
        jsonResponse(['success' => true, 'suppliers' => $suppliers]);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

// Direct browser GET navigation
if (file_exists(__DIR__ . '/suppliers.html')) {
    include __DIR__ . '/suppliers.html';
} else {
    echo 'Suppliers view not found.';
}
