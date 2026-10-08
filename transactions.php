<?php
/**
 * SmartMed – Transactions Controller & API
 * Provides historical audit ledger of all Stock In and Stock Out movements,
 * supports filtering by transaction type, date range, and medicine.
 * Also serves transactions.html.
 */

require_once __DIR__ . '/config.php';
requireLogin();

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
          (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
          isset($_GET['api']) || isset($_GET['action']) || isset($_GET['format']);

if ($isAjax) {
    try {
        $db = getDB();

        $type = trim($_GET['type'] ?? '');
        $medId = trim($_GET['medicine_id'] ?? $_GET['medicine'] ?? '');
        $startDate = trim($_GET['start_date'] ?? '');
        $endDate = trim($_GET['end_date'] ?? '');
        $limit = isset($_GET['limit']) ? max(1, min(500, (int)$_GET['limit'])) : 100;

        $sql = "
            SELECT 
                t.*,
                s.name as supplier_name 
            FROM transactions t 
            LEFT JOIN suppliers s ON t.supplier_record_id = s.supplier_record_id 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($type) && $type !== 'ALL') {
            $sql .= " AND t.type = ?";
            $params[] = strtoupper($type);
        }

        if (!empty($medId)) {
            $sql .= " AND (t.medicine_record_id = ? OR t.medicine_name LIKE ?)";
            $params[] = $medId;
            $params[] = "%$medId%";
        }

        if (!empty($startDate)) {
            $sql .= " AND DATE(t.date) >= ?";
            $params[] = $startDate;
        }

        if (!empty($endDate)) {
            $sql .= " AND DATE(t.date) <= ?";
            $params[] = $endDate;
        }

        $sql .= " ORDER BY t.date DESC, t.id DESC LIMIT " . (int)$limit;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();

        jsonResponse([
            'success'      => true,
            'count'        => count($transactions),
            'transactions' => $transactions
        ]);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

// Direct browser GET navigation
if (file_exists(__DIR__ . '/transactions.html')) {
    include __DIR__ . '/transactions.html';
} else {
    echo 'Transactions view not found.';
}
