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
 * Protect a page from unauthenticated users.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Get the currently logged-in user.
 */
function current_user(): array
{
    return $_SESSION['user'] ?? [
        'name' => 'Property Manager',
        'email' => 'admin@example.com',
        'role' => 'Administrator'
    ];
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