<?php

declare(strict_types=1);

require_once __DIR__ . '/../middleware/AdminMiddleware.php';

require_once __DIR__ . '/../controllers/PropertyController.php';
require_once __DIR__ . '/../controllers/UnitController.php';
require_once __DIR__ . '/../controllers/TenantController.php';
require_once __DIR__ . '/../controllers/LeaseController.php';
require_once __DIR__ . '/../controllers/PaymentController.php';
require_once __DIR__ . '/../controllers/MaintenanceController.php';
require_once __DIR__ . '/../controllers/ExpenseController.php';
require_once __DIR__ . '/../controllers/NotificationController.php';
require_once __DIR__ . '/../controllers/UserController.php';

function admin_routes(
    string $method,
    string $path
): void {

    AdminMiddleware::handle();

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD / USERS
    |--------------------------------------------------------------------------
    */

    if ($method === 'GET' && $path === '/user/{id}') {
        UserController::show(route_parameter($path, '/user/{id}'));
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | PROPERTIES
    |--------------------------------------------------------------------------
    */

    if ($method === 'GET' && $path === '/properties') {
        PropertyController::index();
        return;
    }

    if ($method === 'GET' && $path === '/properties/{id}') {
        PropertyController::show(
            route_parameter($path, '/properties/{id}')
        );
        return;
    }

    if ($method === 'POST' && $path === '/properties') {
        PropertyController::store();
        return;
    }

    if ($method === 'PUT' && $path === '/properties/{id}') {
        PropertyController::update(
            route_parameter($path, '/properties/{id}')
        );
        return;
    }

    if ($method === 'DELETE' && $path === '/properties/{id}') {
        PropertyController::destroy(
            route_parameter($path, '/properties/{id}')
        );
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | UNITS
    |--------------------------------------------------------------------------
    */

    if ($method === 'GET' && $path === '/units') {
        UnitController::index();
        return;
    }

    if ($method === 'GET' && $path === '/units/{id}') {
        UnitController::show(
            route_parameter($path, '/units/{id}')
        );
        return;
    }

    if (
        $method === 'GET' &&
        $path === '/properties/{propertyId}/units'
    ) {
        UnitController::byProperty(
            route_parameter(
                $path,
                '/properties/{propertyId}/units'
            )
        );
        return;
    }

    if ($method === 'POST' && $path === '/units') {
        UnitController::store();
        return;
    }

    if ($method === 'PUT' && $path === '/units/{id}') {
        UnitController::update(
            route_parameter($path, '/units/{id}')
        );
        return;
    }

    if ($method === 'DELETE' && $path === '/units/{id}') {
        UnitController::destroy(
            route_parameter($path, '/units/{id}')
        );
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | TENANTS
    |--------------------------------------------------------------------------
    */

    if ($method === 'GET' && $path === '/tenants') {
        TenantController::index();
        return;
    }

    if ($method === 'GET' && $path === '/tenants/{id}') {
        TenantController::show(
            route_parameter($path, '/tenants/{id}')
        );
        return;
    }

    if ($method === 'POST' && $path === '/tenants') {
        TenantController::store();
        return;
    }

    if ($method === 'PUT' && $path === '/tenants/{id}') {
        TenantController::update(
            route_parameter($path, '/tenants/{id}')
        );
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | LEASES
    |--------------------------------------------------------------------------
    */

    if ($method === 'GET' && $path === '/leases') {
        LeaseController::index();
        return;
    }

    if ($method === 'GET' && $path === '/leases/{id}') {
        LeaseController::show(
            route_parameter($path, '/leases/{id}')
        );
        return;
    }

    if ($method === 'GET' && $path === '/tenants/{tenantId}/leases') {
        LeaseController::byTenant(
            route_parameter($path, '/tenants/{tenantId}/leases')
        );
        return;
    }

    if ($method === 'POST' && $path === '/leases') {
        LeaseController::store();
        return;
    }

    if ($method === 'PUT' && $path === '/leases/{id}') {
        LeaseController::update(
            route_parameter($path, '/leases/{id}')
        );
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENTS
    |--------------------------------------------------------------------------
    */

    if ($method === 'GET' && $path === '/payments') {
        PaymentController::index();
        return;
    }

    if ($method === 'GET' && $path === '/payments/{id}') {
        PaymentController::show(
            route_parameter($path, '/payments/{id}')
        );
        return;
    }

    if ($method === 'GET' && $path === '/tenants/{tenantId}/payments') {
        PaymentController::byTenant(
            route_parameter($path, '/tenants/{tenantId}/payments')
        );
        return;
    }

    if ($method === 'POST' && $path === '/payments') {
        PaymentController::store();
        return;
    }

    if ($method === 'PUT' && $path === '/payments/{id}') {
        PaymentController::update(
            route_parameter($path, '/payments/{id}')
        );
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | MAINTENANCE
    |--------------------------------------------------------------------------
    */

    if ($method === 'GET' && $path === '/maintenance') {
        MaintenanceController::index();
        return;
    }

    if ($method === 'GET' && $path === '/maintenance/{id}') {
        MaintenanceController::show(
            route_parameter($path, '/maintenance/{id}')
        );
        return;
    }

    if (
        $method === 'GET' &&
        $path === '/tenants/{tenantId}/maintenance'
    ) {
        MaintenanceController::byTenant(
            route_parameter(
                $path,
                '/tenants/{tenantId}/maintenance'
            )
        );
        return;
    }

    if ($method === 'POST' && $path === '/maintenance') {
        MaintenanceController::store();
        return;
    }

    if ($method === 'PUT' && $path === '/maintenance/{id}') {
        MaintenanceController::update(
            route_parameter($path, '/maintenance/{id}')
        );
        return;
    }

    if ($method === 'DELETE' && $path === '/maintenance/{id}') {
        MaintenanceController::destroy(
            route_parameter($path, '/maintenance/{id}')
        );
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | EXPENSES
    |--------------------------------------------------------------------------
    */

    if ($method === 'GET' && $path === '/expenses') {
        ExpenseController::index();
        return;
    }

    if ($method === 'GET' && $path === '/expenses/{id}') {
        ExpenseController::show(
            route_parameter($path, '/expenses/{id}')
        );
        return;
    }

    if ($method === 'POST' && $path === '/expenses') {
        ExpenseController::store();
        return;
    }

    if ($method === 'PUT' && $path === '/expenses/{id}') {
        ExpenseController::update(
            route_parameter($path, '/expenses/{id}')
        );
        return;
    }

    if ($method === 'DELETE' && $path === '/expenses/{id}') {
        ExpenseController::destroy(
            route_parameter($path, '/expenses/{id}')
        );
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN NOTIFICATIONS
    |--------------------------------------------------------------------------
    */

    if ($method === 'GET' && $path === '/notifications') {
        NotificationController::admin();
        return;
    }

    if ($method === 'GET' && $path === '/notifications/unread') {
        NotificationController::adminUnread();
        return;
    }

    if ($method === 'PUT' && $path === '/notifications/read-all') {
        NotificationController::markAllAdminRead();
        return;
    }

    if ($method === 'PUT' && $path === '/notifications/{id}/read') {
        NotificationController::markRead(
            route_parameter($path, '/notifications/{id}/read')
        );
        return;
    }

    if ($method === 'DELETE' && $path === '/notifications/{id}') {
        NotificationController::delete(
            route_parameter($path, '/notifications/{id}')
        );
        return;
    }

    if ($method === 'DELETE' && $path === '/notifications') {
        NotificationController::clearAdmin();
        return;
    }

    error_response(
        'Administrator route not found.',
        404
    );
}