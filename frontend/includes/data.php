<?php

if (!function_exists('money')) {
    function money($amount): string
    {
        return 'KES ' . number_format(
            (float) ($amount ?? 0),
            2
        );
    }
}

require_once __DIR__ . '/api.php';
require_once __DIR__ . '/auth.php';
/*
|--------------------------------------------------------------------------
| Default data
|--------------------------------------------------------------------------
*/

$properties = [];
$units = [];
$tenants = [];
$payments = [];
$leases = [];
$expenses = [];
$maintenanceRequests = [];
$notifications = [];

$tenant = [];
$customerTenant = [];
$currentLease = [];

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

if (!function_exists('api_rows')) {
    function api_rows(array $response, ?string $key = null): array
    {
        if (
            !isset($response['success']) ||
            $response['success'] !== true ||
            !array_key_exists('data', $response)
        ) {
            return [];
        }

        $data = $response['data'];

        if (!is_array($data)) {
            return [];
        }

        /*
         * Requested wrapper key.
         */
        if (
            $key !== null &&
            isset($data[$key]) &&
            is_array($data[$key])
        ) {
            return $data[$key];
        }

        /*
         * If data itself is a list.
         */
        if (array_is_list($data)) {
            return $data;
        }

        /*
         * Common API wrappers.
         */
        $possibleKeys = [
            'items',
            'rows',
            'results',
            'data',
            'properties',
            'units',
            'tenants',
            'payments',
            'leases',
            'expenses',
            'maintenance',
            'maintenanceRequests',
            'notifications',
        ];

        foreach ($possibleKeys as $possibleKey) {
            if (
                isset($data[$possibleKey]) &&
                is_array($data[$possibleKey])
            ) {
                return $data[$possibleKey];
            }
        }

        return [];
    }
}

if (!function_exists('api_data')) {
    function api_data(array $response): array
    {
        if (
            !isset($response['success']) ||
            $response['success'] !== true ||
            !array_key_exists('data', $response)
        ) {
            return [];
        }

        return is_array($response['data'])
            ? $response['data']
            : [];
    }
}

if (!function_exists('data_value')) {
    function data_value(
        array $data,
        string $key,
        $default = null
    ) {
        return $data[$key] ?? $default;
    }
}

/*
|--------------------------------------------------------------------------
| ID helper
|--------------------------------------------------------------------------
*/

function normalize_id($value): string
{
    if (is_array($value)) {
        return (string) (
            $value['_id']
            ?? $value['id']
            ?? $value['propertyId']
            ?? $value['tenantId']
            ?? $value['unitId']
            ?? $value['leaseId']
            ?? ''
        );
    }

    return (string) ($value ?? '');
}

/*
|--------------------------------------------------------------------------
| Administrator Data
|--------------------------------------------------------------------------
*/

