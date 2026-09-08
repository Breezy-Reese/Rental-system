<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Check whether a user is logged in.
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user']);
}

/**
 * Get the currently logged-in user.
 */
function current_user(): array
{
    return $_SESSION['user'] ?? [];
}

/**
 * Get the current user's role.
 */
function current_role(): string
{
    return $_SESSION['user']['role'] ?? '';
}

/**
 * Require any authenticated user.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        header("Location: ../login.php");
        exit;
    }
}

/**
 * Redirect a logged-in user to the correct dashboard.
 */
function redirect_by_role(): void
{
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }

    if (current_role() === 'Administrator') {
        header("Location: admin/dashboard.php");
        exit;
    }

    if (current_role() === 'Customer') {
        header("Location: customer/dashboard.php");
        exit;
    }

    // Unknown role
    logout_user();

    header("Location: login.php");
    exit;
}

/**
 * Log the user out.
 */
function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}