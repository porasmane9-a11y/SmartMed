# SmartMed – Medicine Stock & Expiry Management System

SmartMed is a complete, full-stack medicine stock and expiry management web application designed for clinics, charitable hospitals, and community healthcare pharmacies.

## Technology Stack

- **Frontend:** HTML5, CSS3, JavaScript (ES6+), Bootstrap 5, Bootstrap Icons, Chart.js
- **Backend:** PHP 8+ (PDO, Prepared Statements, Session Authentication, Bcrypt)
- **Database:** MySQL / MariaDB (`smartmed_db`)
- **Environment:** XAMPP / Apache / PHP CLI

---

## Architecture & Features

1. **User Authentication & RBAC:**
   - Session-based authentication with `session_regenerate_id`
   - Secure bcrypt password hashing (`password_hash` & `password_verify`)
   - Role-Based Access Control (`admin` vs `staff`)
   - Secure audit trail logging (`audit_logs` table)
2. **Medicine Inventory Management (CRUD):**
   - Full CRUD support with `medicines.php`, `add_medicine.php`, `edit_medicine.php`, `delete_medicine.php`
   - Real-time stock summation across batches
3. **Stock In:**
   - Atomic batch quantity increment or new batch creation (`stock_in.php`)
   - Automatic `STOCK IN` transaction recording in ledger
4. **Stock Out & FEFO Guidance:**
   - Stock deduction with real-time availability check (`stock_out.php`)
   - First-Expired, First-Out (FEFO) recommendation and alert
   - Automatic `STOCK OUT` transaction recording in ledger
5. **Intelligent Alerts:**
   - Dynamic low-stock detection based on `minimum_stock` threshold
   - FEFO expiry categorization: `EXPIRED`, `EXPIRING SOON` (<= threshold days), and `SAFE`
6. **Suppliers & Transactions:**
   - Supplier CRUD with active batch counting (`suppliers.php`)
   - Complete movements ledger with multi-field search and filters (`transactions.php`)
7. **Reports & Exports:**
   - Inventory, Low-stock, Expiry, and Movement reports (`reports.php`)
   - 1-Click CSV export with standard HTTP download headers
8. **User Profile & System Settings:**
   - Password reset and profile management (`profile.php`)
   - System configurations stored in MySQL (`settings.php`)

---

## How to Run Using XAMPP

### Step 1: Start Apache and MySQL
1. Open the **XAMPP Control Panel**.
2. Click **Start** for **Apache**.
3. Click **Start** for **MySQL**.

### Step 2: Deploy to XAMPP htdocs
Copy or move the `smartmed` folder into your XAMPP web directory:
`C:\xampp\htdocs\smartmed`

### Step 3: Initialize MySQL Database (1-Click)
Open your browser and navigate to:
**`http://localhost/smartmed/init_db.php`**

Click the **"Initialize / Reset Database Now"** button, or run from terminal:
```powershell
php "C:\xampp\htdocs\smartmed\init_db.php"
```
*(Alternatively, you can import `smartmed_db.sql` directly into phpMyAdmin at `http://localhost/phpmyadmin`)*

### Step 4: Launch Application
Open your web browser and visit:
**`http://localhost/smartmed/`** or **`http://localhost/smartmed/login.php`**

---

## Demo Credentials

| Role | Username | Password | Access Level |
|---|---|---|---|
| **Administrator** | `admin` | `admin123` | Full access (User management, delete operations, system settings) |
| **Healthcare Staff** | `staff` | `staff123` | Operational access (Stock In, Stock Out, Inventory view, Reports) |

---

## Backend Files Overview

- [`config.php`](config.php) – Session management, RBAC enforcement, helper utilities, and audit logging.
- [`db.php`](db.php) – PDO database connection singleton for `smartmed_db`.
- [`init_db.php`](init_db.php) – 1-Click database initializer and demo data seeder.
- [`smartmed_db.sql`](smartmed_db.sql) – Full database schema and seed data SQL script.
- [`login.php`](login.php) – User authentication with bcrypt validation and session creation.
- [`logout.php`](logout.php) – Session termination, cookie removal, and redirect.
- [`medicines.php`](medicines.php) – Medicine inventory overview, aggregated batch stock, and expiry status.
- [`add_medicine.php`](add_medicine.php) – Medicine addition with validation and duplicate checks.
- [`edit_medicine.php`](edit_medicine.php) – Medicine update controller.
- [`delete_medicine.php`](delete_medicine.php) – Admin-only medicine and batch deletion controller.
- [`stock_in.php`](stock_in.php) – Stock receipt, batch management, and ledger entry.
- [`stock_out.php`](stock_out.php) – Stock dispensation, FEFO alert, and ledger entry.
- [`suppliers.php`](suppliers.php) – Supplier management (CRUD) and active batch statistics.
- [`transactions.php`](transactions.php) – Complete inventory transaction history and filtering.
- [`reports.php`](reports.php) – Inventory, low-stock, and expiry reports with direct CSV export.
- [`profile.php`](profile.php) – User profile updates and secure password changes.
- [`settings.php`](settings.php) – System configuration management in MySQL.
- [`api.php`](api.php) – Real-time MySQL state and dashboard statistics gateway.
- [`index.php`](index.php) – Application router.
