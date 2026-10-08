<?php
/**
 * SmartMed – User Login Controller
 * Authenticates user credentials against MySQL with bcrypt,
 * establishes secure session, and logs audit entries.
 */

require_once __DIR__ . '/config.php';

// Handle POST authentication request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = getRequestData();
    $username = trim($data['username'] ?? '');
    $password = trim($data['password'] ?? '');

    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
              (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
              (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));

    if (empty($username) || empty($password)) {
        if ($isAjax) {
            jsonResponse(['success' => false, 'error' => 'Please provide both username and password.'], 400);
        } else {
            $error = 'Please provide both username and password.';
        }
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    if ($isAjax) {
                        jsonResponse(['success' => false, 'error' => 'This account has been deactivated. Contact your administrator.'], 403);
                    } else {
                        $error = 'This account has been deactivated.';
                    }
                } else {
                    // Session regeneration for security against session fixation
                    session_regenerate_id(true);
                    setCurrentUser($user);
                    logAudit('Login', $user['username'], 'User successfully authenticated');

                    if ($isAjax) {
                        jsonResponse([
                            'success'  => true,
                            'message'  => 'Login successful',
                            'user'     => getCurrentUser(),
                            'redirect' => 'dashboard.html'
                        ]);
                    } else {
                        header('Location: dashboard.html');
                        exit;
                    }
                }
            } else {
                if ($isAjax) {
                    jsonResponse(['success' => false, 'error' => 'Invalid username or password.'], 401);
                } else {
                    $error = 'Invalid username or password.';
                }
            }
        } catch (PDOException $e) {
            if ($isAjax) {
                jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
            } else {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.html');
    exit;
}

// If browser GET request, serve login.html directly
if (file_exists(__DIR__ . '/login.html')) {
    include __DIR__ . '/login.html';
} else {
    echo 'Login file not found.';
}
