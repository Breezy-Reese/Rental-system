<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| HTML Escape Helper
|--------------------------------------------------------------------------
*/

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars(
            (string) ($value ?? ''),
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

function is_logged_in(): bool
{
    return !empty($_SESSION['user'])
        && is_array($_SESSION['user']);
}

function current_user(): array
{
    return $_SESSION['user'] ?? [];
}

function current_role(): string
{
    $role = $_SESSION['user']['role'] ?? '';

    /*
     * Normalize the role so that Customer/customer/CUSTOMER
     * and Administrator/administrator are handled consistently.
     */
    $normalized = strtolower(trim((string) $role));

    if ($normalized === 'customer') {
        return 'Customer';
    }

    if (
        $normalized === 'administrator' ||
        $normalized === 'admin'
    ) {
        return 'Administrator';
    }

    return '';
}

function current_user_id(): string
{
    return (string) (
        $_SESSION['user']['id']
        ?? $_SESSION['user']['_id']
        ?? ''
    );
}

/*
|--------------------------------------------------------------------------
| Login Requirement
|--------------------------------------------------------------------------
*/

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ../login.php');
        exit;
    }

    /*
     * A logged-in user must also have a valid role.
     */
    if (current_role() === '') {
        logout_user();

        header('Location: ../login.php');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Role Requirement
|--------------------------------------------------------------------------
*/

function require_role(string $role): void
{
    require_login();

    $requiredRole = strtolower(trim($role));
    $actualRole = strtolower(current_role());

    if ($actualRole === $requiredRole) {
        return;
    }

    redirect_by_role();
}

/*
|--------------------------------------------------------------------------
| Redirect According To Role
|--------------------------------------------------------------------------
*/

function redirect_by_role(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }

    $role = current_role();

    if ($role === 'Administrator') {
        header('Location: admin/dashboard.php');
        exit;
    }

    if ($role === 'Customer') {
        header('Location: customer/dashboard.php');
        exit;
    }

    /*
     * Unknown/invalid role.
     */
    logout_user();

    header('Location: login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
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