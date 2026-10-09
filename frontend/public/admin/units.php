
<?php
/**
 * ============================================================
 * PropertyPro - Admin Units & Customer Assignments
 * ============================================================
 */

require_once __DIR__ . '/../../includes/api.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

$pageTitle = 'Units';

/*
|--------------------------------------------------------------------------
| HELPERS
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
    return 'KES ' . number_format((float)($value ?? 0), 2);
}

function pp_units_ok(array $response): bool
{
    $status = (int)($response['status'] ?? 200);

    return $status >= 200
        && $status < 300
        && (!array_key_exists('success', $response) || $response['success']);
}

function pp_units_error(array $response, string $fallback): string
{
    foreach (['message', 'error', 'detail', 'msg'] as $key) {
        if (isset($response[$key]) && is_string($response[$key]) && trim($response[$key]) !== '') {
            return $response[$key];
        }
    }

    return $fallback;
}

function pp_units_list(array $response, array $keys = ['data']): array
{
    foreach ($keys as $key) {
        if (!isset($response[$key]) || !is_array($response[$key])) {
            continue;
        }

        $value = $response[$key];

        if (!array_is_list($value)) {
            foreach (['data', 'units', 'properties', 'customers', 'users', 'tenants', 'leases'] as $nested) {
                if (isset($value[$nested]) && is_array($value[$nested])) {
                    $value = $value[$nested];
                    break;
                }
            }
        }

        if (array_is_list($value)) {
            return array_values(array_filter($value, 'is_array'));
        }
    }

    return array_is_list($response)
        ? array_values(array_filter($response, 'is_array'))
        : [];
}

function pp_units_id(array $row, array $keys = ['_id', 'id']): string
{
    foreach ($keys as $key) {
        if (isset($row[$key]) && (is_string($row[$key]) || is_numeric($row[$key]))) {
            $value = trim((string)$row[$key]);

            if ($value !== '') {
                return $value;
            }
        }
    }

    return '';
}

function pp_units_ref_id($value): string
{
    if (is_array($value)) {
        return pp_units_id($value, ['_id', 'id', 'userId', 'tenantId', 'unitId']);
    }

    return is_string($value) || is_numeric($value)
        ? trim((string)$value)
        : '';
}

function pp_units_customer_id(array $customer): string
{
    // /admin/customers should return User documents.
    return pp_units_id($customer, ['_id', 'id']);
}

function pp_units_customer_name(array $customer): string
{
    foreach (['name', 'fullName', 'username'] as $key) {
        if (isset($customer[$key]) && trim((string)$customer[$key]) !== '') {
            return trim((string)$customer[$key]);
        }
    }

    $name = trim(
        (string)($customer['firstName'] ?? '') . ' ' .
        (string)($customer['lastName'] ?? '')
    );

    if ($name !== '') {
        return $name;
    }

    foreach (['userId', 'user', 'customer'] as $key) {
        if (isset($customer[$key]) && is_array($customer[$key])) {
            $nested = pp_units_customer_name($customer[$key]);

            if ($nested !== 'Unnamed Customer') {
                return $nested;
            }
        }
    }

    return trim((string)($customer['email'] ?? 'Unnamed Customer'));
}

function pp_units_customer_email(array $customer): string
{
    if (!empty($customer['email'])) {
        return trim((string)$customer['email']);
    }

    foreach (['userId', 'user'] as $key) {
        if (isset($customer[$key]) && is_array($customer[$key])) {
            return trim((string)($customer[$key]['email'] ?? ''));
        }
    }

    return '';
}

function pp_units_property_id(array $unit): string
{
    return pp_units_ref_id($unit['propertyId'] ?? $unit['property'] ?? '');
}

function pp_units_property_name(array $row): string
{
    $property = $row['propertyId'] ?? $row['property'] ?? null;

    if (is_array($property)) {
        return trim((string)(
            $property['name'] ??
            $property['propertyName'] ??
            $property['title'] ??
            'Unknown Property'
        ));
    }

    return trim((string)($row['propertyName'] ?? 'Unknown Property'));
}

function pp_units_unit_number(array $unit): string
{
    return trim((string)(
        $unit['unitNumber'] ??
        $unit['number'] ??
        'N/A'
    ));
}

function pp_units_rent(array $unit): float
{
    return (float)($unit['rent'] ?? $unit['monthlyRent'] ?? 0);
}

function pp_units_status(array $unit): string
{
    $status = strtolower(trim((string)(
        $unit['status'] ??
        $unit['unitStatus'] ??
        'Vacant'
    )));

    if (in_array($status, ['occupied', 'rented', 'leased'], true)) {
        return 'Occupied';
    }

    if (in_array($status, ['maintenance', 'under maintenance', 'repair'], true)) {
        return 'Maintenance';
    }

    return 'Vacant';
}

function pp_units_date($value): string
{
    if (!$value) {
        return '—';
    }

    try {
        return (new DateTimeImmutable((string)$value))->format('d M Y');
    } catch (Throwable $e) {
        return '—';
    }
}

function pp_units_lease_active(array $lease): bool
{
    $status = strtolower(trim((string)($lease['status'] ?? '')));

    return in_array($status, ['active'], true);
}

function pp_units_flash(string $message, string $type = 'error'): void
{
    $_SESSION['units_flash_message'] = $message;
    $_SESSION['units_flash_type'] = $type;
}

function pp_units_status_badge(string $status): string
{
    if ($status === 'Occupied') {
        return 'bg-emerald-50 text-emerald-700';
    }

    if ($status === 'Maintenance') {
        return 'bg-rose-50 text-rose-700';
    }

    return 'bg-amber-50 text-amber-700';
}

/*
|--------------------------------------------------------------------------
| LOAD API DATA
|--------------------------------------------------------------------------
*/

