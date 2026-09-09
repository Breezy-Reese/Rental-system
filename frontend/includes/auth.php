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

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ../login.php');
        exit;
    }
}

function require_role(string $role): void
{
    require_login();

    if (current_role() !== $role) {
        if (current_role() === 'Administrator') {
            header('Location: admin/dashboard.php');
        } elseif (current_role() === 'Customer') {
            header('Location: customer/dashboard.php');
        } else {
            header('Location: login.php');
        }

        exit;
    }
}

function redirect_by_role(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }

    if (current_role() === 'Administrator') {
        header('Location: admin/dashboard.php');
        exit;
    }

    if (current_role() === 'Customer') {
        header('Location: customer/dashboard.php');
        exit;
    }

    logout_user();

    header('Location: login.php');
    exit;
}

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