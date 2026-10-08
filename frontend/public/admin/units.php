<?php

/**
 * ============================================================
 * PropertyPro - Admin Units
 * ============================================================
 */

require_once __DIR__ . '/../../includes/api.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

$pageTitle = 'Units';

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function pp_units_e($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function pp_units_money($value): string
{
    return 'KES ' . number_format(
        (float)($value ?? 0),
        2
    );
}

/**
 * Treat an API response as successful if:
 *  - HTTP status is 2xx, AND
 *  - if the API explicitly includes a `success` key, it must be truthy.
 */
function pp_api_ok(array $response): bool
{
    $status = (int)($response['status'] ?? 0);
    $httpOk = $status >= 200 && $status < 300;

    if (array_key_exists('success', $response)) {
        return $httpOk && (bool)$response['success'];
    }

    return $httpOk;
}

/**
 * Extract a human-readable error message from an API response.
 */
function pp_api_error(array $response, string $fallback = 'Request failed.'): string
{
    foreach (['message', 'error', 'detail', 'msg'] as $key) {
        if (!empty($response[$key]) && is_string($response[$key])) {
            return $response[$key];
        }
    }

    $status = (int)($response['status'] ?? 0);

    return $status > 0
        ? $fallback . ' (HTTP ' . $status . ')'
        : $fallback;
}

/**
 * Extract a list payload from various possible response shapes.
 */
function pp_api_list(array $response, array $keys = ['data']): array
{
    foreach ($keys as $key) {
        if (isset($response[$key]) && is_array($response[$key])) {
            return array_values(
                array_filter(
                    $response[$key],
                    static fn($row): bool => is_array($row)
                )
            );
        }
    }

    $isList = array_keys($response) === range(0, count($response) - 1);

    if ($isList) {
        return array_values(
            array_filter(
                $response,
                static fn($row): bool => is_array($row)
            )
        );
    }

    return [];
}

/**
 * Pick the correct ID to send to the backend.
 *
 * The backend uses MongoDB and casts IDs to ObjectId. Only `_id` (or `id`)
 * will satisfy that cast. Never send `propertyId` (a human code like
 * "PROP-003") as a value — it causes:
 *   CastError: Cast to ObjectId failed for value "PROP-003" at path "_id"
 */
function pp_backend_id(array $row, array $keys = ['_id', 'id']): string
{
    foreach ($keys as $key) {
        if (!empty($row[$key]) && is_string($row[$key])) {
            return $row[$key];
        }
    }

    return '';
}

/*
|--------------------------------------------------------------------------
| Load Units
|--------------------------------------------------------------------------
*/

$units = [];

try {
    $unitsResponse = api_get('/units');

    if (is_array($unitsResponse)) {
        $units = pp_api_list($unitsResponse, ['data', 'units']);
    }

    if (!pp_api_ok($unitsResponse)) {
        error_log(
            'PropertyPro Units API load failed: ' .
            json_encode($unitsResponse)
        );
    }
} catch (Throwable $e) {
    error_log(
        'PropertyPro Units API Error: ' . $e->getMessage()
    );
}

/*
|--------------------------------------------------------------------------
| Load Properties
|--------------------------------------------------------------------------
*/

$properties = [];

try {
    $propertiesResponse = api_get('/properties');

    if (is_array($propertiesResponse)) {
        $properties = pp_api_list($propertiesResponse, ['data', 'properties']);
    }

    if (!pp_api_ok($propertiesResponse)) {
        error_log(
            'PropertyPro Properties API load failed: ' .
            json_encode($propertiesResponse)
        );
    }
} catch (Throwable $e) {
    error_log(
        'PropertyPro Properties API Error: ' . $e->getMessage()
    );
}

/*
|--------------------------------------------------------------------------
| Helper Functions For Unit Data
|--------------------------------------------------------------------------
*/

function pp_unit_id(array $unit): string
{
    return pp_backend_id($unit, ['_id', 'id', 'unitId']);
}

function pp_unit_number(array $unit): string
{
    return trim(
        (string)(
            $unit['unitNumber']
            ?? $unit['number']
            ?? 'N/A'
        )
    );
}

function pp_unit_property(array $unit): string
{
    $property = $unit['propertyId'] ?? null;

    if (is_array($property)) {
        return trim(
            (string)(
                $property['name']
                ?? $property['propertyName']
                ?? $property['title']
                ?? 'Unknown Property'
            )
        );
    }

    return trim(
        (string)(
            $unit['propertyName']
            ?? 'Unknown Property'
        )
    );
}

function pp_unit_location(array $unit): string
{
    $property = $unit['propertyId'] ?? null;

    if (is_array($property)) {
        return trim((string)($property['location'] ?? ''));
    }

    return trim((string)($unit['location'] ?? ''));
}

function pp_unit_tenant(array $unit): string
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

    return trim(
        (string)(
            $unit['tenantName']
            ?? 'Vacant'
        )
    );
}

