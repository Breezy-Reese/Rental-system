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
    return !empty($_SESSION['user']);
}

function current_user(): array
{
    return $_SESSION['user'] ?? [];
}

function current_role(): string
{
    return $_SESSION['user']['role'] ?? '';
}

/*
|--------------------------------------------------------------------------
| Role Helpers
|--------------------------------------------------------------------------
*/

function is_admin(): bool
{
    return current_role() === 'Administrator';
}

function is_customer(): bool
{
    return current_role() === 'Customer';
}

/*
|--------------------------------------------------------------------------
| Require Login
|--------------------------------------------------------------------------
*/

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Require Administrator
|--------------------------------------------------------------------------
|
| Only users whose role is exactly "Administrator" can access
| administrator pages.
|
| Customers are sent to the customer dashboard.
|
*/

function require_admin(): void
{
    require_login();

    if (!is_admin()) {
        if (is_customer()) {
            header('Location: /customer/dashboard.php');
        } else {
            logout_user();
            header('Location: /login.php');
        }

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Require Customer
|--------------------------------------------------------------------------
|
| Only users whose role is exactly "Customer" can access
| customer pages.
|
| Administrators are sent to the administrator dashboard.
|
*/

function require_customer(): void
{
    require_login();

    if (!is_customer()) {
        if (is_admin()) {
            header('Location: /admin/dashboard.php');
        } else {
            logout_user();
            header('Location: /login.php');
        }

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Generic Role Requirement
|--------------------------------------------------------------------------
*/

function require_role(string $role): void
{
    require_login();

    if (current_role() === $role) {
        return;
    }

    if (is_admin()) {
        header('Location: /admin/dashboard.php');
        exit;
    }

    if (is_customer()) {
        header('Location: /customer/dashboard.php');
        exit;
    }

    logout_user();

    header('Location: /login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Redirect According To Role
|--------------------------------------------------------------------------
*/

function redirect_by_role(): void
{
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }

    if (is_admin()) {
        header('Location: /admin/dashboard.php');
        exit;
    }

    if (is_customer()) {
        header('Location: /customer/dashboard.php');
        exit;
    }

    /*
     * Unknown role = destroy session and require login again.
     */
    logout_user();

    header('Location: /login.php');
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
