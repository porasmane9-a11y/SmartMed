<?php
/**
 * SmartMed – Stock Out Controller
 * Dispenses medicine inventory, enforces stock availability,
 * provides FEFO (First-Expired, First-Out) guidance, records ledger transactions,
 * checks low-stock thresholds, and serves stock-out.html.
 */

require_once __DIR__ . '/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = getRequestData();

    $medId = trim($data['medicineId'] ?? $data['medicine_id'] ?? $data['medicine_record_id'] ?? '');
    $batchIdentifier = trim($data['batchNumber'] ?? $data['batch_number'] ?? $data['batch_record_id'] ?? '');
    $quantity = (int)($data['quantity'] ?? 0);
    $notes = trim($data['notes'] ?? $data['reason'] ?? 'Dispensed');
    $confirmNonFefo = !empty($data['confirmNonFefo']);

    if (empty($medId) || empty($batchIdentifier) || $quantity <= 0) {
        jsonResponse([
            'success' => false,
            'error'   => 'Please provide valid medicine, batch selection, and positive quantity.'
        ], 400);
    }

    try {
        $db = getDB();

        // 1. Verify medicine
        $mStmt = $db->prepare("SELECT * FROM medicines WHERE med_record_id = ? OR medicine_id = ? OR id = ? LIMIT 1");
        $mStmt->execute([$medId, $medId, $medId]);
        $medicine = $mStmt->fetch();

        if (!$medicine) {
            jsonResponse(['success' => false, 'error' => 'Medicine record not found.'], 404);
        }

        $medRecordId = $medicine['med_record_id'];
        $medicineName = $medicine['name'];

        // 2. Locate target batch
        $bStmt = $db->prepare("
            SELECT * FROM batches 
            WHERE medicine_record_id = ? AND (batch_number = ? OR batch_record_id = ? OR id = ?) 
            LIMIT 1
        ");
        $bStmt->execute([$medRecordId, $batchIdentifier, $batchIdentifier, $batchIdentifier]);
        $batch = $bStmt->fetch();

        if (!$batch) {
            jsonResponse(['success' => false, 'error' => 'Selected batch not found for this medicine.'], 404);
        }

        $currentBatchQty = (int)$batch['quantity'];
        if ($currentBatchQty < $quantity) {
            jsonResponse([
                'success' => false,
                'error'   => "Insufficient stock in batch '{$batch['batch_number']}'. Available: {$currentBatchQty}, Requested: {$quantity}."
            ], 400);
        }

        // 3. FEFO (First-Expired, First-Out) Validation
        $fefoStmt = $db->prepare("
            SELECT batch_number, expiry_date, quantity 
            FROM batches 
            WHERE medicine_record_id = ? AND quantity > 0 
            ORDER BY expiry_date ASC 
            LIMIT 1
        ");
        $fefoStmt->execute([$medRecordId]);
        $earliestBatch = $fefoStmt->fetch();

        if ($earliestBatch && $earliestBatch['batch_number'] !== $batch['batch_number'] && !$confirmNonFefo) {
            if ($earliestBatch['expiry_date'] < $batch['expiry_date']) {
                jsonResponse([
                    'success'            => false,
                    'fefo_warning'       => true,
                    'recommended_batch'  => $earliestBatch['batch_number'],
                    'recommended_expiry' => $earliestBatch['expiry_date'],
                    'message'            => "FEFO Alert: Batch '{$earliestBatch['batch_number']}' expires earlier ({$earliestBatch['expiry_date']}). Please prioritize dispensing that batch first."
                ], 409);
            }
        }

        $db->beginTransaction();

        // 4. Decrement batch quantity
        $newBatchQty = $currentBatchQty - $quantity;
        $uStmt = $db->prepare("UPDATE batches SET quantity = ? WHERE batch_record_id = ?");
        $uStmt->execute([$newBatchQty, $batch['batch_record_id']]);

        // 5. Record transaction ledger
        $txnRecordId = 'TXN-' . strtoupper(substr(uniqid(), -6));
        $currentUser = getCurrentUser();
        $username = $currentUser['username'] ?? 'system';

        $tStmt = $db->prepare("
            INSERT INTO transactions 
            (txn_record_id, date, medicine_record_id, medicine_name, batch_number, type, quantity, user, notes) 
            VALUES (?, NOW(), ?, ?, ?, 'STOCK OUT', ?, ?, ?)
        ");
        $tStmt->execute([
            $txnRecordId,
            $medRecordId,
            $medicineName,
            $batch['batch_number'],
            $quantity,
            $username,
            $notes
        ]);

        $db->commit();

        // 6. Audit logging
        logAudit('Stock Out', $username, "Dispensed {$quantity} {$medicine['unit']} from batch '{$batch['batch_number']}' for '{$medicineName}' ({$notes})");

        // 7. Check total remaining stock and detect low-stock
        $totStmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) FROM batches WHERE medicine_record_id = ?");
        $totStmt->execute([$medRecordId]);
        $totalStock = (int)$totStmt->fetchColumn();
        $isLowStock = ($totalStock < (int)$medicine['minimum_stock']);

        jsonResponse([
            'success'          => true,
            'message'          => "Stock Out recorded successfully: -{$quantity} {$medicine['unit']} of {$medicineName}.",
            'batch_number'     => $batch['batch_number'],
            'batch_remaining'  => $newBatchQty,
            'total_stock'      => $totalStock,
            'is_low_stock'     => $isLowStock,
            'minimum_stock'    => (int)$medicine['minimum_stock']
        ]);
    } catch (PDOException $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

// Direct browser GET navigation
if (file_exists(__DIR__ . '/stock-out.html')) {
    include __DIR__ . '/stock-out.html';
} else {
    echo 'Stock Out view not found.';
}