if (
    is_logged_in() &&
    current_role() === 'Administrator'
) {

    /*
     * Get all administrator resources from Node API.
     */

    $propertyResponse = api_get('/properties');
    $unitResponse = api_get('/units');
    $tenantResponse = api_get('/tenants');
    $paymentResponse = api_get('/payments');
    $leaseResponse = api_get('/leases');
    $expenseResponse = api_get('/expenses');
    $maintenanceResponse = api_get('/maintenance');
    $notificationResponse = api_get('/notifications');

    /*
    |--------------------------------------------------------------------------
    | Properties
    |--------------------------------------------------------------------------
    */

    $properties = api_rows(
        $propertyResponse,
        'properties'
    );

    foreach ($properties as &$property) {

        $property['_id'] =
            $property['_id']
            ?? $property['id']
            ?? '';

        $property['id'] =
            $property['id']
            ?? $property['propertyId']
            ?? $property['_id']
            ?? '';

        $property['name'] =
            $property['name']
            ?? $property['propertyName']
            ?? '';

        $property['location'] =
            $property['location']
            ?? '';

        $property['status'] =
            $property['status']
            ?? 'Active';
    }

    unset($property);

    /*
    |--------------------------------------------------------------------------
    | Units
    |--------------------------------------------------------------------------
    */

    $units = api_rows(
        $unitResponse,
        'units'
    );

    foreach ($units as &$unit) {

        $unit['_id'] =
            $unit['_id']
            ?? $unit['id']
            ?? '';

        $unit['id'] =
            $unit['id']
            ?? $unit['unitId']
            ?? $unit['_id']
            ?? '';

        $unit['unitNumber'] =
            $unit['unitNumber']
            ?? '';

        $unit['type'] =
            $unit['type']
            ?? '';

        $unit['status'] =
            $unit['status']
            ?? 'Vacant';

        /*
         * Property can be populated.
         */
        if (
            isset($unit['propertyId']) &&
            is_array($unit['propertyId'])
        ) {
            $unit['property'] =
                $unit['propertyId']['name']
                ?? '';

            $unit['propertyIdValue'] =
                $unit['propertyId']['_id']
                ?? $unit['propertyId']['id']
                ?? '';
        } else {
            $unit['property'] =
                $unit['property']
                ?? '';

            $unit['propertyIdValue'] =
                $unit['propertyId']
                ?? '';
        }
    }

    unset($unit);

    /*
    |--------------------------------------------------------------------------
    | Tenants
    |--------------------------------------------------------------------------
    */

    $tenants = api_rows(
        $tenantResponse,
        'tenants'
    );

    foreach ($tenants as &$tenantItem) {

        $tenantItem['_id'] =
            $tenantItem['_id']
            ?? $tenantItem['id']
            ?? '';

        $tenantItem['id'] =
            $tenantItem['id']
            ?? $tenantItem['tenantId']
            ?? $tenantItem['_id']
            ?? '';

        $tenantItem['name'] =
            $tenantItem['name']
            ?? '';

        $tenantItem['email'] =
            $tenantItem['email']
            ?? '';

        $tenantItem['phone'] =
            $tenantItem['phone']
            ?? '';
    }

    unset($tenantItem);

    /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    */

    $payments = api_rows(
        $paymentResponse,
        'payments'
    );

    foreach ($payments as &$payment) {

        /*
         * MongoDB ID
         */
        $payment['_id'] =
            $payment['_id']
            ?? $payment['id']
            ?? '';

        /*
         * Frontend-compatible ID/reference.
         */
        $payment['id'] =
            $payment['paymentId']
            ?? $payment['id']
            ?? $payment['_id']
            ?? '';

        /*
         * Tenant.
         */
        if (
            isset($payment['tenantId']) &&
            is_array($payment['tenantId'])
        ) {
            $payment['tenant'] =
                $payment['tenantId']['name']
                ?? $payment['tenantId']['tenantId']
                ?? '';

            $payment['tenant_id'] =
                $payment['tenantId']['_id']
                ?? $payment['tenantId']['id']
                ?? '';

            $payment['tenantEmail'] =
                $payment['tenantId']['email']
                ?? '';
        } else {
            $payment['tenant'] =
                $payment['tenant']
                ?? '';

            $payment['tenant_id'] =
                $payment['tenant_id']
                ?? $payment['tenantId']
                ?? '';
        }

        /*
         * Lease / property.
         *
         * Your Payment controller populates leaseId with:
         * leaseId, startDate, endDate, rent, status
         *
         * The lease itself may contain a property reference depending
         * on your Lease model.
         */
        if (
            isset($payment['leaseId']) &&
            is_array($payment['leaseId'])
        ) {

            $payment['lease_id'] =
                $payment['leaseId']['_id']
                ?? $payment['leaseId']['id']
                ?? '';

            if (
                isset($payment['leaseId']['propertyId']) &&
                is_array($payment['leaseId']['propertyId'])
            ) {
                $payment['property'] =
                    $payment['leaseId']['propertyId']['name']
                    ?? '';
            } else {
                $payment['property'] =
                    $payment['property']
                    ?? '';
            }

        } else {

            $payment['lease_id'] =
                $payment['leaseId']
                ?? '';

            $payment['property'] =
                $payment['property']
                ?? '';
        }

        /*
         * Payment method.
         */
        $payment['method'] =
            $payment['paymentMethod']
            ?? $payment['method']
            ?? '-';

        /*
         * Amount.
         */
        $payment['amount'] =
            (float) (
                $payment['amount']
                ?? 0
            );

        /*
         * Date.
         */
        $payment['date'] =
            $payment['paymentDate']
            ?? $payment['date']
            ?? '';

        /*
         * Status.
         */
        $payment['status'] =
            $payment['status']
            ?? 'Pending';
    }

    unset($payment);

    /*
    |--------------------------------------------------------------------------
    | Leases
    |--------------------------------------------------------------------------
    */

    $leases = api_rows(
        $leaseResponse,
        'leases'
    );

    foreach ($leases as &$lease) {

        $lease['_id'] =
            $lease['_id']
            ?? $lease['id']
            ?? '';

        $lease['id'] =
            $lease['leaseId']
            ?? $lease['id']
            ?? $lease['_id']
            ?? '';

        /*
         * Tenant.
         */
        if (
            isset($lease['tenantId']) &&
            is_array($lease['tenantId'])
        ) {
            $lease['tenant'] =
                $lease['tenantId']['name']
                ?? '';

            $lease['tenant_id'] =
                $lease['tenantId']['_id']
                ?? $lease['tenantId']['id']
                ?? '';
        }

        /*
         * Property.
         */
        if (
            isset($lease['propertyId']) &&
            is_array($lease['propertyId'])
        ) {
            $lease['property'] =
                $lease['propertyId']['name']
                ?? '';

            $lease['property_id'] =
                $lease['propertyId']['_id']
                ?? $lease['propertyId']['id']
                ?? '';
        }

        $lease['rent'] =
            (float) (
                $lease['rent']
                ?? 0
            );
    }

    unset($lease);

    /*
    |--------------------------------------------------------------------------
    | Expenses
    |--------------------------------------------------------------------------
    */

    $expenses = api_rows(
        $expenseResponse,
        'expenses'
    );

    foreach ($expenses as &$expense) {

        $expense['_id'] =
            $expense['_id']
            ?? $expense['id']
            ?? '';

        /*
         * Frontend reference.
         */
        $expense['id'] =
            $expense['expenseId']
            ?? $expense['id']
            ?? $expense['_id']
            ?? '';

        /*
         * Property is populated by the backend.
         */
        if (
            isset($expense['propertyId']) &&
            is_array($expense['propertyId'])
        ) {

            $expense['property'] =
                $expense['propertyId']['name']
                ?? '';

            $expense['property_id'] =
                $expense['propertyId']['_id']
                ?? $expense['propertyId']['id']
                ?? '';

            $expense['location'] =
                $expense['propertyId']['location']
                ?? '';

        } else {

            $expense['property'] =
                $expense['property']
                ?? '';

            $expense['property_id'] =
                $expense['property_id']
                ?? $expense['propertyId']
                ?? '';
        }

        $expense['category'] =
            $expense['category']
            ?? '';

        $expense['description'] =
            $expense['description']
            ?? $expense['category']
            ?? '';

        $expense['amount'] =
            (float) (
                $expense['amount']
                ?? 0
            );

        $expense['date'] =
            $expense['expenseDate']
            ?? $expense['date']
            ?? '';

        $expense['status'] =
            $expense['status']
            ?? 'Pending';
    }

    unset($expense);

    /*
    |--------------------------------------------------------------------------
    | Maintenance
    |--------------------------------------------------------------------------
    */

    $maintenanceRequests = api_rows(
        $maintenanceResponse,
        'maintenance'
    );

    if (empty($maintenanceRequests)) {
        $maintenanceRequests = api_rows(
            $maintenanceResponse,
            'maintenanceRequests'
        );
    }

    foreach ($maintenanceRequests as &$request) {

        $request['_id'] =
            $request['_id']
            ?? $request['id']
            ?? '';

        $request['id'] =
            $request['maintenanceId']
            ?? $request['id']
            ?? $request['_id']
            ?? '';

        /*
         * Tenant.
         */
        if (
            isset($request['tenantId']) &&
            is_array($request['tenantId'])
        ) {

            $request['tenant'] =
                $request['tenantId']['name']
                ?? '';

            $request['tenant_id'] =
                $request['tenantId']['_id']
                ?? $request['tenantId']['id']
                ?? '';

            $request['tenantEmail'] =
                $request['tenantId']['email']
                ?? '';

        } else {

            $request['tenant'] =
                $request['tenant']
                ?? '';

            $request['tenant_id'] =
                $request['tenant_id']
                ?? $request['tenantId']
                ?? '';
        }

        /*
         * Property.
         */
        if (
            isset($request['propertyId']) &&
            is_array($request['propertyId'])
        ) {

            $request['property'] =
                $request['propertyId']['name']
                ?? '';

            $request['property_id'] =
                $request['propertyId']['_id']
                ?? $request['propertyId']['id']
                ?? '';

        } else {

            $request['property'] =
                $request['property']
                ?? '';

            $request['property_id'] =
                $request['property_id']
                ?? $request['propertyId']
                ?? '';
        }

        /*
         * Unit.
         */
        if (
            isset($request['unitId']) &&
            is_array($request['unitId'])
        ) {

            $request['unit'] =
                $request['unitId']['unitNumber']
                ?? $request['unitId']['unitId']
                ?? '';

            $request['unit_id'] =
                $request['unitId']['_id']
                ?? $request['unitId']['id']
                ?? '';

        } else {

            $request['unit'] =
                $request['unit']
                ?? '';

            $request['unit_id'] =
                $request['unit_id']
                ?? $request['unitId']
                ?? '';
        }

        $request['issue'] =
            $request['issue']
            ?? '';

        $request['description'] =
            $request['description']
            ?? '';

        $request['priority'] =
            $request['priority']
            ?? 'Medium';

        $request['status'] =
            $request['status']
            ?? 'Pending';

        $request['date'] =
            $request['createdAt']
            ?? $request['date']
            ?? '';
    }

    unset($request);

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    $notifications = api_rows(
        $notificationResponse,
        'notifications'
    );

    foreach ($notifications as &$notification) {

        $notification['_id'] =
            $notification['_id']
            ?? $notification['id']
            ?? '';

        $notification['id'] =
            $notification['id']
            ?? $notification['_id']
            ?? '';

        $notification['type'] =
            $notification['type']
            ?? 'system';

        $notification['title'] =
            $notification['title']
            ?? 'Notification';

        $notification['message'] =
            $notification['message']
            ?? '';

        $notification['date'] =
            $notification['createdAt']
            ?? $notification['date']
            ?? '';

        $notification['read'] =
            !empty($notification['read']);
    }

    unset($notification);
}

