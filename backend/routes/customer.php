<?php

declare(strict_types=1);

require_once __DIR__ . '/../middleware/CustomerMiddleware.php';

require_once __DIR__ . '/../controllers/TenantController.php';
require_once __DIR__ . '/../controllers/LeaseController.php';
require_once __DIR__ . '/../controllers/PaymentController.php';
require_once __DIR__ . '/../controllers/MaintenanceController.php';
require_once __DIR__ . '/../controllers/NotificationController.php';
require_once __DIR__ . '/../controllers/UserController.php';

function customer_routes(
    string $method,
    string $path
): void {

    CustomerMiddleware::handle();

    $userId = CustomerMiddleware::userId();

    /*
    |--------------------------------------------------------------------------
    | MY PROFILE
    |--------------------------------------------------------------------------
    */

    if ($method === 'GET' && $path === '/profile') {
        UserController::show($userId);
        return;
    }

    if ($method === 'PUT' && $path === '/profile') {
        UserController::update($userId);
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | MY TENANT ACCOUNT
    |--------------------------------------------------------------------------
    */

    if ($method === 'GET' && $path === '/tenant') {
        TenantController::byUser($userId);
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | MY LEASES
    |--------------------------------------------------------------------------
    */

    if ($method === 'GET' && $path === '/leases') {

        $tenant = TenantController::byUser($userId);

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | MY PAYMENTS
    |--------------------------------------------------------------------------
    */

    if ($method === 'GET' && $path === '/payments') {

        $tenant = TenantController::byUser($userId);

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | MY MAINTENANCE REQUESTS
    |--------------------------------------------------------------------------
    */

    if ($method === 'GET' && $path === '/maintenance') {

        $tenant = TenantController::byUser($userId);

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE MAINTENANCE REQUEST
    |--------------------------------------------------------------------------
    */

    if ($method === 'POST' && $path === '/maintenance') {

        MaintenanceController::store();

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | CUSTOMER NOTIFICATIONS
    |--------------------------------------------------------------------------
    */

    if ($method === 'GET' && $path === '/notifications') {

        NotificationController::customer($userId);

        return;
    }

    if ($method === 'GET' && $path === '/notifications/unread') {

        NotificationController::customerUnread($userId);

        return;
    }

    if ($method === 'PUT' && $path === '/notifications/read-all') {

        NotificationController::markAllCustomerRead($userId);

        return;
    }

    if ($method === 'PUT' && $path === '/notifications/{id}/read') {

        NotificationController::markRead(
            route_parameter(
                $path,
                '/notifications/{id}/read'
            )
        );

        return;
    }

    error_response(
        'Customer route not found.',
        404
    );
}