function pp_unit_status(array $unit): string
{
    $status = strtolower(
        trim(
            (string)(
                $unit['status']
                ?? $unit['unitStatus']
                ?? 'Vacant'
            )
        )
    );

    if (in_array($status, ['occupied', 'rented', 'leased'], true)) {
        return 'Occupied';
    }

    return 'Vacant';
}

function pp_unit_rent(array $unit): float
{
    return (float)(
        $unit['rent']
        ?? $unit['monthlyRent']
        ?? 0
    );
}

/*
|--------------------------------------------------------------------------
| Flash Messages
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
| CREATE UNIT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'create_unit'
) {

    $propertyId = trim((string)($_POST['propertyId'] ?? ''));
    $unitNumber = trim((string)($_POST['unitNumber'] ?? ''));
    $rent       = trim((string)($_POST['rent'] ?? ''));
    $status     = trim((string)($_POST['status'] ?? 'Vacant'));

    if ($propertyId === '' || $unitNumber === '' || $rent === '') {
        $_SESSION['units_flash_message'] =
            'Property, Unit number and rent are required.';
        $_SESSION['units_flash_type'] = 'error';

        header('Location: units.php');
        exit;
    }

    /*
    |----------------------------------------------------------------------
    | AUTOMATIC UNIT ID (client-side generation)
    |----------------------------------------------------------------------
    | If your backend rejects client-sent `unitId`, remove that field
    | from the payload below.
    */

    try {
        $unitId =
            'UNIT-' .
            date('YmdHis') .
            '-' .
            strtoupper(bin2hex(random_bytes(3)));
    } catch (Throwable $e) {
        $unitId =
            'UNIT-' .
            date('YmdHis') .
            '-' .
            strtoupper(substr(uniqid(), -6));
    }

    $payload = [
        'unitId'     => $unitId,
        'propertyId' => $propertyId,
        'unitNumber' => $unitNumber,
        'rent'       => (float)$rent,
        'status'     => $status,
    ];

    try {
        $response = api_post('/units', $payload);

        error_log('CREATE UNIT payload: ' . json_encode($payload));
        error_log('CREATE UNIT response: ' . json_encode($response));

        if (pp_api_ok($response)) {
            $_SESSION['units_flash_message'] =
                'Unit created successfully.';
            $_SESSION['units_flash_type'] = 'success';
        } else {
            $_SESSION['units_flash_message'] =
                pp_api_error($response, 'Unable to create unit.');
            $_SESSION['units_flash_type'] = 'error';
        }
    } catch (Throwable $e) {
        error_log(
            'PropertyPro Create Unit Error: ' . $e->getMessage()
        );

        $_SESSION['units_flash_message'] =
            'Unable to create unit. Please try again.';
        $_SESSION['units_flash_type'] = 'error';
    }

    header('Location: units.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| ASSIGN CUSTOMER
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'assign_customer'
) {

    $unitId     = trim((string)($_POST['unitId'] ?? ''));
    $customerId = trim((string)($_POST['customerId'] ?? ''));

    if ($unitId === '' || $customerId === '') {
        $_SESSION['units_flash_message'] =
            'Unit and Customer ID are required.';
        $_SESSION['units_flash_type'] = 'error';

        header('Location: units.php');
        exit;
    }

    try {
        $response = api_post(
            '/leases/assign-customer',
            [
                'unitId'     => $unitId,
                'customerId' => $customerId,
            ]
        );

        error_log('ASSIGN CUSTOMER payload: ' . json_encode([
            'unitId'     => $unitId,
            'customerId' => $customerId,
        ]));
        error_log('ASSIGN CUSTOMER response: ' . json_encode($response));

        if (pp_api_ok($response)) {
            $_SESSION['units_flash_message'] =
                'Customer assigned successfully.';
            $_SESSION['units_flash_type'] = 'success';
        } else {
            $_SESSION['units_flash_message'] =
                pp_api_error($response, 'Unable to assign customer.');
            $_SESSION['units_flash_type'] = 'error';
        }
    } catch (Throwable $e) {
        error_log(
            'PropertyPro Assign Customer Error: ' . $e->getMessage()
        );

        $_SESSION['units_flash_message'] =
            'Unable to assign customer.';
        $_SESSION['units_flash_type'] = 'error';
    }

    header('Location: units.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

$totalUnits    = count($units);
$occupiedUnits = 0;
$vacantUnits   = 0;
$totalRent     = 0;

foreach ($units as $unit) {
    if (pp_unit_status($unit) === 'Occupied') {
        $occupiedUnits++;
    } else {
        $vacantUnits++;
    }

    $totalRent += pp_unit_rent($unit);
}

$occupancyRate =
    $totalUnits > 0
        ? round(($occupiedUnits / $totalUnits) * 100)
        : 0;

/*
|--------------------------------------------------------------------------
| Vacant Units
|--------------------------------------------------------------------------
*/

$vacantUnitsList = [];

foreach ($units as $unit) {
    if (pp_unit_status($unit) === 'Vacant') {
        $vacantUnitsList[] = $unit;
    }
}

/*
|--------------------------------------------------------------------------
| Page Layout
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

?>

<main class="min-h-screen bg-slate-50 lg:ml-64">

    <div class="px-4 py-6 sm:px-6 lg:px-8">

        <!-- =====================================================
             HEADER
        ====================================================== -->

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
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Unit
                </button>

                <button
                    type="button"
                    onclick="openAssignCustomerModal()"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m7-8a4 4 0 100-8 4 4 0 000 8zm7-3h4m-2-2v4" />
                    </svg>
                    Assign Customer
                </button>

            </div>

        </div>

        <!-- =====================================================
             FLASH MESSAGE
        ====================================================== -->

        <?php if ($flashMessage !== ''): ?>

            <div
                class="mb-6 rounded-xl border px-4 py-3 <?= $flashType === 'success'
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                    : 'border-red-200 bg-red-50 text-red-800' ?>"
            >
                <?= pp_units_e($flashMessage) ?>
            </div>

        <?php endif; ?>

        <!-- =====================================================
             STATISTICS
        ====================================================== -->

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Total Units</p>
                <p class="mt-2 text-3xl font-bold text-slate-900"><?= $totalUnits ?></p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Occupied</p>
                <p class="mt-2 text-3xl font-bold text-slate-900"><?= $occupiedUnits ?></p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Vacant</p>
                <p class="mt-2 text-3xl font-bold text-slate-900"><?= $vacantUnits ?></p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Occupancy Rate</p>
                <p class="mt-2 text-3xl font-bold text-slate-900"><?= $occupancyRate ?>%</p>
            </div>

        </div>

        <!-- =====================================================
             UNITS TABLE
        ====================================================== -->

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">
                            Rental Units
                        </h2>
                        <p class="text-sm text-slate-500">
                            <?= $totalUnits ?>
                            unit<?= $totalUnits === 1 ? '' : 's' ?>
                            registered
                        </p>
                    </div>

                    <div class="relative">
                        <input
                            type="text"
                            id="unitSearch"
                            placeholder="Search units..."
                            oninput="filterUnits()"
                            class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-10 pr-4 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 sm:w-64"
                        >
                        <svg class="absolute left-3 top-2.5 h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m2.35-5.65a8 8 0 11-16 0 8 8 0 0116 0z" />
                        </svg>
                    </div>

                </div>
            </div>

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Unit</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Property</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tenant</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Monthly Rent</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        </tr>
                    </thead>

                    <tbody id="unitsTableBody" class="divide-y divide-slate-200 bg-white">

                        <?php if (empty($units)): ?>

                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center">
                                    <div class="text-slate-500">
                                        <p class="text-sm font-semibold text-slate-900">
                                            No units found
                                        </p>
                                        <p class="mt-1 text-sm">
                                            Add your first rental unit.
                                        </p>
                                    </div>
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($units as $unit): ?>

                                <?php
                                $unitNumber   = pp_unit_number($unit);
                                $propertyName = pp_unit_property($unit);
                                $location     = pp_unit_location($unit);
                                $tenant       = pp_unit_tenant($unit);
                                $rent         = pp_unit_rent($unit);
                                $status       = pp_unit_status($unit);

                                $searchText = strtolower(
                                    $unitNumber . ' ' .
                                    $propertyName . ' ' .
                                    $location . ' ' .
                                    $tenant . ' ' .
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
                                        <?php if (!empty($unit['type'])): ?>
                                            <div class="mt-0.5 text-xs text-slate-400">
                                                <?= pp_units_e($unit['type']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="text-sm font-medium text-slate-800">
                                            <?= pp_units_e($propertyName) ?>
                                        </div>
                                        <?php if ($location !== ''): ?>
                                            <div class="mt-0.5 text-xs text-slate-400">
                                                <?= pp_units_e($location) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-5 py-4">
                                        <?php if ($tenant !== 'Vacant'): ?>
                                            <span class="text-sm font-medium text-slate-800">
                                                <?= pp_units_e($tenant) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-sm text-slate-400">
                                                Vacant
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-800">
                                        <?= pp_units_money($rent) ?>
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4">
                                        <?php if ($status === 'Occupied'): ?>
                                            <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                Occupied
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
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

<div id="createUnitModal" class="fixed inset-0 z-50 hidden">

    <div class="absolute inset-0 bg-slate-900/50" onclick="closeCreateUnitModal()"></div>

    <div class="relative flex min-h-full items-center justify-center p-4">

        <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">

            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">

                <div>
                    <h2 class="text-lg font-bold text-slate-900">
                        Add Rental Unit
                    </h2>
                    <p class="text-sm text-slate-500">
                        Create a new rental unit.
                    </p>
                </div>

                <button
                    type="button"
                    onclick="closeCreateUnitModal()"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

            </div>

            <form method="POST" action="units.php" class="space-y-5 p-6">

                <input type="hidden" name="action" value="create_unit">

                <!-- PROPERTY -->

                <div>
                    <label for="propertyId" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Property
                    </label>

                    <select
                        name="propertyId"
                        id="propertyId"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >
                        <option value="">Select property</option>

                        <?php foreach ($properties as $property): ?>

                            <?php
                            /*
                             * IMPORTANT:
                             * The backend casts this value to a MongoDB ObjectId.
                             * Only `_id` (or `id`) is valid. Sending `propertyId`
                             * (e.g. "PROP-003") causes:
                             *   CastError: Cast to ObjectId failed...
                             */
                            $optionValue = pp_backend_id(
                                $property,
                                ['_id', 'id']
                            );

                            $propertyName = trim((string)(
                                $property['name']
                                ?? $property['propertyName']
                                ?? $property['title']
                                ?? 'Unnamed Property'
                            ));

                            $propertyLocation = trim((string)(
                                $property['location'] ?? ''
                            ));
                            ?>

                            <?php if ($optionValue !== ''): ?>
                                <option value="<?= pp_units_e($optionValue) ?>">
                                    <?= pp_units_e($propertyName) ?>
                                    <?php if ($propertyLocation !== ''): ?>
                                        — <?= pp_units_e($propertyLocation) ?>
                                    <?php endif; ?>
                                </option>
                            <?php endif; ?>

                        <?php endforeach; ?>

                    </select>

                    <?php if (empty($properties)): ?>
                        <p class="mt-1.5 text-xs text-amber-600">
                            No properties are currently available.
                        </p>
                    <?php endif; ?>
                </div>

                <!-- UNIT NUMBER -->

                <div>
                    <label for="unitNumber" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Unit Number
                    </label>
                    <input
                        type="text"
                        name="unitNumber"
                        id="unitNumber"
                        required
                        placeholder="e.g. A-101"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >
                </div>

                <!-- RENT -->

                <div>
                    <label for="rent" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Monthly Rent
                    </label>
                    <input
                        type="number"
                        name="rent"
                        id="rent"
                        min="0"
                        step="0.01"
                        required
                        placeholder="25000"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >
                </div>

                <!-- STATUS -->

                <div>
                    <label for="status" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Status
                    </label>
                    <select
                        name="status"
                        id="status"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >
                        <option value="Vacant">Vacant</option>
                        <option value="Occupied">Occupied</option>
                    </select>
                </div>

                <!-- ACTIONS -->

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

<div id="assignCustomerModal" class="fixed inset-0 z-50 hidden">

    <div class="absolute inset-0 bg-slate-900/50" onclick="closeAssignCustomerModal()"></div>

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
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

            </div>

            <form method="POST" action="units.php" class="space-y-5 p-6">

                <input type="hidden" name="action" value="assign_customer">

                <!-- UNIT -->

                <div>
                    <label for="assignUnitId" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Vacant Unit
                    </label>
                    <select
                        name="unitId"
                        id="assignUnitId"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >
                        <option value="">Select vacant unit</option>

                        <?php foreach ($vacantUnitsList as $unit): ?>
                            <?php
                            $assignUnitId     = pp_unit_id($unit);
                            $assignUnitNumber = pp_unit_number($unit);
                            $assignProperty   = pp_unit_property($unit);
                            ?>

                            <?php if ($assignUnitId !== ''): ?>
                                <option value="<?= pp_units_e($assignUnitId) ?>">
                                    <?= pp_units_e($assignUnitNumber) ?>
                                    — <?= pp_units_e($assignProperty) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>

                    </select>
                </div>

                <!-- CUSTOMER -->

                <div>
                    <label for="customerId" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Customer ID
                    </label>
                    <input
                        type="text"
                        name="customerId"
                        id="customerId"
                        required
                        placeholder="Enter customer ID"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >
                </div>

                <!-- ACTIONS -->

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
                        class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        Assign Customer
                    </button>
                </div>

            </form>

        </div>

    </div>

</div>

<script>

function openCreateUnitModal() {
    const modal = document.getElementById('createUnitModal');
    if (!modal) return;
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeCreateUnitModal() {
    const modal = document.getElementById('createUnitModal');
    if (!modal) return;
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

function openAssignCustomerModal() {
    const modal = document.getElementById('assignCustomerModal');
    if (!modal) return;
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeAssignCustomerModal() {
    const modal = document.getElementById('assignCustomerModal');
    if (!modal) return;
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

function filterUnits() {
    const input = document.getElementById('unitSearch');
    if (!input) return;

    const search = input.value.toLowerCase().trim();
    const rows = document.querySelectorAll('.unit-row');

    rows.forEach(function (row) {
        const text = (row.dataset.search || '').toLowerCase();

        if (search === '' || text.includes(search)) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    });
}

document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    closeCreateUnitModal();
    closeAssignCustomerModal();
});

</script>

<?php

require_once __DIR__ . '/../../includes/footer.php';

?>