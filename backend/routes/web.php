<?php

declare(strict_types=1);

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

require_once __DIR__ . '/admin.php';
require_once __DIR__ . '/customer.php';

function route_parameter(
    string $path,
    string $pattern
): string {

    $regex = preg_quote($pattern, '#');

    $regex = preg_replace(
        '/\\\\\{[^}]+\\\\\}/',
        '([^/]+)',
        $regex
    );

    if (
        preg_match(
            '#^' . $regex . '$#',
            $path,
            $matches
        )
    ) {
        return urldecode($matches[1]);
    }

    return '';
}

function normalize_path(string $path): string
{
    $path = parse_url(
        $path,
        PHP_URL_PATH
    );

    if (!$path) {
        return '/';
    }

    $path = '/' . trim(
        $path,
        '/'
    );

    return $path === '//' ? '/' : $path;
}

function dispatch_route(): void
{
    $method = strtoupper(
        $_SERVER['REQUEST_METHOD'] ?? 'GET'
    );

    $path = normalize_path(
        $_SERVER['REQUEST_URI'] ?? '/'
    );

    /*
    |--------------------------------------------------------------------------
    | AUTHENTICATION
    |--------------------------------------------------------------------------
    */

    if (
        $method === 'POST' &&
        $path === '/auth/login'
    ) {
        AuthController::login();
        return;
    }

    if (
        $method === 'POST' &&
        $path === '/auth/register'
    ) {
        AuthController::register();
        return;
    }

    if (
        $method === 'POST' &&
        $path === '/auth/logout'
    ) {
        AuthController::logout();
        return;
    }

    if (
        $method === 'GET' &&
        $path === '/auth/me'
    ) {
        AuthController::me();
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | PROTECTED ROUTES
    |--------------------------------------------------------------------------
    */

    AuthMiddleware::handle();

    $role = AuthMiddleware::role();

    /*
    |--------------------------------------------------------------------------
    | ADMIN
    |--------------------------------------------------------------------------
    */

    if (
        str_starts_with(
            $path,
            '/admin'
        )
    ) {
        $adminPath = substr(
            $path,
            strlen('/admin')
        );

        if ($adminPath === '') {
            $adminPath = '/';
        }

        admin_routes(
            $method,
            $adminPath
        );

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | CUSTOMER
    |--------------------------------------------------------------------------
    */

    if (
        str_starts_with(
            $path,
            '/customer'
        )
    ) {
        $customerPath = substr(
            $path,
            strlen('/customer')
        );

        if ($customerPath === '') {
            $customerPath = '/';
        }

        customer_routes(
            $method,
            $customerPath
        );

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | ROLE-BASED DEFAULT
    |--------------------------------------------------------------------------
    */

    if ($path === '/') {

        if ($role === 'Administrator') {
            success_response([
                'role' => $role,
                'redirect' => '/admin/dashboard',
            ]);
        }

        if ($role === 'Customer') {
            success_response([
                'role' => $role,
                'redirect' => '/customer/dashboard',
            ]);
        }
    }

    error_response(
        'API route not found.',
        404
    );
}