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
| Helpers
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

/**
 * Get units directly from the API.
 *
 * The API response is:
 *
 * [
 *     'success' => true,
 *     'count'   => 8,
 *     'data'    => [
 *         ...
 *     ]
 * ]
 */
function pp_units_get(): array
{
    try {

        $response = api_get('/units');

        if (!is_array($response)) {
            error_log(
                'PropertyPro Units: Invalid API response.'
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
            'PropertyPro Units API error: ' .
            $e->getMessage()
        );

        return [
            'success' => false,
            'data' => [],
            'message' => 'Unable to load units.'
        ];
    }
}

/**
 * Convert API data into a simple units array.
 */
function pp_units_rows(array $response): array
{
    $data = $response['data'] ?? [];

    if (!is_array($data)) {
        return [];
    }

    /*
     * The /units endpoint returns the units directly
     * inside data.
     */
    return array_values(
        array_filter(
            $data,
            static function ($unit): bool {
                return is_array($unit);
            }
        )
    );
}

/**
 * Extract MongoDB/API ID.
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

/**
 * Unit number.
 */
function pp_units_number(array $unit): string
{
    return trim(
        (string)(
            $unit['unitNumber']
            ?? $unit['number']
            ?? $unit['name']
            ?? $unit['unitName']
            ?? 'N/A'
        )
    );
}

/**
 * Property name.
 *
 * In the actual API response propertyId is a populated
 * object containing name and location.
 */
function pp_units_property_name(array $unit): string
{
    $property = $unit['propertyId'] ?? null;

    if (is_array($property)) {

        $name = trim(
            (string)(
                $property['name']
                ?? $property['propertyName']
                ?? $property['title']
                ?? ''
            )
        );

        if ($name !== '') {
            return $name;
        }
    }

    if (!empty($unit['propertyName'])) {
        return (string)$unit['propertyName'];
    }

    return 'Unknown Property';
}

/**
 * Property location.
 */
function pp_units_property_location(array $unit): string
{
    $property = $unit['propertyId'] ?? null;

    if (is_array($property)) {

        $location = trim(
            (string)(
                $property['location']
                ?? ''
            )
        );

        if ($location !== '') {
            return $location;
        }
    }

    if (!empty($unit['location'])) {
        return (string)$unit['location'];
    }

    return '';
}

/**
 * Tenant name.
 *
 * In the actual API response tenantId is a populated
 * object containing name, email and phone.
 */
function pp_units_tenant_name(array $unit): string
{
    $tenant = $unit['tenantId'] ?? null;

    if (is_array($tenant)) {

        $name = trim(
            (string)(
                $tenant['name']
                ?? $tenant['fullName']
                ?? ''
            )
        );

        if ($name !== '') {
            return $name;
        }

        $fullName = trim(
            (string)($tenant['firstName'] ?? '') .
            ' ' .
            (string)($tenant['lastName'] ?? '')
        );

        if ($fullName !== '') {
            return $fullName;
        }
    }

    if (!empty($unit['tenantName'])) {
        return (string)$unit['tenantName'];
    }

    return 'Vacant';
}

/**
 * Tenant email.
 */
function pp_units_tenant_email(array $unit): string
{
    $tenant = $unit['tenantId'] ?? null;

    if (is_array($tenant) && !empty($tenant['email'])) {
        return (string)$tenant['email'];
    }

    return '';
}

/**
 * Tenant phone.
 */
function pp_units_tenant_phone(array $unit): string
{
    $tenant = $unit['tenantId'] ?? null;

    if (is_array($tenant) && !empty($tenant['phone'])) {
        return (string)$tenant['phone'];
    }

    return '';
}

/**
 * Unit status.
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

    return 'Vacant';
}

/**
 * Unit rent.
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
| Load units
|--------------------------------------------------------------------------
*/

$unitsResponse = pp_units_get();

$units = pp_units_rows($unitsResponse);

$loadError = (
    ($unitsResponse['success'] ?? false) !== true
);

/*
|--------------------------------------------------------------------------
| Handle flash messages
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

        $_SESSION['units_flash_type'] =
            'error';

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
| Vacant units
|--------------------------------------------------------------------------
*/

$vacantUnitOptions = [];

foreach ($units as $unit) {

    if (
        pp_units_status($unit) === 'Vacant'
    ) {
        $vacantUnitOptions[] = $unit;
    }
}

/*
|--------------------------------------------------------------------------
| Layout
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

    <!-- API warning -->
    <?php if ($loadError): ?>

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
                        d="M12 9v2m0 4h.01M10.29 3.86l-8.82 15a2 2 0 001.71 3h17.64a2 2 0 001.71-3l-8.82-15a2 2 0 00-3.42 0z"
                    />
                </svg>

                <div>

                    <p class="font-semibold text-amber-800">
                        Units could not be loaded.
                    </p>

                    <p class="mt-1 text-sm text-amber-700">
                        Please refresh the page or check the API connection.
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

        <!-- Total -->
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

                            $unitId = pp_units_id(
                                $unit['_id'] ?? ''
                            );

                            $unitNumber =
                                pp_units_number($unit);

                            $propertyName =
                                pp_units_property_name($unit);

                            $propertyLocation =
                                pp_units_property_location($unit);

                            $tenantName =
                                pp_units_tenant_name($unit);

                            $tenantEmail =
                                pp_units_tenant_email($unit);

                            $tenantPhone =
                                pp_units_tenant_phone($unit);

                            $rent =
                                pp_units_rent($unit);

                            $status =
                                pp_units_status($unit);

                            $searchText = strtolower(
                                $unitNumber . ' ' .
                                $propertyName . ' ' .
                                $propertyLocation . ' ' .
                                $tenantName . ' ' .
                                $tenantEmail . ' ' .
                                $status
                            );

                            ?>

                            <tr
                                class="unit-row hover:bg-slate-50"
                                data-search="<?= pp_units_e($searchText) ?>"
                            >

                                <!-- Unit -->
                                <td class="whitespace-nowrap px-5 py-4">

                                    <div class="font-semibold text-slate-900">
                                        <?= pp_units_e($unitNumber) ?>
                                    </div>

                                    <div class="mt-0.5 text-xs text-slate-400">
                                        <?= pp_units_e($unit['type'] ?? '') ?>
                                    </div>

                                </td>

                                <!-- Property -->
                                <td class="px-5 py-4">

                                    <div class="whitespace-nowrap text-sm font-medium text-slate-800">
                                        <?= pp_units_e($propertyName) ?>
                                    </div>

                                    <?php if ($propertyLocation !== ''): ?>

                                        <div class="mt-0.5 whitespace-nowrap text-xs text-slate-400">
                                            <?= pp_units_e($propertyLocation) ?>
                                        </div>

                                    <?php endif; ?>

                                </td>

                                <!-- Tenant -->
                                <td class="px-5 py-4">

                                    <?php if ($tenantName !== 'Vacant'): ?>

                                        <div class="whitespace-nowrap text-sm font-medium text-slate-800">
                                            <?= pp_units_e($tenantName) ?>
                                        </div>

                                        <?php if ($tenantEmail !== ''): ?>

                                            <div class="mt-0.5 text-xs text-slate-400">
                                                <?= pp_units_e($tenantEmail) ?>
                                            </div>

                                        <?php elseif ($tenantPhone !== ''): ?>

                                            <div class="mt-0.5 text-xs text-slate-400">
                                                <?= pp_units_e($tenantPhone) ?>
                                            </div>

                                        <?php endif; ?>

                                    <?php else: ?>

                                        <span class="text-sm text-slate-400">
                                            Vacant
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <!-- Rent -->
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-800">

                                    KSh <?= number_format($rent, 2) ?>

                                </td>

                                <!-- Status -->
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

                <input
                    type="text"
                    name="propertyId"
                    id="propertyId"
                    required
                    placeholder="Enter property ID"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                >

                <p class="mt-1.5 text-xs text-slate-400">
                    Enter the property's API ID.
                </p>

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

```
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

        <div class="space-y-5 p-6">

            <div>

                <label
                    for="assignUnitId"
                    class="mb-1.5 block text-sm font-semibold text-slate-700"
                >
                    Vacant Unit
                </label>

                <select
                    id="assignUnitId"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                >

                    <option value="">
                        Select vacant unit
                    </option>

                    <?php foreach ($vacantUnitOptions as $unit): ?>

                        <?php

                        $unitId = pp_units_id(
                            $unit['_id'] ?? ''
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
                    Customer ID
                </label>

                <input
                    type="text"
                    id="customerId"
                    placeholder="Enter customer ID"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                >

                <p class="mt-1.5 text-xs text-slate-400">
                    Customer assignment can be completed once a valid customer ID is provided.
                </p>

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
                    type="button"
                    onclick="submitAssignCustomer()"
                    <?= empty($vacantUnitOptions) ? 'disabled' : '' ?>
                    class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Assign Customer
                </button>

            </div>

        </div>

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

    const modal =
        document.getElementById('createUnitModal');

    if (!modal) {
        return;
    }

    modal.classList.remove('hidden');

    modal.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.classList.add(
        'overflow-hidden'
    );
}

function closeCreateUnitModal() {

    const modal =
        document.getElementById('createUnitModal');

    if (!modal) {
        return;
    }

    modal.classList.add('hidden');

    modal.setAttribute(
        'aria-hidden',
        'true'
    );

    document.body.classList.remove(
        'overflow-hidden'
    );
}

/*
|--------------------------------------------------------------------------
| Assign Customer Modal
|--------------------------------------------------------------------------
*/

function openAssignCustomerModal() {

    const modal =
        document.getElementById('assignCustomerModal');

    if (!modal) {
        return;
    }

    modal.classList.remove('hidden');

    modal.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.classList.add(
        'overflow-hidden'
    );
}

function closeAssignCustomerModal() {

    const modal =
        document.getElementById('assignCustomerModal');

    if (!modal) {
        return;
    }

    modal.classList.add('hidden');

    modal.setAttribute(
        'aria-hidden',
        'true'
    );

    document.body.classList.remove(
        'overflow-hidden'
    );
}

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

function filterUnits() {

    const input =
        document.getElementById('unitSearch');

    if (!input) {
        return;
    }

    const search =
        input.value
            .toLowerCase()
            .trim();

    const rows =
        document.querySelectorAll('.unit-row');

    rows.forEach(function(row) {

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
| Assign customer
|--------------------------------------------------------------------------
*/

function submitAssignCustomer() {

    const unitSelect =
        document.getElementById('assignUnitId');

    const customerInput =
        document.getElementById('customerId');

    if (!unitSelect || !customerInput) {
        return;
    }

    const unitId =
        unitSelect.value.trim();

    const customerId =
        customerInput.value.trim();

    if (unitId === '') {

        alert(
            'Please select a vacant unit.'
        );

        return;
    }

    if (customerId === '') {

        alert(
            'Please enter the customer ID.'
        );

        return;
    }

    const form =
        document.createElement('form');

    form.method = 'POST';
    form.action = 'units.php';

    const action =
        document.createElement('input');

    action.type = 'hidden';
    action.name = 'action';
    action.value = 'assign_customer';

    const unit =
        document.createElement('input');

    unit.type = 'hidden';
    unit.name = 'unitId';
    unit.value = unitId;

    const customer =
        document.createElement('input');

    customer.type = 'hidden';
    customer.name = 'customerId';
    customer.value = customerId;

    form.appendChild(action);
    form.appendChild(unit);
    form.appendChild(customer);

    document.body.appendChild(form);

    form.submit();
}

/*
|--------------------------------------------------------------------------
| Escape key
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'keydown',
    function(event) {

        if (event.key !== 'Escape') {
            return;
        }

        closeCreateUnitModal();
        closeAssignCustomerModal();

    }
);

</script>

<?php

require_once __DIR__ . '/../../includes/footer.php';

?>
