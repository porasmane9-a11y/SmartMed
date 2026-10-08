<?php
/**
 * SmartMed – Reports Controller & Data Export API
 * Generates comprehensive inventory, low-stock, expiry, and stock movement reports,
 * supports direct CSV file export, and serves reports.html.
 */

require_once __DIR__ . '/config.php';
requireLogin();

$db = getDB();
$reportType = $_GET['type'] ?? 'inventory';
$exportCsv = isset($_GET['export']) && $_GET['export'] === 'csv';
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
          (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
          isset($_GET['api']) || isset($_GET['format']);

if ($exportCsv || $isAjax) {
    try {
        // Read warning days threshold from settings
        $wStmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'expiryWarningDays' LIMIT 1");
        $warningDays = (int)($wStmt->fetchColumn() ?: 90);

        if ($reportType === 'low_stock') {
            $stmt = $db->query("
                SELECT 
                    m.med_record_id,
                    m.medicine_id,
                    m.name,
                    m.category,
                    m.manufacturer,
                    m.unit,
                    m.minimum_stock,
                    COALESCE(SUM(b.quantity), 0) AS current_stock
                FROM medicines m
                LEFT JOIN batches b ON m.med_record_id = b.medicine_record_id
                GROUP BY m.id, m.med_record_id, m.medicine_id, m.name, m.category, m.manufacturer, m.unit, m.minimum_stock
                HAVING current_stock < m.minimum_stock
                ORDER BY (m.minimum_stock - current_stock) DESC
            ");
            $rows = $stmt->fetchAll();
            $filename = 'smartmed_low_stock_report_' . date('Y-m-d') . '.csv';
            $headers = ['Medicine ID', 'Name', 'Category', 'Manufacturer', 'Unit', 'Min Stock', 'Current Stock', 'Deficit'];

            $dataRows = [];
            foreach ($rows as $r) {
                $dataRows[] = [
                    $r['medicine_id'],
                    $r['name'],
                    $r['category'],
                    $r['manufacturer'],
                    $r['unit'],
                    $r['minimum_stock'],
                    $r['current_stock'],
                    $r['minimum_stock'] - $r['current_stock']
                ];
            }
        } elseif ($reportType === 'expiry') {
            $stmt = $db->query("
                SELECT 
                    m.name as medicine_name,
                    m.category,
                    b.batch_number,
                    b.quantity,
                    b.expiry_date,
                    b.location,
                    s.name as supplier_name
                FROM batches b
                JOIN medicines m ON b.medicine_record_id = m.med_record_id
                LEFT JOIN suppliers s ON b.supplier_record_id = s.supplier_record_id
                ORDER BY b.expiry_date ASC
            ");
            $batches = $stmt->fetchAll();

            $filename = 'smartmed_expiry_report_' . date('Y-m-d') . '.csv';
            $headers = ['Medicine', 'Category', 'Batch Number', 'Quantity', 'Expiry Date', 'Status', 'Days Remaining', 'Location', 'Supplier'];

            $dataRows = [];
            $expiryItems = [];
            foreach ($batches as $b) {
                $status = calculateExpiryStatus($b['expiry_date'], $warningDays);
                $b['status'] = $status['label'];
                $b['days_remaining'] = $status['daysRemaining'];
                $expiryItems[] = $b;

                $dataRows[] = [
                    $b['medicine_name'],
                    $b['category'],
                    $b['batch_number'],
                    $b['quantity'],
                    $b['expiry_date'],
                    $status['label'],
                    $status['daysRemaining'],
                    $b['location'],
                    $b['supplier_name'] ?? '—'
                ];
            }
            $rows = $expiryItems;
        } elseif ($reportType === 'movement') {
            $stmt = $db->query("
                SELECT 
                    DATE(date) as txn_date,
                    type,
                    COUNT(*) as txn_count,
                    SUM(quantity) as total_quantity
                FROM transactions
                GROUP BY DATE(date), type
                ORDER BY txn_date DESC
                LIMIT 60
            ");
            $rows = $stmt->fetchAll();

            $filename = 'smartmed_movement_report_' . date('Y-m-d') . '.csv';
            $headers = ['Date', 'Type', 'Transaction Count', 'Total Quantity'];
            $dataRows = [];
            foreach ($rows as $r) {
                $dataRows[] = [$r['txn_date'], $r['type'], $r['txn_count'], $r['total_quantity']];
            }
        } else {
            // General Inventory Report
            $stmt = $db->query("
                SELECT 
                    m.medicine_id,
                    m.name,
                    m.category,
                    m.manufacturer,
                    m.unit,
                    m.minimum_stock,
                    COALESCE(SUM(b.quantity), 0) AS current_stock,
                    COUNT(b.id) AS batch_count,
                    MIN(CASE WHEN b.quantity > 0 THEN b.expiry_date ELSE NULL END) AS earliest_expiry
                FROM medicines m
                LEFT JOIN batches b ON m.med_record_id = b.medicine_record_id
                GROUP BY m.id
                ORDER BY m.name ASC
            ");
            $rows = $stmt->fetchAll();

            $filename = 'smartmed_inventory_report_' . date('Y-m-d') . '.csv';
            $headers = ['Medicine ID', 'Name', 'Category', 'Manufacturer', 'Unit', 'Min Stock', 'Current Stock', 'Stock Status', 'Earliest Expiry'];
            $dataRows = [];
            foreach ($rows as $r) {
                $status = ((int)$r['current_stock'] < (int)$r['minimum_stock']) ? 'LOW STOCK' : 'ADEQUATE';
                $dataRows[] = [
                    $r['medicine_id'],
                    $r['name'],
                    $r['category'],
                    $r['manufacturer'],
                    $r['unit'],
                    $r['minimum_stock'],
                    $r['current_stock'],
                    $status,
                    $r['earliest_expiry'] ?? 'No active batch'
                ];
            }
        }

        if ($exportCsv) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);
            foreach ($dataRows as $line) {
                fputcsv($output, $line);
            }
            fclose($output);
            exit;
        }

        jsonResponse([
            'success'     => true,
            'report_type' => $reportType,
            'generated_at'=> date('Y-m-d H:i:s'),
            'data'        => $rows
        ]);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

// Direct browser GET navigation
if (file_exists(__DIR__ . '/reports.html')) {
    include __DIR__ . '/reports.html';
} else {
    echo 'Reports view not found.';
}
