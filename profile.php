<?php
/**
 * SmartMed – User Profile Controller & API
 * Manages authenticated user details, provides secure password hashing with bcrypt,
 * records audit entries, and serves profile.html.
 */

require_once __DIR__ . '/config.php';
requireLogin();

$currentUser = getCurrentUser();
$db = getDB();

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
          (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
          isset($_GET['api']) || isset($_GET['action']) || $_SERVER['REQUEST_METHOD'] === 'POST';

if ($isAjax) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = getRequestData();
        $action = $data['action'] ?? 'update_profile';

        if ($action === 'change_password') {
            $currentPassword = trim($data['currentPassword'] ?? $data['current_password'] ?? '');
            $newPassword = trim($data['newPassword'] ?? $data['new_password'] ?? '');
            $confirmPassword = trim($data['confirmPassword'] ?? $data['confirm_password'] ?? '');

            if (empty($currentPassword) || empty($newPassword)) {
                jsonResponse(['success' => false, 'error' => 'Please provide current and new passwords.'], 400);
            }

            if (strlen($newPassword) < 6) {
                jsonResponse(['success' => false, 'error' => 'New password must be at least 6 characters long.'], 400);
            }

            if (!empty($confirmPassword) && $newPassword !== $confirmPassword) {
                jsonResponse(['success' => false, 'error' => 'New passwords do not match.'], 400);
            }

            try {
                $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR user_id = ? LIMIT 1");
                $stmt->execute([$currentUser['username'], $currentUser['id']]);
                $user = $stmt->fetch();

                if (!$user || !password_verify($currentPassword, $user['password'])) {
                    jsonResponse(['success' => false, 'error' => 'Current password is incorrect.'], 401);
                }

                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $upStmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upStmt->execute([$newHash, $user['id']]);

                logAudit('Password changed', $user['username'], 'User successfully changed password');

                jsonResponse(['success' => true, 'message' => 'Password changed successfully.']);
            } catch (PDOException $e) {
                jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
            }
        }

        // Action: update_profile
        $name = trim($data['name'] ?? '');
        $username = trim($data['username'] ?? '');

        if (empty($name) || empty($username)) {
            jsonResponse(['success' => false, 'error' => 'Name and username are required.'], 400);
        }

        try {
            // Check username conflict
            $cStmt = $db->prepare("SELECT id FROM users WHERE username = ? AND user_id != ? LIMIT 1");
            $cStmt->execute([$username, $currentUser['id']]);
            if ($cStmt->fetch()) {
                jsonResponse(['success' => false, 'error' => "Username '{$username}' is already in use."], 409);
            }

            $upStmt = $db->prepare("UPDATE users SET name = ?, username = ? WHERE user_id = ?");
            $upStmt->execute([$name, $username, $currentUser['id']]);

            // Refresh session data
            $_SESSION['smartmed_user']['name'] = $name;
            $_SESSION['smartmed_user']['username'] = $username;

            logAudit('Profile updated', $username, 'User updated profile details');

            jsonResponse([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'user'    => $_SESSION['smartmed_user']
            ]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    // GET: user profile details
    try {
        $stmt = $db->prepare("SELECT user_id, name, username, role, status, created_at FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$currentUser['id']]);
        $user = $stmt->fetch();

        if (!$user) {
            jsonResponse(['success' => false, 'error' => 'User not found.'], 404);
        }

        jsonResponse(['success' => true, 'user' => $user]);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

// Direct browser GET navigation
if (file_exists(__DIR__ . '/profile.html')) {
    include __DIR__ . '/profile.html';
} else {
    echo 'Profile view not found.';
}
