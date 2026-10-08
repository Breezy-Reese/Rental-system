<?php

/**
 * ============================================================
 * PropertyPro - System Guard
 * ============================================================
 *
 * Include at the very top of any admin page that lives inside
 * the System section (hub, health, database, …).
 *
 * Usage:
 *   require_once __DIR__ . '/_system_guard.php';
 *
 * The calling page must already have run require_admin().
 */

if (!function_exists('pp_system_guard_check')) {

    function pp_system_guard_check(int $ttlSeconds = 1800): void
    {
        $verifiedAt = (int)($_SESSION['system_verified_at'] ?? 0);
        $age        = time() - $verifiedAt;

        if ($verifiedAt > 0 && $age < $ttlSeconds) {
            return; // still valid
        }

        /*
         * Remember where the user was trying to go, so we can
         * send them back after successful verification.
         */
        $scriptPath  = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $currentFile = basename($scriptPath);

        $allowed = ['system.php', 'health.php', 'database.php'];

        if (!in_array($currentFile, $allowed, true)) {
            $currentFile = 'system.php';
        }

        $_SESSION['system_after_verify'] = $currentFile;

        header('Location: verify-system.php');
        exit;
    }
}

pp_system_guard_check();