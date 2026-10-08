<?php
/**
 * SmartMed – Edit Medicine Controller
 * Validates updated metadata, updates medicine in MySQL using PDO prepared statement,
 * and records audit log.
 */

require_once __DIR__ . '/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: medicines.php');
    exit;
}

$data = getRequestData();
$id = trim($data['id'] ?? $data['med_record_id'] ?? '');
$medicineId = trim($data['medicineId'] ?? $data['medicine_id'] ?? '');
$name = trim($data['name'] ?? '');
$category = trim($data['category'] ?? '');
$manufacturer = trim($data['manufacturer'] ?? '');
$unit = trim($data['unit'] ?? '');
$minimumStock = isset($data['minimumStock']) ? (int)$data['minimumStock'] : (isset($data['minimum_stock']) ? (int)$data['minimum_stock'] : 10);
$description = trim($data['description'] ?? '');
$status = trim($data['status'] ?? 'active');

if (empty($id) || empty($medicineId) || empty($name) || empty($category) || empty($unit)) {
    jsonResponse(['success' => false, 'error' => 'Please provide all required fields.'], 400);
}

if ($minimumStock < 0) {
    jsonResponse(['success' => false, 'error' => 'Minimum stock cannot be negative.'], 400);
}

try {
    $db = getDB();

    // Check if target medicine exists
    $targetStmt = $db->prepare("SELECT * FROM medicines WHERE med_record_id = ? OR id = ? LIMIT 1");
    $targetStmt->execute([$id, $id]);
    $existing = $targetStmt->fetch();

    if (!$existing) {
        jsonResponse(['success' => false, 'error' => 'Medicine record not found.'], 404);
    }

    // Check for duplicate medicine ID in other records
    $dupStmt = $db->prepare("SELECT id FROM medicines WHERE medicine_id = ? AND med_record_id != ? AND id != ? LIMIT 1");
    $dupStmt->execute([$medicineId, $id, $id]);
    if ($dupStmt->fetch()) {
        jsonResponse(['success' => false, 'error' => "Medicine ID '{$medicineId}' is already assigned to another medicine."], 409);
    }

    $stmt = $db->prepare("
        UPDATE medicines 
        SET medicine_id = ?, name = ?, category = ?, manufacturer = ?, unit = ?, minimum_stock = ?, description = ?, status = ? 
        WHERE med_record_id = ? OR id = ?
    ");
    $stmt->execute([
        $medicineId,
        $name,
        $category,
        $manufacturer,
        $unit,
        $minimumStock,
        $description,
        $status,
        $id,
        $id
    ]);

    $user = getCurrentUser();
    logAudit('Medicine edited', $user['username'] ?? 'system', "Updated medicine '{$name}' (ID: {$medicineId})");

    jsonResponse([
        'success' => true,
        'message' => "Medicine '{$name}' updated successfully.",
        'data'    => [
            'id'            => $existing['id'],
            'med_record_id' => $existing['med_record_id'],
            'medicine_id'   => $medicineId,
            'name'          => $name,
            'category'      => $category,
            'manufacturer'  => $manufacturer,
            'unit'          => $unit,
            'minimum_stock' => $minimumStock,
            'description'   => $description,
            'status'        => $status
        ]
    ]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
}
