<?php
/**
 * SmartMed – Delete Medicine Controller
 * Enforces admin authorization, removes medicine and associated batches within a transaction,
 * and records audit log.
 */

require_once __DIR__ . '/config.php';
requireAdmin();

$data = getRequestData();
$id = trim($data['id'] ?? $data['med_record_id'] ?? $_GET['id'] ?? '');

if (empty($id)) {
    jsonResponse(['success' => false, 'error' => 'Medicine ID is required for deletion.'], 400);
}

try {
    $db = getDB();

    $stmt = $db->prepare("SELECT * FROM medicines WHERE med_record_id = ? OR id = ? LIMIT 1");
    $stmt->execute([$id, $id]);
    $medicine = $stmt->fetch();

    if (!$medicine) {
        jsonResponse(['success' => false, 'error' => 'Medicine not found.'], 404);
    }

    $db->beginTransaction();

    // Remove associated batches
    $delBatches = $db->prepare("DELETE FROM batches WHERE medicine_record_id = ?");
    $delBatches->execute([$medicine['med_record_id']]);

    // Remove medicine
    $delMed = $db->prepare("DELETE FROM medicines WHERE id = ?");
    $delMed->execute([$medicine['id']]);

    $db->commit();

    $user = getCurrentUser();
    logAudit('Medicine deleted', $user['username'] ?? 'admin', "Deleted medicine '{$medicine['name']}' ({$medicine['medicine_id']}) and all related batches");

    jsonResponse([
        'success' => true,
        'message' => "Medicine '{$medicine['name']}' and its batch records were deleted successfully."
    ]);
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(['success' => false, 'error' => 'Failed to delete medicine: ' . $e->getMessage()], 500);
}
