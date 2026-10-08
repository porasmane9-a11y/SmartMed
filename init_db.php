<?php
/**
 * SmartMed – 1-Click Database Initializer & Seed Tool
 * Creates smartmed_db database and seeds complete demo dataset.
 */

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'smartmed_db');

$message = '';
$status = 'info';

function runDatabaseInit(): array {
    try {
        // 1. Connect to MySQL server
        $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // 2. Create Database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `" . DB_NAME . "`;");

        // 3. Drop existing tables
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS `audit_logs`;");
        $pdo->exec("DROP TABLE IF EXISTS `transactions`;");
        $pdo->exec("DROP TABLE IF EXISTS `batches`;");
        $pdo->exec("DROP TABLE IF EXISTS `suppliers`;");
        $pdo->exec("DROP TABLE IF EXISTS `medicines`;");
        $pdo->exec("DROP TABLE IF EXISTS `users`;");
        $pdo->exec("DROP TABLE IF EXISTS `settings`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        // 4. Create Tables
        $pdo->exec("
            CREATE TABLE `users` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `user_id` VARCHAR(50) NOT NULL UNIQUE,
              `name` VARCHAR(100) NOT NULL,
              `username` VARCHAR(50) NOT NULL UNIQUE,
              `password` VARCHAR(255) NOT NULL,
              `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
              `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE `medicines` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `med_record_id` VARCHAR(50) NOT NULL UNIQUE,
              `medicine_id` VARCHAR(50) NOT NULL UNIQUE,
              `name` VARCHAR(150) NOT NULL,
              `category` VARCHAR(50) NOT NULL,
              `manufacturer` VARCHAR(100) NOT NULL,
              `unit` VARCHAR(50) NOT NULL,
              `minimum_stock` INT NOT NULL DEFAULT 10,
              `description` TEXT NULL,
              `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              INDEX `idx_med_name` (`name`),
              INDEX `idx_med_cat` (`category`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE `suppliers` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `supplier_record_id` VARCHAR(50) NOT NULL UNIQUE,
              `supplier_id` VARCHAR(50) NOT NULL UNIQUE,
              `name` VARCHAR(150) NOT NULL,
              `phone` VARCHAR(50) NOT NULL,
              `email` VARCHAR(100) NULL,
              `address` TEXT NULL,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE `batches` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `batch_record_id` VARCHAR(50) NOT NULL UNIQUE,
              `batch_id` VARCHAR(50) NOT NULL,
              `medicine_record_id` VARCHAR(50) NOT NULL,
              `batch_number` VARCHAR(50) NOT NULL,
              `quantity` INT NOT NULL DEFAULT 0,
              `purchase_date` DATE NOT NULL,
              `expiry_date` DATE NOT NULL,
              `supplier_record_id` VARCHAR(50) NULL,
              `location` VARCHAR(100) NOT NULL,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              INDEX `idx_batch_exp` (`expiry_date`),
              INDEX `idx_batch_med` (`medicine_record_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE `transactions` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `txn_record_id` VARCHAR(50) NOT NULL UNIQUE,
              `date` DATETIME DEFAULT CURRENT_TIMESTAMP,
              `medicine_record_id` VARCHAR(50) NOT NULL,
              `medicine_name` VARCHAR(150) NOT NULL,
              `batch_number` VARCHAR(50) NOT NULL,
              `type` ENUM('STOCK IN', 'STOCK OUT') NOT NULL,
              `quantity` INT NOT NULL,
              `user` VARCHAR(50) NOT NULL,
              `supplier_record_id` VARCHAR(50) NULL,
              `notes` TEXT NULL,
              INDEX `idx_txn_date` (`date`),
              INDEX `idx_txn_type` (`type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE `audit_logs` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `user` VARCHAR(50) NOT NULL,
              `action` VARCHAR(100) NOT NULL,
              `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
              `ip_address` VARCHAR(50) NULL,
              `details` TEXT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE `settings` (
              `setting_key` VARCHAR(50) PRIMARY KEY,
              `setting_value` TEXT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 5. Seed Users with securely hashed passwords
        $adminPass = password_hash('admin123', PASSWORD_BCRYPT);
        $staffPass = password_hash('staff123', PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("INSERT INTO `users` (`user_id`, `name`, `username`, `password`, `role`, `status`) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['USR-ADMIN', 'Admin User', 'admin', $adminPass, 'admin', 'active']);
        $stmt->execute(['USR-STAFF', 'Staff User', 'staff', $staffPass, 'staff', 'active']);

        // 6. Seed Suppliers
        $suppliers = [
            ['SUP-001', 'SUP001', 'PrimeCare Pharma', '9876543210', 'sales@primecare.com', 'MG Road, Bengaluru'],
            ['SUP-002', 'SUP002', 'MedLine Labs', '9988776655', 'orders@medline.com', 'Koramangala, Bengaluru'],
            ['SUP-003', 'SUP003', 'Apex Healthcare', '9123456780', 'support@apexhealthcare.com', 'Whitefield, Bengaluru'],
            ['SUP-004', 'SUP004', 'CarePlus Distribution', '9012345678', 'care@careplusdist.com', 'HSR Layout, Bengaluru'],
            ['SUP-005', 'SUP005', 'HealthBridge Supply', '9807654321', 'hello@healthbridge.com', 'Indiranagar, Bengaluru']
        ];
        $sStmt = $pdo->prepare("INSERT INTO `suppliers` (`supplier_record_id`, `supplier_id`, `name`, `phone`, `email`, `address`) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($suppliers as $s) {
            $sStmt->execute($s);
        }

        // 7. Seed Medicines
        $medicines = [
            ['MED-001', 'MED001', 'Paracetamol', 'Tablet', 'ABC Pharma', 'Tablets', 50, 'Pain and fever relief', 'active'],
            ['MED-002', 'MED002', 'Amoxicillin', 'Capsule', 'Wellcare Labs', 'Capsules', 40, 'Antibiotic capsules', 'active'],
            ['MED-003', 'MED003', 'Cough Syrup', 'Syrup', 'LifeMed', 'Bottles', 25, 'Cough suppressant', 'active'],
            ['MED-004', 'MED004', 'Insulin Glargine', 'Injection', 'NovoCare', 'Vials', 30, 'Diabetes management injection', 'active'],
            ['MED-005', 'MED005', 'Vitamin C', 'Tablet', 'Sunrise Health', 'Tablets', 60, 'Immunity support tablets', 'active'],
            ['MED-006', 'MED006', 'Salbutamol', 'Inhaler', 'BreathWell', 'Inhalers', 20, 'Bronchodilator inhaler', 'active'],
            ['MED-007', 'MED007', 'Oral Rehydration Salts', 'Powder', 'HydraCare', 'Packets', 35, 'Electrolyte replacement', 'active'],
            ['MED-008', 'MED008', 'Ibuprofen', 'Tablet', 'Rapid Relief', 'Tablets', 45, 'Anti-inflammatory tablets', 'active'],
            ['MED-009', 'MED009', 'Amlodipine', 'Tablet', 'HeartCare', 'Tablets', 30, 'Blood pressure medicine', 'active'],
            ['MED-010', 'MED010', 'Cetirizine', 'Tablet', 'AllergyCare', 'Tablets', 55, 'Allergy relief tablets', 'active']
        ];
        $mStmt = $pdo->prepare("INSERT INTO `medicines` (`med_record_id`, `medicine_id`, `name`, `category`, `manufacturer`, `unit`, `minimum_stock`, `description`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($medicines as $m) {
            $mStmt->execute($m);
        }

        // 8. Seed Batches (with dynamic dates so demo calculations always demonstrate safe/expiring soon/expired)
        $today = date('Y-m-d');
        $batches = [
            ['BAT-001', 'B001', 'MED-001', 'P101', 180, '2024-01-15', date('Y-m-d', strtotime('+45 days')), 'SUP-001', 'Rack A-03'], // Expiring soon
            ['BAT-002', 'B002', 'MED-001', 'P102', 220, '2025-02-10', date('Y-m-d', strtotime('+500 days')), 'SUP-002', 'Rack A-04'], // Safe
            ['BAT-003', 'B003', 'MED-002', 'A110', 95, '2024-06-05', date('Y-m-d', strtotime('+80 days')), 'SUP-002', 'Rack B-01'], // Expiring soon
            ['BAT-004', 'B004', 'MED-003', 'S220', 80, '2025-01-12', date('Y-m-d', strtotime('+350 days')), 'SUP-003', 'Rack C-02'], // Safe
            ['BAT-005', 'B005', 'MED-004', 'I340', 25, '2024-11-01', date('Y-m-d', strtotime('-10 days')), 'SUP-005', 'Cooler 1'], // Expired!
            ['BAT-006', 'B006', 'MED-005', 'V150', 55, '2025-03-05', date('Y-m-d', strtotime('+400 days')), 'SUP-004', 'Rack D-03'], // Safe
            ['BAT-007', 'B007', 'MED-006', 'H095', 18, '2024-12-15', date('Y-m-d', strtotime('+60 days')), 'SUP-003', 'Rack E-01'], // Expiring soon (Low stock!)
            ['BAT-008', 'B008', 'MED-007', 'R320', 70, '2025-02-25', date('Y-m-d', strtotime('+220 days')), 'SUP-001', 'Rack F-02'], // Safe
            ['BAT-009', 'B009', 'MED-008', 'T405', 200, '2025-04-10', date('Y-m-d', strtotime('+300 days')), 'SUP-002', 'Rack A-07'], // Safe
            ['BAT-010', 'B010', 'MED-009', 'M901', 60, '2024-03-20', date('Y-m-d', strtotime('-30 days')), 'SUP-005', 'Rack G-01'], // Expired!
            ['BAT-011', 'B011', 'MED-010', 'C990', 48, '2024-08-11', date('Y-m-d', strtotime('+30 days')), 'SUP-004', 'Rack H-02']  // Expiring soon (Low stock!)
        ];
        $bStmt = $pdo->prepare("INSERT INTO `batches` (`batch_record_id`, `batch_id`, `medicine_record_id`, `batch_number`, `quantity`, `purchase_date`, `expiry_date`, `supplier_record_id`, `location`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($batches as $b) {
            $bStmt->execute($b);
        }

        // 9. Seed Transactions
        $txns = [
            ['TXN-001', date('Y-m-d H:i:s', strtotime('-7 days')), 'MED-001', 'Paracetamol', 'P101', 'STOCK IN', 150, 'admin', 'SUP-001', 'Shipment arrived'],
            ['TXN-002', date('Y-m-d H:i:s', strtotime('-6 days')), 'MED-003', 'Cough Syrup', 'S220', 'STOCK OUT', 20, 'staff', null, 'Dispensed to OPD clinic'],
            ['TXN-003', date('Y-m-d H:i:s', strtotime('-5 days')), 'MED-005', 'Vitamin C', 'V150', 'STOCK IN', 80, 'admin', 'SUP-004', 'Restock from CarePlus'],
            ['TXN-004', date('Y-m-d H:i:s', strtotime('-4 days')), 'MED-006', 'Salbutamol', 'H095', 'STOCK OUT', 12, 'staff', null, 'Issued for emergency care'],
            ['TXN-005', date('Y-m-d H:i:s', strtotime('-3 days')), 'MED-004', 'Insulin Glargine', 'I340', 'STOCK IN', 30, 'admin', 'SUP-005', 'Cool storage delivery'],
            ['TXN-006', date('Y-m-d H:i:s', strtotime('-2 days')), 'MED-008', 'Ibuprofen', 'T405', 'STOCK OUT', 25, 'staff', null, 'General ward dispatch'],
            ['TXN-007', date('Y-m-d H:i:s', strtotime('-1 days')), 'MED-009', 'Amlodipine', 'M901', 'STOCK IN', 55, 'admin', 'SUP-005', 'Cardio batch delivery'],
            ['TXN-008', date('Y-m-d H:i:s', strtotime('-4 hours')), 'MED-010', 'Cetirizine', 'C990', 'STOCK OUT', 18, 'staff', null, 'Allergy camp issue'],
            ['TXN-009', date('Y-m-d H:i:s', strtotime('-2 hours')), 'MED-002', 'Amoxicillin', 'A110', 'STOCK IN', 40, 'admin', 'SUP-002', 'Antibiotics refill'],
            ['TXN-010', date('Y-m-d H:i:s', strtotime('-30 minutes')), 'MED-007', 'Oral Rehydration Salts', 'R320', 'STOCK OUT', 15, 'staff', null, 'Summer hydration kit issue']
        ];
        $tStmt = $pdo->prepare("INSERT INTO `transactions` (`txn_record_id`, `date`, `medicine_record_id`, `medicine_name`, `batch_number`, `type`, `quantity`, `user`, `supplier_record_id`, `notes`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($txns as $t) {
            $tStmt->execute($t);
        }

        // 10. Seed Settings
        $settings = [
            ['expiryWarningDays', '90'],
            ['theme', 'light'],
            ['lowStockAlert', 'enabled'],
            ['clinicName', 'SmartMed Community Health Center']
        ];
        $setStmt = $pdo->prepare("INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES (?, ?)");
        foreach ($settings as $st) {
            $setStmt->execute($st);
        }

        // 11. Seed Audit Logs
        $aStmt = $pdo->prepare("INSERT INTO `audit_logs` (`user`, `action`, `timestamp`, `ip_address`, `details`) VALUES (?, ?, NOW(), ?, ?)");
        $aStmt->execute(['admin', 'Database Initialized', '127.0.0.1', 'SmartMed database created and seeded']);

        return ['success' => true, 'message' => "Database 'smartmed_db' initialized successfully with all tables and demo records!"];
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Error: " . $e->getMessage()];
    }
}

// Handle execution
$isCli = (php_sapi_name() === 'cli');
$isPost = (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST');

if ($isCli || $isPost || isset($_GET['run'])) {
    $result = runDatabaseInit();
    $message = $result['message'];
    $status = $result['success'] ? 'success' : 'danger';
    if ($isCli) {
        echo ($result['success'] ? "[SUCCESS] " : "[ERROR] ") . $message . PHP_EOL;
        exit($result['success'] ? 0 : 1);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartMed – Database Initializer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .init-card {
            max-width: 550px;
            width: 100%;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>

<div class="init-card">
    <div class="bg-primary text-white p-4 text-center" style="background: linear-gradient(135deg, #0284c7, #0d9488) !important;">
        <i class="bi bi-database-fill-gear fs-1 mb-2 d-inline-block"></i>
        <h4 class="fw-bold mb-1">SmartMed Database Initializer</h4>
        <p class="mb-0 small opacity-75">1-Click MySQL Database & Demo Data Installer</p>
    </div>

    <div class="p-4">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $status ?> d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi <?= $status === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> fs-5"></i>
                <div><?= htmlspecialchars($message) ?></div>
            </div>
            <?php if ($status === 'success'): ?>
                <div class="text-center mb-4">
                    <a href="login.php" class="btn btn-success px-4 fw-semibold">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Proceed to Login Page
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <p class="text-secondary small">
            This tool initializes the <code>smartmed_db</code> MySQL database, sets up all relational tables with foreign keys and indexes, and seeds pre-configured demo medicines, batches, suppliers, transactions, and user accounts.
        </p>

        <div class="border rounded p-3 bg-light mb-4 small">
            <h6 class="fw-bold mb-2">Pre-Configured Accounts:</h6>
            <div class="d-flex justify-content-between mb-1">
                <span><strong>Admin:</strong> <code>admin</code></span>
                <span>Password: <code>admin123</code></span>
            </div>
            <div class="d-flex justify-content-between">
                <span><strong>Staff:</strong> <code>staff</code></span>
                <span>Password: <code>staff123</code></span>
            </div>
        </div>

        <form method="POST">
            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                <i class="bi bi-play-circle me-1"></i> Initialize / Reset Database Now
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="index.html" class="text-decoration-none small text-muted">&larr; Back to Application</a>
        </div>
    </div>
</div>

</body>
</html>
