<?php
/**
 * SmartMed – Database Connection (PDO)
 * Connects to MySQL using PDO with prepared statement support
 */

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'smartmed_db');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            $isJson = (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
                      (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));
            if ($isJson || php_sapi_name() === 'cli') {
                if (!headers_sent() && php_sapi_name() !== 'cli') {
                    header('Content-Type: application/json');
                }
                echo json_encode([
                    'success' => false,
                    'error'   => 'Database connection failed: ' . $e->getMessage() . '. Please run init_db.php first.'
                ]);
            } else {
                echo '
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <title>Database Error – SmartMed</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
                </head>
                <body class="bg-light d-flex align-items-center min-vh-100">
                    <div class="container" style="max-width: 550px;">
                        <div class="card shadow border-danger">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">SmartMed – Database Connection Error</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-danger fw-bold">' . htmlspecialchars($e->getMessage()) . '</p>
                                <hr>
                                <h6>Quick Setup:</h6>
                                <p class="small text-muted">The database <code>' . DB_NAME . '</code> might not have been created yet or MySQL is stopped in XAMPP.</p>
                                <a href="init_db.php" class="btn btn-primary w-100">Initialize Database Now (1-Click)</a>
                            </div>
                        </div>
                    </div>
                </body>
                </html>';
            }
            exit;
        }
    }
    return $pdo;
}
