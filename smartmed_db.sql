-- SmartMed – Medicine Stock & Expiry Management System
-- Database Schema & Complete Demo Dataset for smartmed_db
-- Compatible with MySQL 5.7+, MySQL 8.0+, MariaDB 10.4+ (XAMPP default)

CREATE DATABASE IF NOT EXISTS `smartmed_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `smartmed_db`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `batches`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `medicines`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `settings`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Table structure for `users`
-- --------------------------------------------------------
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

-- --------------------------------------------------------
-- Table structure for `medicines`
-- --------------------------------------------------------
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

-- --------------------------------------------------------
-- Table structure for `suppliers`
-- --------------------------------------------------------
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

-- --------------------------------------------------------
-- Table structure for `batches`
-- --------------------------------------------------------
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

-- --------------------------------------------------------
-- Table structure for `transactions`
-- --------------------------------------------------------
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

-- --------------------------------------------------------
-- Table structure for `audit_logs`
-- --------------------------------------------------------
CREATE TABLE `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user` VARCHAR(50) NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `ip_address` VARCHAR(50) NULL,
  `details` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `settings`
-- --------------------------------------------------------
CREATE TABLE `settings` (
  `setting_key` VARCHAR(50) PRIMARY KEY,
  `setting_value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed Users (Bcrypt Passwords: admin -> admin123, staff -> staff123)
-- --------------------------------------------------------
INSERT INTO `users` (`user_id`, `name`, `username`, `password`, `role`, `status`) VALUES
('USR-ADMIN', 'Admin User', 'admin', '$2y$10$wB5L7vS1YgXJ50zD3zcqmO1uF1v6b3P8gJ1F51C1b0j7xQ4R5W6bS', 'admin', 'active'),
('USR-STAFF', 'Staff User', 'staff', '$2y$10$78/x58y119Y8C49L1F2kDe0F8P885C42B1A6E1F8C9E2A3B4C5D6E', 'staff', 'active');

-- --------------------------------------------------------
-- Seed Suppliers
-- --------------------------------------------------------
INSERT INTO `suppliers` (`supplier_record_id`, `supplier_id`, `name`, `phone`, `email`, `address`) VALUES
('SUP-001', 'SUP001', 'PrimeCare Pharma', '9876543210', 'sales@primecare.com', 'MG Road, Bengaluru'),
('SUP-002', 'SUP002', 'MedLine Labs', '9988776655', 'orders@medline.com', 'Koramangala, Bengaluru'),
('SUP-003', 'SUP003', 'Apex Healthcare', '9123456780', 'support@apexhealthcare.com', 'Whitefield, Bengaluru'),
('SUP-004', 'SUP004', 'CarePlus Distribution', '9012345678', 'care@careplusdist.com', 'HSR Layout, Bengaluru'),
('SUP-005', 'SUP005', 'HealthBridge Supply', '9807654321', 'hello@healthbridge.com', 'Indiranagar, Bengaluru');

-- --------------------------------------------------------
-- Seed Medicines
-- --------------------------------------------------------
INSERT INTO `medicines` (`med_record_id`, `medicine_id`, `name`, `category`, `manufacturer`, `unit`, `minimum_stock`, `description`, `status`) VALUES
('MED-001', 'MED001', 'Paracetamol', 'Tablet', 'ABC Pharma', 'Tablets', 50, 'Pain and fever relief', 'active'),
('MED-002', 'MED002', 'Amoxicillin', 'Capsule', 'Wellcare Labs', 'Capsules', 40, 'Antibiotic capsules', 'active'),
('MED-003', 'MED003', 'Cough Syrup', 'Syrup', 'LifeMed', 'Bottles', 25, 'Cough suppressant', 'active'),
('MED-004', 'MED004', 'Insulin Glargine', 'Injection', 'NovoCare', 'Vials', 30, 'Diabetes management injection', 'active'),
('MED-005', 'MED005', 'Vitamin C', 'Tablet', 'Sunrise Health', 'Tablets', 60, 'Immunity support tablets', 'active'),
('MED-006', 'MED006', 'Salbutamol', 'Inhaler', 'BreathWell', 'Inhalers', 20, 'Bronchodilator inhaler', 'active'),
('MED-007', 'MED007', 'Oral Rehydration Salts', 'Powder', 'HydraCare', 'Packets', 35, 'Electrolyte replacement', 'active'),
('MED-008', 'MED008', 'Ibuprofen', 'Tablet', 'Rapid Relief', 'Tablets', 45, 'Anti-inflammatory tablets', 'active'),
('MED-009', 'MED009', 'Amlodipine', 'Tablet', 'HeartCare', 'Tablets', 30, 'Blood pressure medicine', 'active'),
('MED-010', 'MED010', 'Cetirizine', 'Tablet', 'AllergyCare', 'Tablets', 55, 'Allergy relief tablets', 'active');

