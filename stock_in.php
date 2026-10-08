<?php
/**
 * SmartMed – Stock In Controller
 * Records incoming medicine inventory into batches, updates quantities,
 * generates a ledger transaction, and logs audit events.
 * Also serves stock-in.html on browser GET navigation.
 */

require_once __DIR__ . '/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = getRequestData();

    $medId = trim($data['medicineId'] ?? $data['medicine_id'] ?? $data['medicine_record_id'] ?? '');
    $batchNumber = trim($data['batchNumber'] ?? $data['batch_number'] ?? '');
    $quantity = (int)($data['quantity'] ?? 0);
    $purchaseDate = trim($data['purchaseDate'] ?? $data['purchase_date'] ?? date('Y-m-d'));
    $expiryDate = trim($data['expiryDate'] ?? $data['expiry_date'] ?? '');
    $supplierRecordId = trim($data['supplierId'] ?? $data['supplier_record_id'] ?? '');
    $location = trim($data['location'] ?? 'Main Storage');
    $notes = trim($data['notes'] ?? '');

    if (empty($medId) || empty($batchNumber) || $quantity <= 0 || empty($expiryDate)) {
        jsonResponse([
            'success' => false,
            'error'   => 'Please provide valid medicine, batch number, positive quantity, and expiry date.'
        ], 400);
    }

    try {
        $db = getDB();

        // 1. Verify medicine exists
        $mStmt = $db->prepare("SELECT * FROM medicines WHERE med_record_id = ? OR medicine_id = ? OR id = ? LIMIT 1");
        $mStmt->execute([$medId, $medId, $medId]);
        $medicine = $mStmt->fetch();

        if (!$medicine) {
            jsonResponse(['success' => false, 'error' => 'Selected medicine not found in database.'], 404);
        }

        $medRecordId = $medicine['med_record_id'];
        $medicineName = $medicine['name'];

        $db->beginTransaction();

        // 2. Check if this batch already exists
        $bStmt = $db->prepare("SELECT * FROM batches WHERE medicine_record_id = ? AND batch_number = ? LIMIT 1");
        $bStmt->execute([$medRecordId, $batchNumber]);
        $existingBatch = $bStmt->fetch();

        if ($existingBatch) {
            $batchRecordId = $existingBatch['batch_record_id'];
            $newQuantity = (int)$existingBatch['quantity'] + $quantity;

            $uStmt = $db->prepare("
                UPDATE batches 
                SET quantity = ?, expiry_date = ?, location = ?, supplier_record_id = COALESCE(NULLIF(?, ''), supplier_record_id)
                WHERE batch_record_id = ?
            ");
            $uStmt->execute([$newQuantity, $expiryDate, $location, $supplierRecordId, $batchRecordId]);
        } else {
            $batchRecordId = 'BAT-' . strtoupper(substr(uniqid(), -6));
            $batchCode = 'B' . rand(100, 999);

            $iStmt = $db->prepare("
                INSERT INTO batches 
                (batch_record_id, batch_id, medicine_record_id, batch_number, quantity, purchase_date, expiry_date, supplier_record_id, location) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $iStmt->execute([
                $batchRecordId,
                $batchCode,
                $medRecordId,
                $batchNumber,
                $quantity,
                $purchaseDate,
                $expiryDate,
                $supplierRecordId ?: null,
                $location
            ]);
        }

        // 3. Record transaction ledger
        $txnRecordId = 'TXN-' . strtoupper(substr(uniqid(), -6));
        $currentUser = getCurrentUser();
        $username = $currentUser['username'] ?? 'system';

        $tStmt = $db->prepare("
            INSERT INTO transactions 
            (txn_record_id, date, medicine_record_id, medicine_name, batch_number, type, quantity, user, supplier_record_id, notes) 
            VALUES (?, NOW(), ?, ?, ?, 'STOCK IN', ?, ?, ?, ?)
        ");
        $tStmt->execute([
            $txnRecordId,
            $medRecordId,
            $medicineName,
            $batchNumber,
            $quantity,
            $username,
            $supplierRecordId ?: null,
            $notes
        ]);

        $db->commit();

        // 4. Audit Log
        logAudit('Stock In', $username, "Added {$quantity} {$medicine['unit']} to batch '{$batchNumber}' for '{$medicineName}'");

        // 5. Calculate updated total stock
        $totStmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) FROM batches WHERE medicine_record_id = ?");
        $totStmt->execute([$medRecordId]);
        $totalStock = (int)$totStmt->fetchColumn();

        jsonResponse([
            'success'        => true,
            'message'        => "Stock In recorded successfully: +{$quantity} {$medicine['unit']} of {$medicineName}.",
            'batch_number'   => $batchNumber,
            'added_quantity' => $quantity,
            'total_stock'    => $totalStock,
            'medicine_name'  => $medicineName
        ]);
    } catch (PDOException $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

// Direct browser GET navigation
if (file_exists(__DIR__ . '/stock-in.html')) {
    include __DIR__ . '/stock-in.html';
} else {
    echo 'Stock In view not found.';
}
