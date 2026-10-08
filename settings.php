<?php
/**
 * SmartMed – Settings Controller & API
 * Manages system configuration options (expiry threshold, themes, alerts),
 * enforces administrator access for modifications, and serves settings.html.
 */

require_once __DIR__ . '/config.php';
requireLogin();

$db = getDB();

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
          (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
          isset($_GET['api']) || isset($_GET['action']) || $_SERVER['REQUEST_METHOD'] === 'POST';

if ($isAjax) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        requireAdmin();
        $data = getRequestData();

        $expiryWarningDays = isset($data['expiryWarningDays']) ? (int)$data['expiryWarningDays'] : null;
        $theme = isset($data['theme']) ? trim($data['theme']) : null;
        $lowStockAlert = isset($data['lowStockAlert']) ? trim($data['lowStockAlert']) : null;

        $settingsToUpdate = [];
        if ($expiryWarningDays !== null && $expiryWarningDays > 0) {
            $settingsToUpdate['expiryWarningDays'] = (string)$expiryWarningDays;
        }
        if ($theme !== null && in_array($theme, ['light', 'dark', 'system'], true)) {
            $settingsToUpdate['theme'] = $theme;
        }
        if ($lowStockAlert !== null && in_array($lowStockAlert, ['enabled', 'disabled'], true)) {
            $settingsToUpdate['lowStockAlert'] = $lowStockAlert;
        }

        if (empty($settingsToUpdate)) {
            jsonResponse(['success' => false, 'error' => 'No valid settings provided to update.'], 400);
        }

        try {
            $stmt = $db->prepare("
                INSERT INTO settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");

            foreach ($settingsToUpdate as $key => $val) {
                $stmt->execute([$key, $val]);
            }

            $user = getCurrentUser();
            logAudit('Settings updated', $user['username'] ?? 'admin', 'System settings changed');

            jsonResponse([
                'success'  => true,
                'message'  => 'System settings updated successfully.',
                'settings' => $settingsToUpdate
            ]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    // GET: Read all system settings
    try {
        $rows = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $val = $row['setting_value'];
            if (is_numeric($val)) {
                $val = (int)$val;
            }
            $settings[$row['setting_key']] = $val;
        }

        // Set defaults if missing
        if (!isset($settings['expiryWarningDays'])) $settings['expiryWarningDays'] = 90;
        if (!isset($settings['theme'])) $settings['theme'] = 'light';
        if (!isset($settings['lowStockAlert'])) $settings['lowStockAlert'] = 'enabled';

        jsonResponse(['success' => true, 'settings' => $settings]);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

// Direct browser GET navigation
if (file_exists(__DIR__ . '/settings.html')) {
    include __DIR__ . '/settings.html';
} else {
    echo 'Settings view not found.';
}
