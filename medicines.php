<?php
/**
 * SmartMed – Medicines Controller & API
 * Provides medicine inventory listing with aggregated batch stock and expiry calculations.
 * Also serves medicines.html for direct browser navigation.
 */

require_once __DIR__ . '/config.php';

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
          (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
          isset($_GET['api']) || isset($_GET['action']) || isset($_GET['format']);

if ($isAjax) {
    requireLogin();
    $db = getDB();
    $action = $_GET['action'] ?? 'list';

    if ($action === 'details') {
        $id = trim($_GET['id'] ?? '');
        if (empty($id)) {
            jsonResponse(['success' => false, 'error' => 'Medicine ID is required.'], 400);
        }

        $stmt = $db->prepare("SELECT * FROM medicines WHERE med_record_id = ? OR medicine_id = ? OR id = ? LIMIT 1");
        $stmt->execute([$id, $id, $id]);
        $medicine = $stmt->fetch();

        if (!$medicine) {
            jsonResponse(['success' => false, 'error' => 'Medicine not found.'], 404);
        }

        $bStmt = $db->prepare("SELECT b.*, s.name as supplier_name FROM batches b LEFT JOIN suppliers s ON b.supplier_record_id = s.supplier_record_id WHERE b.medicine_record_id = ? ORDER BY b.expiry_date ASC");
        $bStmt->execute([$medicine['med_record_id']]);
        $batches = $bStmt->fetchAll();

        foreach ($batches as &$batch) {
            $batch['expiry_status'] = calculateExpiryStatus($batch['expiry_date']);
        }

        jsonResponse([
            'success'  => true,
            'medicine' => $medicine,
            'batches'  => $batches
        ]);
    }

    // Default action: List all medicines with live aggregated stock and earliest expiry
    $search = trim($_GET['search'] ?? '');
    $category = trim($_GET['category'] ?? '');
    $manufacturer = trim($_GET['manufacturer'] ?? '');

    $sql = "
        SELECT 
            m.*,
            COALESCE(SUM(b.quantity), 0) AS current_stock,
            MIN(CASE WHEN b.quantity > 0 THEN b.expiry_date ELSE NULL END) AS earliest_expiry
        FROM medicines m
        LEFT JOIN batches b ON m.med_record_id = b.medicine_record_id
        WHERE 1=1
    ";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (m.name LIKE ? OR m.medicine_id LIKE ? OR m.description LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    if (!empty($category)) {
        $sql .= " AND m.category = ?";
        $params[] = $category;
    }
    if (!empty($manufacturer)) {
        $sql .= " AND m.manufacturer = ?";
        $params[] = $manufacturer;
    }

    $sql .= " GROUP BY m.id ORDER BY m.name ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $medicines = $stmt->fetchAll();

    foreach ($medicines as &$med) {
        $med['current_stock'] = (int)$med['current_stock'];
        $med['minimum_stock'] = (int)$med['minimum_stock'];
        $med['is_low'] = ($med['current_stock'] < $med['minimum_stock']);
        if ($med['earliest_expiry']) {
            $med['expiry_info'] = calculateExpiryStatus($med['earliest_expiry']);
        } else {
            $med['expiry_info'] = ['label' => 'NO STOCK', 'className' => 'secondary', 'daysRemaining' => null];
        }
    }

    jsonResponse(['success' => true, 'medicines' => $medicines]);
}

// Direct browser access
requireLogin();
if (file_exists(__DIR__ . '/medicines.html')) {
    include __DIR__ . '/medicines.html';
} else {
    echo 'Medicines view not found.';
}
