<?php

/**
 * ============================================================
 * PropertyPro Administrator Access Control
 * ============================================================
 *
 * This file protects all pages inside /public/admin/.
 *
 * Rules:
 * - Not logged in  → login page
 * - Administrator  → allowed
 * - Customer       → customer dashboard
 * - Unknown role   → logout and login
 * ============================================================
 */

require_once __DIR__ . "/auth.php";

/**
 * Require the current user to be an Administrator.
 */
function require_admin(): void
{
    // User must be logged in first.
    require_login();

    // Administrator is allowed to continue.
    if (current_role() === 'Administrator') {
        return;
    }

    // Customers must never access admin pages.
    if (current_role() === 'Customer') {
        header("Location: /customer/dashboard.php");
        exit;
    }

    // Unknown or invalid role.
    logout_user();

    header("Location: /login.php");
    exit;
}