/*
|--------------------------------------------------------------------------
| Customer Data
|--------------------------------------------------------------------------
*/

if (
    is_logged_in() &&
    current_role() === 'Customer'
) {

    $dashboardResponse =
        api_get('/customer/dashboard');

    $paymentResponse =
        api_get('/customer/payments');

    $leaseResponse =
        api_get('/customer/lease');

    $maintenanceResponse =
        api_get('/customer/maintenance');

    $notificationResponse =
        api_get('/notifications');

    $dashboardData =
        api_data($dashboardResponse);

    /*
     * Tenant.
     */
    if (
        isset($dashboardData['tenant']) &&
        is_array($dashboardData['tenant'])
    ) {
        $tenant =
            $dashboardData['tenant'];
    }

    if (
        empty($tenant) &&
        isset($dashboardData['currentTenant']) &&
        is_array($dashboardData['currentTenant'])
    ) {
        $tenant =
            $dashboardData['currentTenant'];
    }

    /*
     * Lease.
     */
    if (
        isset($dashboardData['lease']) &&
        is_array($dashboardData['lease'])
    ) {
        $currentLease =
            $dashboardData['lease'];
    }

    if (
        empty($currentLease) &&
        isset($dashboardData['currentLease']) &&
        is_array($dashboardData['currentLease'])
    ) {
        $currentLease =
            $dashboardData['currentLease'];
    }

    if (empty($currentLease)) {

        $leaseData =
            api_data($leaseResponse);

        if (
            isset($leaseData['_id']) ||
            isset($leaseData['id'])
        ) {
            $currentLease =
                $leaseData;
        } elseif (
            isset($leaseData['lease']) &&
            is_array($leaseData['lease'])
        ) {
            $currentLease =
                $leaseData['lease'];
        } elseif (array_is_list($leaseData)) {
            $currentLease =
                $leaseData[0] ?? [];
        }
    }

    /*
     * Customer payments.
     */
    $payments =
        api_rows(
            $paymentResponse,
            'payments'
        );

    if (
        empty($payments) &&
        isset($dashboardData['payments']) &&
        is_array($dashboardData['payments'])
    ) {
        $payments =
            $dashboardData['payments'];
    }

    foreach ($payments as &$payment) {

        $payment['_id'] =
            $payment['_id']
            ?? $payment['id']
            ?? '';

        $payment['id'] =
            $payment['paymentId']
            ?? $payment['id']
            ?? $payment['_id']
            ?? '';

        if (
            isset($payment['tenantId']) &&
            is_array($payment['tenantId'])
        ) {
            $payment['tenant'] =
                $payment['tenantId']['name']
                ?? '';
        }

        $payment['method'] =
            $payment['paymentMethod']
            ?? $payment['method']
            ?? '-';

        $payment['date'] =
            $payment['paymentDate']
            ?? $payment['date']
            ?? '';

        $payment['amount'] =
            (float) (
                $payment['amount']
                ?? 0
            );

        $payment['status'] =
            $payment['status']
            ?? 'Pending';
    }

    unset($payment);

    /*
     * Customer maintenance.
     */
    $maintenanceRequests =
        api_rows(
            $maintenanceResponse,
            'maintenance'
        );

    if (empty($maintenanceRequests)) {
        $maintenanceRequests =
            api_rows(
                $maintenanceResponse,
                'maintenanceRequests'
            );
    }

    foreach ($maintenanceRequests as &$request) {

        $request['_id'] =
            $request['_id']
            ?? $request['id']
            ?? '';

        $request['id'] =
            $request['maintenanceId']
            ?? $request['id']
            ?? $request['_id']
            ?? '';

        if (
            isset($request['tenantId']) &&
            is_array($request['tenantId'])
        ) {
            $request['tenant'] =
                $request['tenantId']['name']
                ?? '';
        }

        if (
            isset($request['propertyId']) &&
            is_array($request['propertyId'])
        ) {
            $request['property'] =
                $request['propertyId']['name']
                ?? '';
        }

        if (
            isset($request['unitId']) &&
            is_array($request['unitId'])
        ) {
            $request['unit'] =
                $request['unitId']['unitNumber']
                ?? '';
        }

        $request['status'] =
            $request['status']
            ?? 'Pending';

        $request['priority'] =
            $request['priority']
            ?? 'Medium';

        $request['date'] =
            $request['createdAt']
            ?? $request['date']
            ?? '';
    }

    unset($request);

    /*
     * Customer notifications.
     */
    $notifications =
        api_rows(
            $notificationResponse,
            'notifications'
        );

    foreach ($notifications as &$notification) {

        $notification['_id'] =
            $notification['_id']
            ?? $notification['id']
            ?? '';

        $notification['id'] =
            $notification['id']
            ?? $notification['_id']
            ?? '';

        $notification['date'] =
            $notification['createdAt']
            ?? $notification['date']
            ?? '';

        $notification['title'] =
            $notification['title']
            ?? 'Notification';

        $notification['message'] =
            $notification['message']
            ?? '';

        $notification['type'] =
            $notification['type']
            ?? 'system';

        $notification['read'] =
            !empty($notification['read']);
    }

    unset($notification);

    /*
     * Customer tenant.
     */
    $customerTenant =
        $tenant;

    /*
     * Normalize customer maintenance.
     */
    if (!empty($maintenanceRequests)) {

        $customerName =
            current_user()['name']
            ?? '';

        $filteredMaintenance = [];

        foreach ($maintenanceRequests as $request) {

            /*
             * Customer endpoint should already be filtered.
             * Keep records returned by it.
             */
            if (
                !isset($request['tenant']) ||
                $request['tenant'] === '' ||
                $customerName === '' ||
                $request['tenant'] === $customerName
            ) {
                $filteredMaintenance[] =
                    $request;
            }
        }

        $maintenanceRequests =
            $filteredMaintenance;
    }
}

/*
|--------------------------------------------------------------------------
| Backwards compatibility
|--------------------------------------------------------------------------
*/

if (!isset($tenant)) {
    $tenant = [];
}

if (!isset($customerTenant)) {
    $customerTenant = $tenant;
}

if (!isset($currentLease)) {
    $currentLease =
        $leases[0] ?? [];
}