$units = [];
$properties = [];
$customers = [];
$tenants = [];
$leases = [];

$apiErrors = [];

$endpoints = [
    'units' => ['/units', ['data', 'units']],
    'properties' => ['/properties', ['data', 'properties']],
    'customers' => ['/admin/customers', ['data', 'customers', 'users']],
    'tenants' => ['/tenants', ['data', 'tenants']],
    'leases' => ['/leases', ['data', 'leases']],
];

foreach ($endpoints as $name => [$endpoint, $keys]) {
    try {
        $response = api_get($endpoint);

        if (!is_array($response) || !pp_units_ok($response)) {
            $apiErrors[$name] = is_array($response)
                ? pp_units_error($response, "Could not load {$name}.")
                : "Could not load {$name}.";

            error_log("PropertyPro {$name} API: " . json_encode($response));
            continue;
        }

        $data = pp_units_list($response, $keys);

        if ($name === 'units') {
            $units = $data;
        } elseif ($name === 'properties') {
            $properties = $data;
        } elseif ($name === 'customers') {
            $customers = $data;
        } elseif ($name === 'tenants') {
            $tenants = $data;
        } elseif ($name === 'leases') {
            $leases = $data;
        }
    } catch (Throwable $e) {
        $apiErrors[$name] = "Could not load {$name}.";
        error_log("PropertyPro {$name} API error: " . $e->getMessage());
    }
}