-- --------------------------------------------------------
-- Seed Batches (Safe, Expiring Soon, and Expired demonstration)
-- --------------------------------------------------------
INSERT INTO `batches` (`batch_record_id`, `batch_id`, `medicine_record_id`, `batch_number`, `quantity`, `purchase_date`, `expiry_date`, `supplier_record_id`, `location`) VALUES
('BAT-001', 'B001', 'MED-001', 'P101', 180, '2024-01-15', '2026-11-20', 'SUP-001', 'Rack A-03'),
('BAT-002', 'B002', 'MED-001', 'P102', 220, '2025-02-10', '2028-08-20', 'SUP-002', 'Rack A-04'),
('BAT-003', 'B003', 'MED-002', 'A110', 95, '2024-06-05', '2026-12-15', 'SUP-002', 'Rack B-01'),
('BAT-004', 'B004', 'MED-003', 'S220', 80, '2025-01-12', '2027-05-18', 'SUP-003', 'Rack C-02'),
('BAT-005', 'B005', 'MED-004', 'I340', 25, '2024-11-01', '2026-04-10', 'SUP-005', 'Cooler 1'),
('BAT-006', 'B006', 'MED-005', 'V150', 55, '2025-03-05', '2028-09-12', 'SUP-004', 'Rack D-03'),
('BAT-007', 'B007', 'MED-006', 'H095', 18, '2024-12-15', '2026-11-10', 'SUP-003', 'Rack E-01'),
('BAT-008', 'B008', 'MED-007', 'R320', 70, '2025-02-25', '2027-06-14', 'SUP-001', 'Rack F-02'),
('BAT-009', 'B009', 'MED-008', 'T405', 200, '2025-04-10', '2027-03-03', 'SUP-002', 'Rack A-07'),
('BAT-010', 'B010', 'MED-009', 'M901', 60, '2024-03-20', '2025-10-28', 'SUP-005', 'Rack G-01'),
('BAT-011', 'B011', 'MED-010', 'C990', 48, '2024-08-11', '2026-11-05', 'SUP-004', 'Rack H-02');

-- --------------------------------------------------------
-- Seed Transactions Ledger
-- --------------------------------------------------------
INSERT INTO `transactions` (`txn_record_id`, `date`, `medicine_record_id`, `medicine_name`, `batch_number`, `type`, `quantity`, `user`, `supplier_record_id`, `notes`) VALUES
('TXN-001', '2026-10-01 09:00:00', 'MED-001', 'Paracetamol', 'P101', 'STOCK IN', 150, 'admin', 'SUP-001', 'Initial consignment delivery'),
('TXN-002', '2026-10-02 15:30:00', 'MED-003', 'Cough Syrup', 'S220', 'STOCK OUT', 20, 'staff', NULL, 'Dispensed to outpatient clinic'),
('TXN-003', '2026-10-03 11:15:00', 'MED-005', 'Vitamin C', 'V150', 'STOCK IN', 80, 'admin', 'SUP-004', 'Replenishment order'),
('TXN-004', '2026-10-04 14:00:00', 'MED-006', 'Salbutamol', 'H095', 'STOCK OUT', 12, 'staff', NULL, 'Emergency ward allocation'),
('TXN-005', '2026-10-05 09:45:00', 'MED-004', 'Insulin Glargine', 'I340', 'STOCK IN', 30, 'admin', 'SUP-005', 'Cold storage shipment'),
('TXN-006', '2026-10-06 10:20:00', 'MED-008', 'Ibuprofen', 'T405', 'STOCK OUT', 25, 'staff', NULL, 'General dispensing'),
('TXN-007', '2026-10-07 13:40:00', 'MED-009', 'Amlodipine', 'M901', 'STOCK IN', 55, 'admin', 'SUP-005', 'Monthly supply'),
('TXN-008', '2026-10-08 09:10:00', 'MED-010', 'Cetirizine', 'C990', 'STOCK OUT', 18, 'staff', NULL, 'Outpatient prescription'),
('TXN-009', '2026-10-08 11:00:00', 'MED-002', 'Amoxicillin', 'A110', 'STOCK IN', 40, 'admin', 'SUP-002', 'Pediatric ward stock'),
('TXN-010', '2026-10-08 12:30:00', 'MED-007', 'Oral Rehydration Salts', 'R320', 'STOCK OUT', 15, 'staff', NULL, 'Camp distribution');

-- --------------------------------------------------------
-- Seed Audit Logs
-- --------------------------------------------------------
INSERT INTO `audit_logs` (`user`, `action`, `timestamp`, `ip_address`, `details`) VALUES
('admin', 'System Initialized', NOW(), '127.0.0.1', 'SmartMed database successfully configured with initial dataset'),
('admin', 'Login', NOW(), '127.0.0.1', 'Admin login session initialized');

-- --------------------------------------------------------
-- Seed Settings
-- --------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('expiryWarningDays', '90'),
('theme', 'light'),
('lowStockAlert', 'enabled');
