<?php
/**
 * SmartMed – Add Medicine Controller
 * Validates medicine metadata, verifies unique identifiers,
 * inserts record into MySQL using PDO prepared statement, and records audit log.
 */

require_once __DIR__ . '/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: medicines.php');
    exit;
}

$data = getRequestData();
$medicineId = trim($data['medicineId'] ?? $data['medicine_id'] ?? '');
$name = trim($data['name'] ?? '');
$category = trim($data['category'] ?? '');
$manufacturer = trim($data['manufacturer'] ?? '');
$unit = trim($data['unit'] ?? '');
$minimumStock = isset($data['minimumStock']) ? (int)$data['minimumStock'] : (isset($data['minimum_stock']) ? (int)$data['minimum_stock'] : 10);
$description = trim($data['description'] ?? '');

// Validation
if (empty($medicineId) || empty($name) || empty($category) || empty($manufacturer) || empty($unit)) {
    jsonResponse(['success' => false, 'error' => 'Please fill in all required fields (Medicine ID, Name, Category, Manufacturer, Unit).'], 400);
}

if ($minimumStock < 0) {
    jsonResponse(['success' => false, 'error' => 'Minimum stock cannot be negative.'], 400);
}

try {
    $db = getDB();

    // Check for duplicate medicine ID
    $checkStmt = $db->prepare("SELECT id FROM medicines WHERE medicine_id = ? LIMIT 1");
    $checkStmt->execute([$medicineId]);
    if ($checkStmt->fetch()) {
        jsonResponse(['success' => false, 'error' => "Medicine ID '{$medicineId}' already exists in inventory."], 409);
    }

    $medRecordId = 'MED-' . strtoupper(substr(uniqid(), -6));

    $stmt = $db->prepare("
        INSERT INTO medicines 
        (med_record_id, medicine_id, name, category, manufacturer, unit, minimum_stock, description, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
    ");

    $stmt->execute([
        $medRecordId,
        $medicineId,
        $name,
        $category,
        $manufacturer,
        $unit,
        $minimumStock,
        $description
    ]);

    $newId = $db->lastInsertId();

    $user = getCurrentUser();
    logAudit('Medicine added', $user['username'] ?? 'system', "Added medicine '{$name}' (ID: {$medicineId})");

    jsonResponse([
        'success' => true,
        'message' => "Medicine '{$name}' added successfully.",
        'data'    => [
            'id'            => $newId,
            'med_record_id' => $medRecordId,
            'medicine_id'   => $medicineId,
            'name'          => $name,
            'category'      => $category,
            'manufacturer'  => $manufacturer,
            'unit'          => $unit,
            'minimum_stock' => $minimumStock,
            'description'   => $description,
            'status'        => 'active'
        ]
    ]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
}
