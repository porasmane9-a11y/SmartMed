<?php
/**
 * SmartMed – Configuration & Authentication Helpers
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

define('APP_NAME', 'SmartMed');
define('APP_VERSION', '1.0.0');

/**
 * Return JSON response and exit cleanly
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($data);
    exit;
}

/**
 * Read request payload (supports JSON body and form-urlencoded $_POST)
 */
function getRequestData(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($contentType, 'application/json')) {
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $data = json_decode($raw, true);
            if (is_array($data)) {
                return $data;
            }
        }
    }
    return $_POST;
}

/**
 * Clean & sanitize user input
 */
function sanitizeInput($data): string {
    if ($data === null) return '';
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if session user exists
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['smartmed_user']);
}

/**
 * Return current session user details
 */
function getCurrentUser(): ?array {
    return $_SESSION['smartmed_user'] ?? null;
}

/**
 * Set current session user
 */
function setCurrentUser(array $user): void {
    $_SESSION['smartmed_user'] = [
        'id'       => $user['user_id'] ?? $user['id'] ?? '',
        'name'     => $user['name'] ?? '',
        'username' => $user['username'] ?? '',
        'role'     => $user['role'] ?? 'staff'
    ];
}

/**
 * Enforce authenticated user
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                  (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
        if ($isAjax) {
            jsonResponse(['success' => false, 'error' => 'Authentication required. Please log in.'], 401);
        } else {
            header('Location: login.php');
            exit;
        }
    }
}

/**
 * Enforce administrator role
 */
function requireAdmin(): void {
    requireLogin();
    $user = getCurrentUser();
    if (($user['role'] ?? '') !== 'admin') {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                  (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
        if ($isAjax) {
            jsonResponse(['success' => false, 'error' => 'Permission denied. Administrator privileges required.'], 403);
        } else {
            header('Location: dashboard.html');
            exit;
        }
    }
}

/**
 * Write to audit log in MySQL
 */
function logAudit(string $action, ?string $user = null, ?string $details = null): void {
    try {
        $db = getDB();
        $username = $user ?? ($_SESSION['smartmed_user']['username'] ?? 'system');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $db->prepare("INSERT INTO audit_logs (user, action, timestamp, ip_address, details) VALUES (?, ?, NOW(), ?, ?)");
        $stmt->execute([$username, $action, $ip, $details]);
    } catch (Exception $e) {
        // Suppress audit log write exception to not break business flow
    }
}

/**
 * Calculate Expiry Status relative to current date and warning days
 */
function calculateExpiryStatus(string $expiryDate, int $warningDays = 90): array {
    $today = new DateTime('today');
    $exp = new DateTime($expiryDate);
    $diff = $today->diff($exp);
    $diffDays = (int) $diff->format('%r%a');

    if ($diffDays < 0) {
        return ['label' => 'EXPIRED', 'className' => 'danger', 'daysRemaining' => $diffDays];
    } elseif ($diffDays <= $warningDays) {
        return ['label' => 'EXPIRING SOON', 'className' => 'warning', 'daysRemaining' => $diffDays];
    } else {
        return ['label' => 'SAFE', 'className' => 'success', 'daysRemaining' => $diffDays];
    }
}
