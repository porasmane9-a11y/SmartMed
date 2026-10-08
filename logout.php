<?php
/**
 * SmartMed – Logout Controller
 * Terminates session, removes authentication cookies, logs audit entry, and redirects.
 */

require_once __DIR__ . '/config.php';

$currentUser = getCurrentUser();
if ($currentUser) {
    logAudit('Logout', $currentUser['username'], 'User logged out');
}

// Clear all session variables
$_SESSION = [];

// Delete the session cookie if present
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
          (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

if ($isAjax) {
    jsonResponse(['success' => true, 'message' => 'Logged out successfully']);
} else {
    header('Location: login.html');
    exit;
}
