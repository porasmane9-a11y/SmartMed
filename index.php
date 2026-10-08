<?php
/**
 * SmartMed – Application Entry Point
 * Redirects authenticated users to dashboard and guests to login.
 */

require_once __DIR__ . '/config.php';

if (isLoggedIn()) {
    header('Location: dashboard.html');
} else {
    header('Location: login.php');
}
exit;
