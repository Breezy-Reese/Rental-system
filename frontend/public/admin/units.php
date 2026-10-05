<?php
require_once __DIR__ . '/../../includes/api.php';
require_once __DIR__ . '/../../includes/auth.php';

/*
|--------------------------------------------------------------------------
| Admin protection
|--------------------------------------------------------------------------
*/

require_admin();

/*
|--------------------------------------------------------------------------
| Page settings
|--------------------------------------------------------------------------
*/

$pageTitle = 'Units';

/*
|--------------------------------------------------------------------------
| Safe escaping helper
|--------------------------------------------------------------------------
*/

if (!function_exists('pp_units_e')) {
    function pp_units_e($value): string
    {
        return htmlspecialchars(
            (string)($value ?? ''),
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

function pp_units_get(string $endpoint): array
{
    try {
        $response = api_get($endpoint);

        if (!is_array($response)) {
            error_log(
                'PropertyPro Units: Invalid API response from ' .
                $endpoint
            );

            return [
                'success' => false,
                'data' => [],
                'message' => 'Invalid API response.'
            ];
        }

        return $response;

    } catch (Throwable $e) {

        error_log(
            'PropertyPro Units API error [' .
            $endpoint .
            ']: ' .
            $e->getMessage()
        );

        return [
            'success' => false,
            'data' => [],
            'message' => 'Unable to load data.'
        ];
    }
}

/*
|--------------------------------------------------------------------------
| Safely extract rows from API response
|--------------------------------------------------------------------------
*/

function pp_units_rows($response, ?string $preferredKey = null): array
{
    if (!is_array($response)) {
        return [];
    }


    $data = $response['data'] ?? null;

    if (!is_array($data)) {
        return [];
    }

    /*
     * Some endpoints may return:
     *
     * data: {
     *     units: [...]
     * }
     */

    if (
        $preferredKey !== null &&
        isset($data[$preferredKey]) &&
        is_array($data[$preferredKey])
    ) {
        return $data[$preferredKey];
    }

    /*
     * If data itself is already a list, return it.
     */

    if (array_is_list($data)) {
        return $data;
    }

    /*
     * Try to find the first nested list.
     */

    foreach ($data as $value) {
        if (is_array($value) && array_is_list($value)) {
            return $value;
        }
    }

    return [];
}

/*
|--------------------------------------------------------------------------
| ID helper
|--------------------------------------------------------------------------
*/

function pp_units_id($value): string
{
    if (is_array($value)) {
        return (string)(
            $value['_id']
            ?? $value['id']
            ?? ''
        );
    }

    if (is_object($value)) {
        return (string)(
            $value->_id
            ?? $value->id
            ?? ''
        );
    }

    return (string)($value ?? '');
}

/*
|--------------------------------------------------------------------------
| Text helper
|--------------------------------------------------------------------------
*/

function pp_units_text($value): string
{
    if (is_array($value)) {
        return (string)(
            $value['name']
            ?? $value['title']
            ?? $value['fullName']
            ?? $value['email']
            ?? ''
        );
    }

    if (is_object($value)) {
        return (string)(
            $value->name
            ?? $value->title
            ?? $value->fullName
            ?? $value->email
            ?? ''
        );
    }

    return trim((string)($value ?? ''));
}

/*
|--------------------------------------------------------------------------
| Load only the required datasets
|--------------------------------------------------------------------------
*/

$propertiesResponse = pp_units_get('/properties');
$unitsResponse      = pp_units_get('/units');
$tenantsResponse    = pp_units_get('/tenants');
$customersResponse  = pp_units_get('/admin/customers');

$properties = pp_units_rows(
    $propertiesResponse,
    'properties'
);

$units = pp_units_rows(
    $unitsResponse,
    'units'
);

$tenants = pp_units_rows(
    $tenantsResponse,
    'tenants'
);

$customers = pp_units_rows(
    $customersResponse,
    'customers'
);

/*
|--------------------------------------------------------------------------
| API status messages
|--------------------------------------------------------------------------
*/

$loadErrors = [];

if (($propertiesResponse['success'] ?? false) !== true) {
    $loadErrors[] = 'Properties could not be loaded.';
}

if (($unitsResponse['success'] ?? false) !== true) {
    $loadErrors[] = 'Units could not be loaded.';
}

if (($tenantsResponse['success'] ?? false) !== true) {
    $loadErrors[] = 'Tenant information could not be loaded.';
}

if (($customersResponse['success'] ?? false) !== true) {
    $loadErrors[] = 'Customer information could not be loaded.';
}

/*
|--------------------------------------------------------------------------
| Find property
|--------------------------------------------------------------------------
*/

function pp_units_find_property($propertyId): ?array
{
    global $properties;

    $id = pp_units_id($propertyId);

    if ($id === '') {
        return null;
    }

    foreach ($properties as $property) {

        if (!is_array($property)) {
            continue;
        }

        $candidateId = pp_units_id(
            $property['_id']
            ?? $property['id']
            ?? ''
        );

        if ($candidateId === $id) {
            return $property;
        }
    }

    return null;
}

/*
|--------------------------------------------------------------------------
| Find tenant
|--------------------------------------------------------------------------
*/

function pp_units_find_tenant($tenantId): ?array
{
    global $tenants;

    $id = pp_units_id($tenantId);

    if ($id === '') {
        return null;
    }

    foreach ($tenants as $tenant) {

        if (!is_array($tenant)) {
            continue;
        }

        $candidateId = pp_units_id(
            $tenant['_id']
            ?? $tenant['id']
            ?? ''
        );

        if ($candidateId === $id) {
            return $tenant;
        }
    }

    return null;
}

/*
|--------------------------------------------------------------------------
| Property name
|--------------------------------------------------------------------------
*/

function pp_units_property_name(array $unit): string
{
    $propertyValue =
        $unit['property']
        ?? $unit['propertyId']
        ?? null;

    /*
     * If the API already populated the property.
     */

    if (is_array($propertyValue)) {

        $name =
            $propertyValue['name']
            ?? $propertyValue['propertyName']
            ?? $propertyValue['title']
            ?? '';

        if ($name !== '') {
            return (string)$name;
        }
    }

    /*
     * Otherwise look it up.
     */

    $property = pp_units_find_property($propertyValue);

    if ($property) {

        return (string)(
            $property['name']
            ?? $property['propertyName']
            ?? $property['title']
            ?? 'Unknown Property'
        );
    }

    return 'Unknown Property';
}

/*
|--------------------------------------------------------------------------
| Tenant name
|--------------------------------------------------------------------------
*/

function pp_units_tenant_name(array $unit): string
{
    /*
     * Already-populated tenant object.
     */

    $tenantValue =
        $unit['tenant']
        ?? $unit['tenantId']
        ?? null;

    if (is_array($tenantValue)) {

        $name =
            $tenantValue['name']
            ?? $tenantValue['fullName']
            ?? trim(
                ($tenantValue['firstName'] ?? '') .
                ' ' .
                ($tenantValue['lastName'] ?? '')
            );

        if ($name !== '') {
            return $name;
        }
    }

    /*
     * Look up tenant using tenantId.
     */

    $tenant = pp_units_find_tenant($tenantValue);

    if ($tenant) {

        $name =
            $tenant['name']
            ?? $tenant['fullName']
            ?? trim(
                ($tenant['firstName'] ?? '') .
                ' ' .
                ($tenant['lastName'] ?? '')
            );

        if ($name !== '') {
            return $name;
        }
    }

    /*
     * Some API responses may directly provide tenantName.
     */

    if (!empty($unit['tenantName'])) {
        return (string)$unit['tenantName'];
    }

    return 'Vacant';
}

/*
|--------------------------------------------------------------------------
| Unit status
|--------------------------------------------------------------------------
*/

function pp_units_status(array $unit): string
{
    $status = strtolower(
        trim(
            (string)(
                $unit['status']
                ?? $unit['unitStatus']
                ?? ''
            )
        )
    );

    if (
        in_array(
            $status,
            ['occupied', 'rented', 'leased'],
            true
        )
    ) {
        return 'Occupied';
    }

    if (
        in_array(
            $status,
            ['vacant', 'available', 'empty'],
            true
        )
    ) {
        return 'Vacant';
    }

    /*
     * If there is a tenant assigned, treat it as occupied.
     */

    $tenantId =
        $unit['tenantId']
        ?? $unit['tenant']
        ?? null;

    if (pp_units_id($tenantId) !== '') {
        return 'Occupied';
    }

    return 'Vacant';
}

/*
|--------------------------------------------------------------------------
| Unit rent
|--------------------------------------------------------------------------
*/

function pp_units_rent(array $unit): float
{
    return (float)(
        $unit['rent']
        ?? $unit['monthlyRent']
        ?? $unit['price']
        ?? 0
    );
}

/*
|--------------------------------------------------------------------------
| Unit identifier
|--------------------------------------------------------------------------
*/

function pp_units_number(array $unit): string
{
    return (string)(
        $unit['unitNumber']
        ?? $unit['number']
        ?? $unit['name']
        ?? $unit['unitName']
        ?? 'N/A'
    );
}

/*
|--------------------------------------------------------------------------
| Customer display name
|--------------------------------------------------------------------------
*/

function pp_units_customer_name(array $customer): string
{
    $name =
        $customer['name']
        ?? $customer['fullName']
        ?? '';

    if ($name !== '') {
        return (string)$name;
    }

    $firstName = trim(
        (string)($customer['firstName'] ?? '')
    );

    $lastName = trim(
        (string)($customer['lastName'] ?? '')
    );

    $fullName = trim(
        $firstName . ' ' . $lastName
    );

    if ($fullName !== '') {
        return $fullName;
    }

    return (string)(
        $customer['email']
        ?? 'Unnamed Customer'
    );
}

/*
|--------------------------------------------------------------------------
| Determine whether customer already has a tenant/lease
|--------------------------------------------------------------------------
*/

function pp_units_customer_has_lease(array $customer): bool
{
    if (
        !empty($customer['hasActiveLease']) ||
        !empty($customer['hasLease']) ||
        !empty($customer['hasTenant'])
    ) {
        return true;
    }

    return false;
}

/*
|--------------------------------------------------------------------------
| Handle POST actions
|--------------------------------------------------------------------------
*/

$flashMessage = $_SESSION['units_flash_message'] ?? '';
$flashType    = $_SESSION['units_flash_type'] ?? '';

unset(
    $_SESSION['units_flash_message'],
    $_SESSION['units_flash_type']
);

/*
|--------------------------------------------------------------------------
| Create Unit
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'create_unit'
) {

    $propertyId = trim(
        (string)($_POST['propertyId'] ?? '')
    );

    $unitNumber = trim(
        (string)($_POST['unitNumber'] ?? '')
    );

    $rent = trim(
        (string)($_POST['rent'] ?? '')
    );

    $status = trim(
        (string)($_POST['status'] ?? 'Vacant')
    );

    if (
        $propertyId === '' ||
        $unitNumber === '' ||
        $rent === ''
    ) {

        $_SESSION['units_flash_message'] =
            'Please complete all required unit fields.';

        $_SESSION['units_flash_type'] = 'error';

        header('Location: units.php');
        exit;
    }

    $payload = [
        'unitNumber' => $unitNumber,
        'propertyId' => $propertyId,
        'rent'       => (float)$rent,
        'status'     => $status
    ];

    try {

        $response = api_post(
            '/units',
            $payload
        );

        if (
            is_array($response) &&
            !empty($response['success'])
        ) {

            $_SESSION['units_flash_message'] =
                'Unit created successfully.';

            $_SESSION['units_flash_type'] =
                'success';

        } else {

            $_SESSION['units_flash_message'] =
                is_array($response)
                    ? (
                        $response['message']
                        ?? 'Unable to create unit.'
                    )
                    : 'Unable to create unit.';

            $_SESSION['units_flash_type'] =
                'error';
        }

    } catch (Throwable $e) {

        error_log(
            'PropertyPro create unit error: ' .
            $e->getMessage()
        );

        $_SESSION['units_flash_message'] =
            'Unable to create the unit. Please try again.';

        $_SESSION['units_flash_type'] =
            'error';
    }

    header('Location: units.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Assign Customer
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'assign_customer'
) {

    $unitId = trim(
        (string)($_POST['unitId'] ?? '')
    );

    $customerId = trim(
        (string)($_POST['customerId'] ?? '')
    );

    if (
        $unitId === '' ||
        $customerId === ''
    ) {

        $_SESSION['units_flash_message'] =
            'Please select both a unit and a customer.';

        $_SESSION['units_flash_type'] =
            'error';

        header('Location: units.php');
        exit;
    }

    $payload = [
        'unitId'     => $unitId,
        'customerId' => $customerId
    ];

    try {

        $response = api_post(
            '/leases/assign-customer',
            $payload
        );

        if (
            is_array($response) &&
            !empty($response['success'])
        ) {

            $_SESSION['units_flash_message'] =
                'Customer assigned to the unit successfully.';

            $_SESSION['units_flash_type'] =
                'success';

        } else {

            $_SESSION['units_flash_message'] =
                is_array($response)
                    ? (
                        $response['message']
                        ?? 'Unable to assign customer.'
                    )
                    : 'Unable to assign customer.';

            $_SESSION['units_flash_type'] =
                'error';
        }

    } catch (Throwable $e) {

        error_log(
            'PropertyPro assign customer error: ' .
            $e->getMessage()
        );

        $_SESSION['units_flash_message'] =
            'Unable to assign customer. Please try again.';

        $_SESSION['units_flash_type'] =
            'error';
    }

    header('Location: units.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Calculate statistics
|--------------------------------------------------------------------------
*/

$totalUnits = count($units);

$occupiedUnits = 0;
$vacantUnits   = 0;
$totalRent     = 0;

foreach ($units as $unit) {

    if (!is_array($unit)) {
        continue;
    }

    $status = pp_units_status($unit);

    if ($status === 'Occupied') {
        $occupiedUnits++;
    } else {
        $vacantUnits++;
    }

    $totalRent += pp_units_rent($unit);
}

$occupancyRate =
    $totalUnits > 0
        ? round(
            ($occupiedUnits / $totalUnits) * 100
        )
        : 0;

/*
|--------------------------------------------------------------------------
| Prepare active properties
|--------------------------------------------------------------------------
*/

$activeProperties = [];

foreach ($properties as $property) {

    if (!is_array($property)) {
        continue;
    }

    $propertyStatus = strtolower(
        trim(
            (string)(
                $property['status']
                ?? 'active'
            )
        )
    );

    if (
        in_array(
            $propertyStatus,
            ['active', 'available'],
            true
        )
    ) {
        $activeProperties[] = $property;
    }
}

/*
|--------------------------------------------------------------------------
| Prepare vacant units
|--------------------------------------------------------------------------
*/

$vacantUnitOptions = [];

foreach ($units as $unit) {

    if (!is_array($unit)) {
        continue;
    }

    if (
        pp_units_status($unit) === 'Vacant'
    ) {

        $vacantUnitOptions[] = $unit;
    }
}

/*
|--------------------------------------------------------------------------
| Load layout
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

?>

<main class="min-h-screen bg-slate-50 lg:ml-64">

    <div class="px-4 py-6 sm:px-6 lg:px-8">

        <!-- Page Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Units
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Manage rental units, occupancy and customer assignments.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">

                <button
                    type="button"
                    onclick="openCreateUnitModal()"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>

                    Add Unit
                </button>

                <button
                    type="button"
                    onclick="openAssignCustomerModal()"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m7-8a4 4 0 100-8 4 4 0 000 8zm7-3h4m-2-2v4"
                        />
                    </svg>

                    Assign Customer
                </button>

            </div>
        </div>

        <!-- Load warning -->
        <?php if (!empty($loadErrors)): ?>

            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4">

                <div class="flex gap-3">

                    <svg
                        class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01M10.29 3.86l-8.82 15a1 1 0 001.71 1.78h17.64a1 1 0 001.71-1.78l-8.82-15a1 1 0 00-3.42 0z"
                        />
                    </svg>

                    <div>
                        <p class="font-semibold text-amber-800">
                            Some information could not be loaded.
                        </p>

                        <p class="mt-1 text-sm text-amber-700">
                            The Units page is still available, but some
                            information may be incomplete.
                        </p>
                    </div>

                </div>

            </div>

        <?php endif; ?>

        <!-- Flash message -->
        <?php if ($flashMessage !== ''): ?>

            <div
                class="mb-6 rounded-xl border px-4 py-3 <?= $flashType === 'success'
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                    : 'border-red-200 bg-red-50 text-red-800' ?>"
            >
                <?= pp_units_e($flashMessage) ?>
            </div>

        <?php endif; ?>

        <!-- Statistics -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            <!-- Total Units -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Total Units
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            <?= pp_units_e($totalUnits) ?>
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">

                        <svg
                            class="h-6 w-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 21h18M5 21V5a2 2 0 012-2h10a2 2 0 012 2v16M9 7h2m-2 4h2m2-4h2m-2 4h2M9 21v-4h6v4"
                            />
                        </svg>

                    </div>

                </div>

            </div>

            <!-- Occupied -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Occupied
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            <?= pp_units_e($occupiedUnits) ?>
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">

                        <svg
                            class="h-6 w-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>

                    </div>

                </div>

            </div>

            <!-- Vacant -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Vacant
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            <?= pp_units_e($vacantUnits) ?>
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-amber-50 text-amber-600">

                        <svg
                            class="h-6 w-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>

                    </div>

                </div>

            </div>

            <!-- Occupancy -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Occupancy Rate
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            <?= pp_units_e($occupancyRate) ?>%
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50 text-blue-600">

                        <svg
                            class="h-6 w-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 19V6l12-3v13M9 19a3 3 0 11-6 0 3 3 0 016 0zm12-3a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>

                    </div>

                </div>

            </div>

        </div>

        <!-- Units table -->
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">
                            Rental Units
                        </h2>

                        <p class="text-sm text-slate-500">
                            <?= pp_units_e($totalUnits) ?>
                            unit<?= $totalUnits === 1 ? '' : 's' ?>
                            registered
                        </p>
                    </div>

                    <div class="relative">

                        <input
                            type="text"
                            id="unitSearch"
                            placeholder="Search units..."
                            class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-10 pr-4 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 sm:w-64"
                            oninput="filterUnits()"
                        >

                        <svg
                            class="absolute left-3 top-2.5 h-5 w-5 text-slate-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-4.35-4.35m2.35-5.65a8 8 0 11-16 0 8 8 0 0116 0z"
                            />
                        </svg>

                    </div>

                </div>

            </div>

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-50">

                        <tr>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Unit
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Property
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Tenant
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Monthly Rent
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Status
                            </th>

                        </tr>

                    </thead>

                    <tbody
                        id="unitsTableBody"
                        class="divide-y divide-slate-200 bg-white"
                    >

                        <?php if (empty($units)): ?>

                            <tr>

                                <td
                                    colspan="5"
                                    class="px-5 py-12 text-center"
                                >

                                    <div class="mx-auto max-w-sm">

                                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">

                                            <svg
                                                class="h-7 w-7 text-slate-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M3 21h18M5 21V5a2 2 0 012-2h10a2 2 0 012 2v16"
                                                />
                                            </svg>

                                        </div>

                                        <h3 class="mt-4 text-sm font-semibold text-slate-900">
                                            No units found
                                        </h3>

                                        <p class="mt-1 text-sm text-slate-500">
                                            Add your first rental unit to get started.
                                        </p>

                                        <button
                                            type="button"
                                            onclick="openCreateUnitModal()"
                                            class="mt-4 inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                                        >
                                            Add Unit
                                        </button>

                                    </div>

                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($units as $unit): ?>

                                <?php

                                if (!is_array($unit)) {
                                    continue;
                                }

                                $unitId = pp_units_id(
                                    $unit['_id']
                                    ?? $unit['id']
                                    ?? ''
                                );

                                $unitNumber =
                                    pp_units_number($unit);

                                $propertyName =
                                    pp_units_property_name($unit);

                                $tenantName =
                                    pp_units_tenant_name($unit);

                                $rent =
                                    pp_units_rent($unit);

                                $status =
                                    pp_units_status($unit);

                                $searchText = strtolower(
                                    $unitNumber . ' ' .
                                    $propertyName . ' ' .
                                    $tenantName . ' ' .
                                    $status
                                );

                                ?>

                                <tr
                                    class="unit-row hover:bg-slate-50"
                                    data-search="<?= pp_units_e($searchText) ?>"
                                >

                                    <td class="whitespace-nowrap px-5 py-4">

                                        <div class="font-semibold text-slate-900">
                                            <?= pp_units_e($unitNumber) ?>
                                        </div>

                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                        <?= pp_units_e($propertyName) ?>
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4">

                                        <?php if ($tenantName !== 'Vacant'): ?>

                                            <span class="text-sm font-medium text-slate-800">
                                                <?= pp_units_e($tenantName) ?>
                                            </span>

                                        <?php else: ?>

                                            <span class="text-sm text-slate-400">
                                                Vacant
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-800">

                                        KSh <?= number_format($rent, 2) ?>

                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4">

                                        <?php if ($status === 'Occupied'): ?>

                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                Occupied
                                            </span>

                                        <?php else: ?>

                                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                                Vacant
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</main>

<!-- =========================================================
     CREATE UNIT MODAL
========================================================= -->

<div
    id="createUnitModal"
    class="fixed inset-0 z-50 hidden"
    aria-hidden="true"
>

    <div
        class="absolute inset-0 bg-slate-900/50"
        onclick="closeCreateUnitModal()"
    ></div>

    <div class="relative flex min-h-full items-center justify-center p-4">

        <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">

            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">

                <div>
                    <h2 class="text-lg font-bold text-slate-900">
                        Add Rental Unit
                    </h2>

                    <p class="text-sm text-slate-500">
                        Create a new unit under a property.
                    </p>
                </div>

                <button
                    type="button"
                    onclick="closeCreateUnitModal()"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>

            </div>

            <form
                method="POST"
                action="units.php"
                class="space-y-5 p-6"
            >

                <input
                    type="hidden"
                    name="action"
                    value="create_unit"
                >

                <div>

                    <label
                        for="propertyId"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        Property
                    </label>

                    <select
                        id="propertyId"
                        name="propertyId"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                        <option value="">
                            Select property
                        </option>

                        <?php foreach ($activeProperties as $property): ?>

                            <?php

                            $propertyId = pp_units_id(
                                $property['_id']
                                ?? $property['id']
                                ?? ''
                            );

                            $propertyName =
                                $property['name']
                                ?? $property['propertyName']
                                ?? $property['title']
                                ?? 'Unnamed Property';

                            ?>

                            <?php if ($propertyId !== ''): ?>

                                <option value="<?= pp_units_e($propertyId) ?>">
                                    <?= pp_units_e($propertyName) ?>
                                </option>

                            <?php endif; ?>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div>

                    <label
                        for="unitNumber"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        Unit Number
                    </label>

                    <input
                        type="text"
                        id="unitNumber"
                        name="unitNumber"
                        required
                        placeholder="e.g. A-101"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                </div>

                <div>

                    <label
                        for="rent"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        Monthly Rent
                    </label>

                    <input
                        type="number"
                        id="rent"
                        name="rent"
                        min="0"
                        step="0.01"
                        required
                        placeholder="25000"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                </div>

                <div>

                    <label
                        for="status"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                        <option value="Vacant">
                            Vacant
                        </option>

                        <option value="Occupied">
                            Occupied
                        </option>

                    </select>

                </div>

                <div class="flex justify-end gap-3 border-t border-slate-200 pt-5">

                    <button
                        type="button"
                        onclick="closeCreateUnitModal()"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        Create Unit
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- =========================================================
     ASSIGN CUSTOMER MODAL
========================================================= -->

<div
    id="assignCustomerModal"
    class="fixed inset-0 z-50 hidden"
    aria-hidden="true"
>

    <div
        class="absolute inset-0 bg-slate-900/50"
        onclick="closeAssignCustomerModal()"
    ></div>

    <div class="relative flex min-h-full items-center justify-center p-4">

        <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">

            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">

                <div>
                    <h2 class="text-lg font-bold text-slate-900">
                        Assign Customer
                    </h2>

                    <p class="text-sm text-slate-500">
                        Assign a customer to a vacant unit.
                    </p>
                </div>

                <button
                    type="button"
                    onclick="closeAssignCustomerModal()"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>

            </div>

            <form
                method="POST"
                action="units.php"
                class="space-y-5 p-6"
            >

                <input
                    type="hidden"
                    name="action"
                    value="assign_customer"
                >

                <div>

                    <label
                        for="assignUnitId"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        Vacant Unit
                    </label>

                    <select
                        id="assignUnitId"
                        name="unitId"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                        <option value="">
                            Select vacant unit
                        </option>

                        <?php foreach ($vacantUnitOptions as $unit): ?>

                            <?php

                            $unitId = pp_units_id(
                                $unit['_id']
                                ?? $unit['id']
                                ?? ''
                            );

                            $unitNumber =
                                pp_units_number($unit);

                            $propertyName =
                                pp_units_property_name($unit);

                            ?>

                            <?php if ($unitId !== ''): ?>

                                <option value="<?= pp_units_e($unitId) ?>">
                                    <?= pp_units_e($unitNumber) ?>
                                    -
                                    <?= pp_units_e($propertyName) ?>
                                </option>

                            <?php endif; ?>

                        <?php endforeach; ?>

                    </select>

                    <?php if (empty($vacantUnitOptions)): ?>

                        <p class="mt-1.5 text-xs text-amber-600">
                            There are currently no vacant units available.
                        </p>

                    <?php endif; ?>

                </div>

                <div>

                    <label
                        for="customerId"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        Customer
                    </label>

                    <select
                        id="customerId"
                        name="customerId"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                        <option value="">
                            Select customer
                        </option>

                        <?php foreach ($customers as $customer): ?>

                            <?php

                            if (!is_array($customer)) {
                                continue;
                            }

                            $customerId = pp_units_id(
                                $customer['_id']
                                ?? $customer['id']
                                ?? ''
                            );

                            ?>

                            <?php if ($customerId !== ''): ?>

                                <option value="<?= pp_units_e($customerId) ?>">
                                    <?= pp_units_e(
                                        pp_units_customer_name($customer)
                                    ) ?>
                                </option>

                            <?php endif; ?>

                        <?php endforeach; ?>

                    </select>

                    <?php if (empty($customers)): ?>

                        <p class="mt-1.5 text-xs text-amber-600">
                            No customers were returned by the API.
                        </p>

                    <?php endif; ?>

                </div>

                <div class="flex justify-end gap-3 border-t border-slate-200 pt-5">

                    <button
                        type="button"
                        onclick="closeAssignCustomerModal()"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        <?= empty($vacantUnitOptions) || empty($customers)
                            ? 'disabled'
                            : '' ?>
                        class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Assign Customer
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<script>

/*
|--------------------------------------------------------------------------
| Create Unit Modal
|--------------------------------------------------------------------------
*/

function openCreateUnitModal() {
    const modal = document.getElementById('createUnitModal');

    if (!modal) {
        return;
    }

    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');

    document.body.classList.add('overflow-hidden');
}

function closeCreateUnitModal() {
    const modal = document.getElementById('createUnitModal');

    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');

    document.body.classList.remove('overflow-hidden');
}

/*
|--------------------------------------------------------------------------
| Assign Customer Modal
|--------------------------------------------------------------------------
*/

function openAssignCustomerModal() {
    const modal = document.getElementById('assignCustomerModal');

    if (!modal) {
        return;
    }

    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');

    document.body.classList.add('overflow-hidden');
}

function closeAssignCustomerModal() {
    const modal = document.getElementById('assignCustomerModal');

    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');

    document.body.classList.remove('overflow-hidden');
}

/*
|--------------------------------------------------------------------------
| Unit Search
|--------------------------------------------------------------------------
*/

function filterUnits() {

    const input = document.getElementById('unitSearch');

    if (!input) {
        return;
    }

    const search = input.value
        .toLowerCase()
        .trim();

    const rows = document.querySelectorAll('.unit-row');

    rows.forEach(function (row) {

        const text =
            row.dataset.search || '';

        if (
            search === '' ||
            text.includes(search)
        ) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }

    });
}

/*
|--------------------------------------------------------------------------
| Escape key closes modals
|--------------------------------------------------------------------------
*/

document.addEventListener('keydown', function (event) {

    if (event.key !== 'Escape') {
        return;
    }

    closeCreateUnitModal();
    closeAssignCustomerModal();

});

</script>

<?php

require_once __DIR__ . '/../../includes/footer.php';
?>