/*
|--------------------------------------------------------------------------
| CREATE UNIT
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_unit') {
    $propertyId = trim((string)($_POST['propertyId'] ?? ''));
    $unitNumber = trim((string)($_POST['unitNumber'] ?? ''));
    $rentInput = trim((string)($_POST['rent'] ?? ''));

    if (
        $propertyId === '' ||
        $unitNumber === '' ||
        $rentInput === '' ||
        !is_numeric($rentInput) ||
        (float)$rentInput < 0
    ) {
        pp_units_flash('Select a property and enter a valid unit number and rent.');
        header('Location: units.php');
        exit;
    }

    $propertyExists = false;

    foreach ($properties as $property) {
        if (pp_units_id($property) === $propertyId) {
            $propertyExists = true;
            break;
        }
    }

    if (!$propertyExists) {
        pp_units_flash('The selected property could not be found.');
        header('Location: units.php');
        exit;
    }

    try {
        $unitCode = 'UNIT-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
    } catch (Throwable $e) {
        $unitCode = 'UNIT-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -6));
    }

    try {
        $response = api_post('/units', [
            'unitId' => $unitCode,
            'propertyId' => $propertyId,
            'unitNumber' => $unitNumber,
            'rent' => (float)$rentInput,
            'status' => 'Vacant',
        ]);

        if (is_array($response) && pp_units_ok($response)) {
            pp_units_flash('Unit created successfully.', 'success');
        } else {
            pp_units_flash(
                pp_units_error(
                    is_array($response) ? $response : [],
                    'Unable to create unit.'
                )
            );
        }
    } catch (Throwable $e) {
        error_log('Create unit error: ' . $e->getMessage());
        pp_units_flash('Unable to create unit. Please try again.');
    }

    header('Location: units.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| ASSIGN CUSTOMER
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'assign_customer') {
    $customerId = trim((string)($_POST['customerId'] ?? ''));
    $propertyId = trim((string)($_POST['propertyId'] ?? ''));
    $unitId = trim((string)($_POST['unitId'] ?? ''));
    $startDate = trim((string)($_POST['startDate'] ?? ''));
    $endDate = trim((string)($_POST['endDate'] ?? ''));

    if (!$customerId || !$propertyId || !$unitId || !$startDate || !$endDate) {
        pp_units_flash('Customer, property, unit, start date and end date are required.');
        header('Location: units.php');
        exit;
    }

    $start = DateTimeImmutable::createFromFormat('!Y-m-d', $startDate);
    $end = DateTimeImmutable::createFromFormat('!Y-m-d', $endDate);
    $startErrors = DateTimeImmutable::getLastErrors();
    $endErrors = DateTimeImmutable::getLastErrors();

    $validStart = $start !== false &&
        ($startErrors === false || (
            $startErrors['warning_count'] === 0 &&
            $startErrors['error_count'] === 0
        )) &&
        $start->format('Y-m-d') === $startDate;

    $validEnd = $end !== false &&
        ($endErrors === false || (
            $endErrors['warning_count'] === 0 &&
            $endErrors['error_count'] === 0
        )) &&
        $end->format('Y-m-d') === $endDate;

    if (!$validStart || !$validEnd || $end <= $start) {
        pp_units_flash('Enter valid lease dates. The end date must be after the start date.');
        header('Location: units.php');
        exit;
    }

    $selectedUnit = null;

    foreach ($units as $unit) {
        if (pp_units_id($unit, ['_id', 'id', 'unitId']) === $unitId) {
            $selectedUnit = $unit;
            break;
        }
    }

    if (!$selectedUnit) {
        pp_units_flash('The selected unit could not be found. Refresh the page and try again.');
        header('Location: units.php');
        exit;
    }

    if (pp_units_property_id($selectedUnit) !== $propertyId) {
        pp_units_flash('The selected unit does not belong to the selected property.');
        header('Location: units.php');
        exit;
    }

    if (pp_units_status($selectedUnit) !== 'Vacant') {
        pp_units_flash('Only vacant units can be assigned.');
        header('Location: units.php');
        exit;
    }

    $selectedCustomer = null;

    foreach ($customers as $customer) {
        if (pp_units_customer_id($customer) === $customerId) {
            $selectedCustomer = $customer;
            break;
        }
    }

    if (!$selectedCustomer) {
        pp_units_flash('The selected customer could not be found. Refresh the page and try again.');
        header('Location: units.php');
        exit;
    }

    /*
     * Send both accepted customer ID field names for compatibility.
     * The value must be the MongoDB User document ID.
     */
    try {
        $response = api_post('/leases/assign-customer', [
            'userId' => $customerId,
            'customerId' => $customerId,
            'propertyId' => $propertyId,
            'unitId' => $unitId,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        error_log('PropertyPro assignment response: ' . json_encode($response));

        if (is_array($response) && pp_units_ok($response)) {
            pp_units_flash('Customer assigned to the unit successfully.', 'success');
        } else {
            pp_units_flash(
                pp_units_error(
                    is_array($response) ? $response : [],
                    'Unable to assign customer.'
                )
            );
        }
    } catch (Throwable $e) {
        error_log('Assign customer error: ' . $e->getMessage());
        pp_units_flash('Unable to assign customer. Please try again.');
    }

    header('Location: units.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| BUILD LOOKUP MAPS
|--------------------------------------------------------------------------
|
| User -> Tenant -> Lease -> Unit/Property.
| Active lease records are the primary source for assignment status.
|
*/

$tenantById = [];
$tenantByUserId = [];
$activeLeaseByTenantId = [];
$activeLeaseByUserId = [];
$activeLeaseByUnitId = [];
$unitById = [];
$propertyById = [];

foreach ($properties as $property) {
    $id = pp_units_id($property);

    if ($id !== '') {
        $propertyById[$id] = $property;
    }
}

foreach ($units as $unit) {
    $id = pp_units_id($unit, ['_id', 'id', 'unitId']);

    if ($id !== '') {
        $unitById[$id] = $unit;
    }
}

foreach ($tenants as $tenant) {
    $tenantId = pp_units_id($tenant, ['_id', 'id']);
    $userId = pp_units_ref_id($tenant['userId'] ?? $tenant['user'] ?? '');

    if ($tenantId !== '') {
        $tenantById[$tenantId] = $tenant;
    }

    if ($userId !== '') {
        $tenantByUserId[$userId] = $tenant;
    }
}

foreach ($leases as $lease) {
    if (!pp_units_lease_active($lease)) {
        continue;
    }

    $tenantId = pp_units_ref_id($lease['tenantId'] ?? '');
    $unitId = pp_units_ref_id($lease['unitId'] ?? '');
    $tenant = is_array($lease['tenantId'] ?? null)
        ? $lease['tenantId']
        : ($tenantById[$tenantId] ?? []);
    $userId = pp_units_ref_id($tenant['userId'] ?? $tenant['user'] ?? '');

    if ($tenantId !== '') {
        $activeLeaseByTenantId[$tenantId] = $lease;
    }

    if ($userId !== '') {
        $activeLeaseByUserId[$userId] = $lease;
    }

    if ($unitId !== '') {
        $activeLeaseByUnitId[$unitId] = $lease;
    }
}

/*
|--------------------------------------------------------------------------
| CLASSIFY CUSTOMERS
|--------------------------------------------------------------------------
*/

$assignedCustomers = [];
$unassignedCustomers = [];
$customerAssignmentByUserId = [];

foreach ($customers as $customer) {
    $userId = pp_units_customer_id($customer);
    $tenant = $tenantByUserId[$userId] ?? [];
    $tenantId = pp_units_id($tenant, ['_id', 'id']);
    $lease = $activeLeaseByUserId[$userId]
        ?? ($tenantId !== '' ? ($activeLeaseByTenantId[$tenantId] ?? null) : null);

    $tenantHasAssignment = !empty($tenant['unitId']) || !empty($tenant['propertyId']);

    $isAssigned = $lease !== null || $tenantHasAssignment;

    /*
     * A tenant's assignment may be present without a populated lease.
     * Use its linked unit/property as a fallback.
     */
    $unitId = $lease
        ? pp_units_ref_id($lease['unitId'] ?? '')
        : pp_units_ref_id($tenant['unitId'] ?? '');

    $propertyId = $lease
        ? pp_units_ref_id($lease['propertyId'] ?? '')
        : pp_units_ref_id($tenant['propertyId'] ?? '');

    $unit = $unitId !== '' ? ($unitById[$unitId] ?? []) : [];
    $property = $propertyId !== '' ? ($propertyById[$propertyId] ?? []) : [];

    if ($lease && is_array($lease['unitId'] ?? null)) {
        $unit = $lease['unitId'];
        $unitId = pp_units_ref_id($unit);
    }

    if ($lease && is_array($lease['propertyId'] ?? null)) {
        $property = $lease['propertyId'];
        $propertyId = pp_units_ref_id($property);
    }

    if (!$property && $unit) {
        $propertyId = pp_units_property_id($unit);
        $property = $propertyById[$propertyId] ?? [];
    }

    /*
     * Do not mark a customer as assigned solely because a stale tenant
     * record exists with an empty or invalid unit reference.
     */
    if ($isAssigned && $unitId === '') {
        $isAssigned = false;
    }

    $record = [
        'customer' => $customer,
        'userId' => $userId,
        'tenant' => $tenant,
        'tenantId' => $tenantId,
        'lease' => $lease,
        'unit' => $unit,
        'unitId' => $unitId,
        'property' => $property,
        'propertyId' => $propertyId,
    ];

    if ($userId !== '') {
        $customerAssignmentByUserId[$userId] = $record;
    }

    if ($isAssigned) {
        $assignedCustomers[] = $record;
    } else {
        $unassignedCustomers[] = $record;
    }
}

/*
|--------------------------------------------------------------------------
| BUILD UNIT ASSIGNMENT DETAILS
|--------------------------------------------------------------------------
*/

$occupiedUnits = [];
$vacantUnits = [];
$maintenanceUnits = [];
$totalRent = 0.0;

foreach ($units as $unit) {
    $unitId = pp_units_id($unit, ['_id', 'id', 'unitId']);
    $status = pp_units_status($unit);
    $totalRent += pp_units_rent($unit);

    $lease = $activeLeaseByUnitId[$unitId] ?? null;
    $tenantRef = pp_units_ref_id($unit['tenantId'] ?? $unit['customerId'] ?? '');
    $tenant = [];

    if (is_array($unit['tenantId'] ?? null)) {
        $tenant = $unit['tenantId'];
    } elseif ($tenantRef !== '') {
        $tenant = $tenantById[$tenantRef] ?? [];
    }

    if ($lease && is_array($lease['tenantId'] ?? null)) {
        $tenant = $lease['tenantId'];
    }

    $userId = pp_units_ref_id($tenant['userId'] ?? '');
    $customerRecord = $userId !== ''
        ? ($customerAssignmentByUserId[$userId] ?? null)
        : null;

    $unitRecord = [
        'unit' => $unit,
        'unitId' => $unitId,
        'status' => $status,
        'lease' => $lease,
        'tenant' => $tenant,
        'customerRecord' => $customerRecord,
    ];

    if ($status === 'Maintenance') {
        $maintenanceUnits[] = $unitRecord;
    } elseif ($status === 'Vacant') {
        $vacantUnits[] = $unitRecord;
    } else {
        $occupiedUnits[] = $unitRecord;
    }
}

$totalUnits = count($units);
$totalCustomers = count($customers);
$assignedCount = count($assignedCustomers);
$unassignedCount = count($unassignedCustomers);
$vacantCount = count($vacantUnits);
$occupiedCount = count($occupiedUnits);
$maintenanceCount = count($maintenanceUnits);
$occupancyRate = $totalUnits > 0
    ? (int)round(($occupiedCount / $totalUnits) * 100)
    : 0;

/*
|--------------------------------------------------------------------------
| FLASH MESSAGES
|--------------------------------------------------------------------------
*/

$flashMessage = (string)($_SESSION['units_flash_message'] ?? '');
$flashType = (string)($_SESSION['units_flash_type'] ?? '');

unset($_SESSION['units_flash_message'], $_SESSION['units_flash_type']);

/*
|--------------------------------------------------------------------------
| PAGE LAYOUT
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="min-h-screen bg-slate-50 lg:ml-64">
    <div class="px-4 py-6 sm:px-6 lg:px-8">

        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Units & Customer Assignments</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Manage rental units, customer occupancy and lease assignments.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="button" onclick="openCreateUnitModal()"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <span class="text-lg">+</span> Add Unit
                </button>

                <button type="button" onclick="openAssignCustomerModal()"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    Assign Customer
                </button>
            </div>
        </div>

        <?php if ($flashMessage !== ''): ?>
            <div class="mb-6 rounded-xl border px-4 py-3 <?= $flashType === 'success'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                : 'border-red-200 bg-red-50 text-red-800' ?>">
                <?= pp_units_e($flashMessage) ?>
            </div>
        <?php endif; ?>

        <?php if ($apiErrors): ?>
            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <p class="font-semibold">Some information could not be loaded.</p>
                <p class="mt-1">
                    The page may show incomplete assignment information until the relevant API endpoints are available.
                </p>
                <?php foreach ($apiErrors as $name => $message): ?>
                    <p class="mt-1"><?= pp_units_e(ucfirst($name) . ': ' . $message) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- SUMMARY CARDS -->

        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">Total Customers</p>
                    <span class="rounded-lg bg-indigo-50 px-2.5 py-2 text-indigo-600">👥</span>
                </div>
                <p class="mt-3 text-3xl font-bold text-slate-900"><?= $totalCustomers ?></p>
                <p class="mt-1 text-xs text-slate-500">Registered customer accounts</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">Assigned Customers</p>
                    <span class="rounded-lg bg-emerald-50 px-2.5 py-2 text-emerald-600">✓</span>
                </div>
                <p class="mt-3 text-3xl font-bold text-slate-900"><?= $assignedCount ?></p>
                <p class="mt-1 text-xs text-slate-500">Customers with a unit assignment</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">Unassigned Customers</p>
                    <span class="rounded-lg bg-amber-50 px-2.5 py-2 text-amber-600">!</span>
                </div>
                <p class="mt-3 text-3xl font-bold text-slate-900"><?= $unassignedCount ?></p>
                <p class="mt-1 text-xs text-slate-500">Customers waiting for a unit</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">Vacant Units</p>
                    <span class="rounded-lg bg-slate-100 px-2.5 py-2 text-slate-600">⌂</span>
                </div>
                <p class="mt-3 text-3xl font-bold text-slate-900"><?= $vacantCount ?></p>
                <p class="mt-1 text-xs text-slate-500">
                    <?= $occupiedCount ?> occupied · <?= $maintenanceCount ?> maintenance
                </p>
            </div>
        </div>

        <!-- CUSTOMER ASSIGNMENTS -->

        <section class="mb-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Customer Assignments</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            View customers who have a unit and those who still need one.
                        </p>
                    </div>

                    <input id="customerSearch" type="search"
                        placeholder="Search customers..."
                        oninput="filterTable('customerSearch', 'customerRows')"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 sm:w-64">
                </div>
            </div>

            <div class="flex flex-wrap gap-2 border-b border-slate-200 px-5 py-3">
                <button type="button" onclick="showCustomerTab('all')"
                    data-customer-tab="all"
                    class="customer-tab rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white">
                    All (<?= $totalCustomers ?>)
                </button>
                <button type="button" onclick="showCustomerTab('assigned')"
                    data-customer-tab="assigned"
                    class="customer-tab rounded-lg bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200">
                    Assigned (<?= $assignedCount ?>)
                </button>
                <button type="button" onclick="showCustomerTab('unassigned')"
                    data-customer-tab="unassigned"
                    class="customer-tab rounded-lg bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200">
                    Unassigned (<?= $unassignedCount ?>)
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Customer</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Property</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Unit</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Lease Period</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Assignment</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Action</th>
                        </tr>
                    </thead>

                    <tbody id="customerRows" class="divide-y divide-slate-200">
                    <?php if (!$customers): ?>
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="font-semibold text-slate-900">No customers found</p>
                                <p class="mt-1 text-sm text-slate-500">
                                    Check that the admin customers API returns registered customer accounts.
                                </p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $customer): ?>
                            <?php
                            $userId = pp_units_customer_id($customer);
                            $record = $customerAssignmentByUserId[$userId] ?? null;
                            $assigned = $record !== null &&
                                (!empty($record['unitId']));

                            $lease = $record['lease'] ?? null;
                            $unit = $record['unit'] ?? [];
                            $property = $record['property'] ?? [];

                            $name = pp_units_customer_name($customer);
                            $email = pp_units_customer_email($customer);
                            $propertyName = $assigned
                                ? (string)($property['name'] ?? $property['propertyName'] ?? pp_units_property_name($unit))
                                : 'Not assigned';
                            $unitNumber = $assigned
                                ? pp_units_unit_number($unit)
                                : '—';

                            $leasePeriod = '—';

                            if ($lease) {
                                $leasePeriod = pp_units_date($lease['startDate'] ?? null)
                                    . ' – ' .
                                    pp_units_date($lease['endDate'] ?? null);
                            }

                            $search = strtolower($name . ' ' . $email . ' ' . $propertyName . ' ' . $unitNumber);
                            ?>
                            <tr
                                class="customer-row hover:bg-slate-50"
                                data-search="<?= pp_units_e($search) ?>"
                                data-assignment="<?= $assigned ? 'assigned' : 'unassigned' ?>">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-900"><?= pp_units_e($name) ?></p>
                                    <p class="mt-1 text-xs text-slate-500"><?= pp_units_e($email) ?></p>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-700">
                                    <?= pp_units_e($propertyName) ?>
                                </td>
                                <td class="px-5 py-4 text-sm font-semibold text-slate-800">
                                    <?= pp_units_e($unitNumber) ?>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                    <?= pp_units_e($leasePeriod) ?>
                                </td>
                                <td class="px-5 py-4">
                                    <?php if ($assigned): ?>
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            Assigned
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                            Unassigned
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <?php if (!$assigned): ?>
                                        <button type="button"
                                            onclick="openAssignCustomerModal('<?= pp_units_e($userId) ?>')"
                                            class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                            Assign Unit
                                        </button>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">Already assigned</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- UNITS DIRECTORY -->

        <section class="mb-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">All Rental Units</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            <?= $totalUnits ?> total · <?= $occupiedCount ?> occupied ·
                            <?= $vacantCount ?> vacant · <?= $maintenanceCount ?> maintenance
                        </p>
                    </div>

                    <input id="unitSearch" type="search"
                        placeholder="Search units..."
                        oninput="filterTable('unitSearch', 'unitRows')"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 sm:w-64">
                </div>
            </div>

            <div class="flex flex-wrap gap-2 border-b border-slate-200 px-5 py-3">
                <button type="button" onclick="showUnitTab('all')" data-unit-tab="all"
                    class="unit-tab rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white">
                    All
                </button>
                <button type="button" onclick="showUnitTab('Occupied')" data-unit-tab="Occupied"
                    class="unit-tab rounded-lg bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200">
                    Occupied
                </button>
                <button type="button" onclick="showUnitTab('Vacant')" data-unit-tab="Vacant"
                    class="unit-tab rounded-lg bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200">
                    Vacant
                </button>
                <button type="button" onclick="showUnitTab('Maintenance')" data-unit-tab="Maintenance"
                    class="unit-tab rounded-lg bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200">
                    Maintenance
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Unit</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Property</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tenant / Customer</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Monthly Rent</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Action</th>
                        </tr>
                    </thead>

                    <tbody id="unitRows" class="divide-y divide-slate-200">
                    <?php if (!$units): ?>
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="font-semibold text-slate-900">No units found</p>
                                <p class="mt-1 text-sm text-slate-500">Add a rental unit to get started.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($units as $unit): ?>
                            <?php
                            $unitId = pp_units_id($unit, ['_id', 'id', 'unitId']);
                            $status = pp_units_status($unit);
                            $unitNumber = pp_units_unit_number($unit);
                            $propertyName = pp_units_property_name($unit);
                            $propertyId = pp_units_property_id($unit);
                            $rent = pp_units_rent($unit);

                            $lease = $activeLeaseByUnitId[$unitId] ?? null;
                            $tenant = [];

                            if (is_array($unit['tenantId'] ?? null)) {
                                $tenant = $unit['tenantId'];
                            } else {
                                $tenantId = pp_units_ref_id($unit['tenantId'] ?? '');
                                $tenant = $tenantById[$tenantId] ?? [];
                            }

                            if ($lease && is_array($lease['tenantId'] ?? null)) {
                                $tenant = $lease['tenantId'];
                            }

                            $tenantName = $tenant
                                ? pp_units_customer_name($tenant)
                                : (trim((string)($unit['tenantName'] ?? '')) ?: '—');

                            $search = strtolower($unitNumber . ' ' . $propertyName . ' ' . $tenantName . ' ' . $status);
                            ?>
                            <tr class="unit-row hover:bg-slate-50"
                                data-search="<?= pp_units_e($search) ?>"
                                data-status="<?= pp_units_e($status) ?>">
                                <td class="whitespace-nowrap px-5 py-4">
                                    <p class="font-semibold text-slate-900"><?= pp_units_e($unitNumber) ?></p>
                                    <?php if (!empty($unit['type'])): ?>
                                        <p class="mt-1 text-xs text-slate-400"><?= pp_units_e($unit['type']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-700">
                                    <p class="font-medium"><?= pp_units_e($propertyName) ?></p>
                                    <?php
                                    $location = '';

                                    if (is_array($unit['propertyId'] ?? null)) {
                                        $location = (string)($unit['propertyId']['location'] ?? '');
                                    } elseif (is_array($unit['property'] ?? null)) {
                                        $location = (string)($unit['property']['location'] ?? '');
                                    }
                                    ?>
                                    <?php if ($location !== ''): ?>
                                        <p class="mt-1 text-xs text-slate-400"><?= pp_units_e($location) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-700">
                                    <?= pp_units_e($tenantName) ?>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-800">
                                    <?= pp_units_money($rent) ?>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= pp_units_status_badge($status) ?>">
                                        <?= pp_units_e($status) ?>
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <?php if ($status === 'Vacant'): ?>
                                        <button type="button"
                                            onclick="openAssignCustomerModal('', '<?= pp_units_e($propertyId) ?>', '<?= pp_units_e($unitId) ?>')"
                                            class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                                            Assign
                                        </button>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- VACANT UNITS SUMMARY -->

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-lg font-semibold text-slate-900">Available Vacant Units</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Units that can be assigned to customers.
                </p>
            </div>

            <?php if (!$vacantUnits): ?>
                <div class="px-5 py-10 text-center">
                    <p class="font-semibold text-slate-900">No vacant units available</p>
                    <p class="mt-1 text-sm text-slate-500">All units are occupied or under maintenance.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
                    <?php foreach ($vacantUnits as $record): ?>
                        <?php
                        $unit = $record['unit'];
                        $unitId = $record['unitId'];
                        $propertyId = pp_units_property_id($unit);
                        ?>
                        <div class="rounded-xl border border-slate-200 p-4 transition hover:border-indigo-200 hover:shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-900">
                                        <?= pp_units_e(pp_units_unit_number($unit)) ?>
                                    </p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        <?= pp_units_e(pp_units_property_name($unit)) ?>
                                    </p>
                                </div>
                                <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                    Vacant
                                </span>
                            </div>

                            <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4">
                                <div>
                                    <p class="text-xs text-slate-500">Monthly rent</p>
                                    <p class="mt-1 font-bold text-slate-900">
                                        <?= pp_units_money(pp_units_rent($unit)) ?>
                                    </p>
                                </div>
                                <button type="button"
                                    onclick="openAssignCustomerModal('', '<?= pp_units_e($propertyId) ?>', '<?= pp_units_e($unitId) ?>')"
                                    class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                    Assign Customer
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<!-- =========================================================
     CREATE UNIT MODAL
========================================================= -->

<div id="createUnitModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/50" onclick="closeCreateUnitModal()"></div>

    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Add Rental Unit</h2>
                    <p class="text-sm text-slate-500">Create a new rental unit.</p>
                </div>
                <button type="button" onclick="closeCreateUnitModal()"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100" aria-label="Close">
                    ✕
                </button>
            </div>

            <form method="POST" action="units.php" class="space-y-5 p-6">
                <input type="hidden" name="action" value="create_unit">

                <div>
                    <label for="createPropertyId" class="mb-1.5 block text-sm font-semibold text-slate-700">Property</label>
                    <select name="propertyId" id="createPropertyId" required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                        <option value="">Select property</option>
                        <?php foreach ($properties as $property): ?>
                            <?php $id = pp_units_id($property); ?>
                            <?php if ($id !== ''): ?>
                                <option value="<?= pp_units_e($id) ?>">
                                    <?= pp_units_e($property['name'] ?? $property['propertyName'] ?? 'Unnamed Property') ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="unitNumber" class="mb-1.5 block text-sm font-semibold text-slate-700">Unit Number</label>
                    <input type="text" name="unitNumber" id="unitNumber" required placeholder="e.g. A-101"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                </div>

                <div>
                    <label for="rent" class="mb-1.5 block text-sm font-semibold text-slate-700">Monthly Rent (KES)</label>
                    <input type="number" name="rent" id="rent" min="0" step="0.01" required placeholder="25000"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-200 pt-5">
                    <button type="button" onclick="closeCreateUnitModal()"
                        class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit"
                        class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
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

<div id="assignCustomerModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/60" onclick="closeAssignCustomerModal()"></div>

    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Assign Customer</h2>
                    <p class="mt-1 text-sm text-slate-500">Select a customer, property, unit and lease dates.</p>
                </div>
                <button type="button" onclick="closeAssignCustomerModal()"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100" aria-label="Close">
                    ✕
                </button>
            </div>

            <form id="assignCustomerForm" method="POST" action="units.php" class="space-y-5 p-6">
                <input type="hidden" name="action" value="assign_customer">

                <div>
                    <label for="customerId" class="mb-1.5 block text-sm font-semibold text-slate-700">Customer *</label>
                    <select name="customerId" id="customerId" required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                        <option value="">Select customer</option>
                        <?php foreach ($unassignedCustomers as $record): ?>
                            <?php
                            $customer = $record['customer'];
                            $id = pp_units_customer_id($customer);
                            $name = pp_units_customer_name($customer);
                            $email = pp_units_customer_email($customer);
                            ?>
                            <?php if ($id !== ''): ?>
                                <option value="<?= pp_units_e($id) ?>">
                                    <?= pp_units_e($name) ?><?= $email !== '' ? ' — ' . pp_units_e($email) : '' ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Only customers not currently assigned are listed.</p>
                </div>

                <div>
                    <label for="assignPropertyId" class="mb-1.5 block text-sm font-semibold text-slate-700">Property *</label>
                    <select name="propertyId" id="assignPropertyId" required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                        <option value="">Select property</option>
                        <?php foreach ($properties as $property): ?>
                            <?php
                            $id = pp_units_id($property);
                            $name = $property['name'] ?? $property['propertyName'] ?? 'Unnamed Property';
                            ?>
                            <?php if ($id !== ''): ?>
                                <option value="<?= pp_units_e($id) ?>"><?= pp_units_e($name) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="assignUnitId" class="mb-1.5 block text-sm font-semibold text-slate-700">Vacant Unit *</label>
                    <select name="unitId" id="assignUnitId" required disabled
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:bg-slate-100">
                        <option value="">Select a property first</option>
                        <?php foreach ($vacantUnits as $record): ?>
                            <?php
                            $unit = $record['unit'];
                            $id = $record['unitId'];
                            $propertyId = pp_units_property_id($unit);
                            ?>
                            <?php if ($id !== '' && $propertyId !== ''): ?>
                                <option value="<?= pp_units_e($id) ?>"
                                    data-property-id="<?= pp_units_e($propertyId) ?>">
                                    <?= pp_units_e(pp_units_unit_number($unit)) ?>
                                    — <?= pp_units_e(pp_units_property_name($unit)) ?>
                                    — <?= pp_units_money(pp_units_rent($unit)) ?>/month
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <p id="assignUnitHelp" class="mt-1.5 text-xs text-slate-500">Choose a property to see its vacant units.</p>
                </div>

                <div>
                    <label for="startDate" class="mb-1.5 block text-sm font-semibold text-slate-700">Lease Start Date *</label>
                    <input type="date" name="startDate" id="startDate" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                </div>

                <div>
                    <label for="endDate" class="mb-1.5 block text-sm font-semibold text-slate-700">Lease End Date *</label>
                    <input type="date" name="endDate" id="endDate" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-200 pt-5">
                    <button type="button" onclick="closeAssignCustomerModal()"
                        class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" id="assignSubmitButton"
                        class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                        Assign Customer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCreateUnitModal() {
    document.getElementById('createUnitModal')?.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeCreateUnitModal() {
    document.getElementById('createUnitModal')?.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

function openAssignCustomerModal(customerId = '', propertyId = '', unitId = '') {
    const modal = document.getElementById('assignCustomerModal');
    if (!modal) return;

    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');

    const customerSelect = document.getElementById('customerId');
    const propertySelect = document.getElementById('assignPropertyId');
    const unitSelect = document.getElementById('assignUnitId');

    if (customerSelect && customerId) customerSelect.value = customerId;
    if (propertySelect && propertyId) propertySelect.value = propertyId;

    filterAssignableUnits();

    if (unitSelect && unitId) {
        unitSelect.value = unitId;
    }
}

function closeAssignCustomerModal() {
    document.getElementById('assignCustomerModal')?.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

function filterTable(searchId, tableId) {
    const query = (document.getElementById(searchId)?.value || '').toLowerCase().trim();

    document.querySelectorAll('#' + tableId + ' tr').forEach(row => {
        const text = (row.dataset.search || row.innerText || '').toLowerCase();
        row.classList.toggle('hidden', query !== '' && !text.includes(query));
    });
}

let customerTab = 'all';
let unitTab = 'all';

function showCustomerTab(tab) {
    customerTab = tab;

    document.querySelectorAll('.customer-tab').forEach(button => {
        const active = button.dataset.customerTab === tab;
        button.className = active
            ? 'customer-tab rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white'
            : 'customer-tab rounded-lg bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200';
    });

    document.querySelectorAll('.customer-row').forEach(row => {
        const matches = tab === 'all' || row.dataset.assignment === tab;
        row.classList.toggle('hidden', !matches);
    });

    filterTable('customerSearch', 'customerRows');
}

function showUnitTab(tab) {
    unitTab = tab;

    document.querySelectorAll('.unit-tab').forEach(button => {
        const active = button.dataset.unitTab === tab;
        button.className = active
            ? 'unit-tab rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white'
            : 'unit-tab rounded-lg bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200';
    });

    document.querySelectorAll('.unit-row').forEach(row => {
        const matches = tab === 'all' || row.dataset.status === tab;
        row.classList.toggle('hidden', !matches);
    });

    filterTable('unitSearch', 'unitRows');
}

const propertySelect = document.getElementById('assignPropertyId');
const unitSelect = document.getElementById('assignUnitId');
const unitHelp = document.getElementById('assignUnitHelp');

function filterAssignableUnits() {
    if (!propertySelect || !unitSelect) return;

    const propertyId = propertySelect.value;
    let count = 0;

    Array.from(unitSelect.options).forEach((option, index) => {
        if (index === 0) return;

        const matches = propertyId !== '' && option.dataset.propertyId === propertyId;
        option.hidden = !matches;
        option.disabled = !matches;

        if (matches) count++;
    });

    unitSelect.value = '';
    unitSelect.disabled = propertyId === '' || count === 0;

    if (propertyId === '') {
        unitSelect.options[0].text = 'Select a property first';
        unitHelp.textContent = 'Choose a property to see its vacant units.';
    } else if (count === 0) {
        unitSelect.options[0].text = 'No vacant units available';
        unitHelp.textContent = 'This property has no vacant units available.';
    } else {
        unitSelect.options[0].text = 'Select vacant unit';
        unitHelp.textContent = count + ' vacant unit(s) available.';
    }
}

propertySelect?.addEventListener('change', filterAssignableUnits);

const startInput = document.getElementById('startDate');
const endInput = document.getElementById('endDate');

startInput?.addEventListener('change', () => {
    if (!endInput) return;

    endInput.min = startInput.value;

    if (endInput.value && endInput.value <= startInput.value) {
        endInput.value = '';
    }

    endInput.setCustomValidity('');
});

endInput?.addEventListener('change', () => {
    if (startInput?.value && endInput.value && endInput.value <= startInput.value) {
        endInput.setCustomValidity('The end date must be after the start date.');
    } else {
        endInput.setCustomValidity('');
    }
});

document.getElementById('assignCustomerForm')?.addEventListener('submit', event => {
    const customer = document.getElementById('customerId')?.value;
    const property = propertySelect?.value;
    const unit = unitSelect?.value;
    const start = startInput?.value;
    const end = endInput?.value;

    if (!customer || !property || !unit || !start || !end) {
        event.preventDefault();
        event.currentTarget.reportValidity();
        return;
    }

    if (end <= start) {
        event.preventDefault();
        endInput.setCustomValidity('The end date must be after the start date.');
        endInput.reportValidity();
    }
});

document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
        closeCreateUnitModal();
        closeAssignCustomerModal();
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>