<?php

require_once __DIR__ . '/../../includes/api.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/data.php';

require_admin();

$pageTitle = 'Units';

/*
|--------------------------------------------------------------------------
| Helper functions
|--------------------------------------------------------------------------
*/

function pp_units_value(array $item, array $keys, $default = '')
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $item) && $item[$key] !== null) {
            return $item[$key];
        }
    }

    return $default;
}

function pp_units_normalize_id($value): string
{
    if (is_array($value)) {
        return (string) (
            $value['_id']
            ?? $value['id']
            ?? $value['unitId']
            ?? $value['propertyId']
            ?? $value['tenantId']
            ?? ''
        );
    }

    return (string) ($value ?? '');
}

function pp_units_get_id(array $item, array $keys): string
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $item)) {
            $id = pp_units_normalize_id($item[$key]);

            if ($id !== '') {
                return $id;
            }
        }
    }

    return '';
}

function pp_units_find_property(array $unit): array
{
    global $properties;

    $propertyId = pp_units_get_id(
        $unit,
        ['propertyId', 'property', 'property_id']
    );

    if ($propertyId === '') {
        return [];
    }

    foreach ($properties as $property) {

        if (!is_array($property)) {
            continue;
        }

        $candidateId = pp_units_get_id(
            $property,
            ['_id', 'id', 'propertyId']
        );

        if (
            $candidateId !== '' &&
            $candidateId === $propertyId
        ) {
            return $property;
        }
    }

    return [];
}

function pp_units_find_tenant(array $unit): array
{
    global $tenants;

    $tenantId = pp_units_get_id(
        $unit,
        ['tenantId', 'tenant_id']
    );

    if ($tenantId !== '') {

        foreach ($tenants as $tenant) {

            if (!is_array($tenant)) {
                continue;
            }

            $candidateId = pp_units_get_id(
                $tenant,
                ['_id', 'id', 'tenantId']
            );

            if (
                $candidateId !== '' &&
                $candidateId === $tenantId
            ) {
                return $tenant;
            }
        }
    }

    return [];
}

function pp_units_tenant_name(array $unit): string
{
    if (
        isset($unit['tenant']) &&
        is_array($unit['tenant'])
    ) {
        $name = pp_units_value(
            $unit['tenant'],
            ['name', 'fullName', 'tenantName'],
            ''
        );

        if ($name !== '') {
            return (string) $name;
        }
    }

    if (
        isset($unit['tenant']) &&
        is_string($unit['tenant'])
    ) {
        return $unit['tenant'];
    }

    $tenant = pp_units_find_tenant($unit);

    if (!empty($tenant)) {

        $name = pp_units_value(
            $tenant,
            ['name', 'fullName', 'tenantName'],
            ''
        );

        if ($name !== '') {
            return (string) $name;
        }
    }

    return 'Vacant';
}

function pp_units_is_occupied(array $unit): bool
{
    $status = strtolower(
        trim(
            (string) (
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
        return true;
    }

    if (
        in_array(
            $status,
            ['vacant', 'available', 'empty'],
            true
        )
    ) {
        return false;
    }

    if (!empty($unit['tenantId'])) {
        return true;
    }

    if (!empty($unit['tenant'])) {
        return true;
    }

    return !empty(pp_units_find_tenant($unit));
}

/*
|--------------------------------------------------------------------------
| Form submission
|--------------------------------------------------------------------------
*/

$successMessage = '';
$errorMessage = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'create_unit'
) {

    $unitNumber = trim($_POST['unitNumber'] ?? '');
    $propertyId = trim($_POST['propertyId'] ?? '');
    $rent = trim($_POST['rent'] ?? '');
    $status = trim($_POST['status'] ?? 'Vacant');
    $tenantId = trim($_POST['tenantId'] ?? '');

    /*
     * Validation
     */
    if ($unitNumber === '') {

        $errorMessage = 'Please enter a unit number.';

    } elseif ($propertyId === '') {

        $errorMessage = 'Please select a property.';

    } elseif ($rent === '' || !is_numeric($rent) || (float) $rent < 0) {

        $errorMessage = 'Please enter a valid monthly rent.';

    } elseif (
        !in_array(
            $status,
            ['Vacant', 'Occupied'],
            true
        )
    ) {

        $errorMessage = 'Please select a valid unit status.';

    } elseif (
        $status === 'Occupied' &&
        $tenantId === ''
    ) {

        $errorMessage = 'Please select a tenant for an occupied unit.';

    } else {

        /*
         * Build API payload.
         *
         * The frontend uses the same fields used by the
         * PropertyPro unit records.
         */
        $payload = [
            'unitNumber' => $unitNumber,
            'propertyId' => $propertyId,
            'rent' => (float) $rent,
            'status' => $status,
        ];

        /*
         * Only attach a tenant to an occupied unit.
         */
        if ($status === 'Occupied' && $tenantId !== '') {
            $payload['tenantId'] = $tenantId;
        }

        /*
         * Send request to Node.js backend.
         */
        $result = api_post('/units', $payload);

        if (
            is_array($result) &&
            !empty($result['success'])
        ) {

            /*
             * Prevent duplicate form submission on refresh.
             */
            header(
                'Location: units.php?created=1'
            );
            exit;

        } else {

            $errorMessage =
                $result['message']
                ?? 'Unable to create the unit. Please try again.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| Success message after redirect
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['created']) &&
    $_GET['created'] === '1'
) {
    $successMessage = 'Unit added successfully.';
}

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

$totalUnits = count($units);

$occupiedCount = 0;
$vacantCount = 0;

foreach ($units as $unit) {

    if (!is_array($unit)) {
        continue;
    }

    if (pp_units_is_occupied($unit)) {
        $occupiedCount++;
    } else {
        $vacantCount++;
    }
}

/*
|--------------------------------------------------------------------------
| Page layout
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="lg:ml-64 min-h-screen bg-slate-50">

    <div class="p-4 sm:p-6 lg:p-8">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">

            <div>

                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">
                    Units
                </h1>

                <p class="mt-1 text-sm text-slate-600">
                    Monitor unit occupancy and tenant assignments.
                </p>

            </div>

            <button
                type="button"
                onclick="openAddUnitModal()"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition"
            >
                <span class="text-lg leading-none">+</span>
                Add Unit
            </button>

        </div>

        <!-- Flash messages -->
        <?php if ($successMessage): ?>

            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                <?= e($successMessage) ?>
            </div>

        <?php endif; ?>

        <?php if ($errorMessage): ?>

            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <?= e($errorMessage) ?>
            </div>

        <?php endif; ?>

        <!-- Summary cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">

            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">

                <p class="text-sm font-medium text-slate-500">
                    Total Units
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-900">
                    <?= $totalUnits ?>
                </p>

            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">

                <p class="text-sm font-medium text-slate-500">
                    Occupied
                </p>

                <p class="mt-2 text-3xl font-bold text-indigo-600">
                    <?= $occupiedCount ?>
                </p>

            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">

                <p class="text-sm font-medium text-slate-500">
                    Vacant
                </p>

                <p class="mt-2 text-3xl font-bold text-emerald-600">
                    <?= $vacantCount ?>
                </p>

            </div>

        </div>

        <!-- Units table -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">

            <div class="px-5 py-5 sm:px-6 border-b border-slate-200">

                <h2 class="text-lg font-semibold text-slate-900">
                    All Units
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    View and manage all units across your properties.
                </p>

            </div>

            <?php if (empty($units)): ?>

                <div class="text-center py-12 px-6">

                    <div class="text-slate-400 text-4xl mb-3">
                        🚪
                    </div>

                    <h3 class="text-lg font-semibold text-slate-900">
                        No units found
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        There are currently no units in the system.
                    </p>

                </div>

            <?php else: ?>

                <div class="overflow-x-auto">

                    <table class="w-full text-left">

                        <thead class="bg-slate-50 border-b border-slate-200">

                            <tr>

                                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Unit
                                </th>

                                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Property
                                </th>

                                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Tenant
                                </th>

                                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Rent
                                </th>

                                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Status
                                </th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            <?php foreach ($units as $unit): ?>

                                <?php
                                if (!is_array($unit)) {
                                    continue;
                                }

                                /*
                                 * Unit number
                                 */
                                $unitNumber = pp_units_value(
                                    $unit,
                                    [
                                        'unitNumber',
                                        'unit_name',
                                        'number',
                                        'code',
                                        'name',
                                        'unit'
                                    ],
                                    ''
                                );

                                if (is_array($unitNumber)) {
                                    $unitNumber =
                                        pp_units_normalize_id(
                                            $unitNumber
                                        );
                                }

                                if ((string) $unitNumber === '') {
                                    $unitNumber = 'Unit';
                                }

                                /*
                                 * Property
                                 */
                                $property =
                                    pp_units_find_property($unit);

                                $propertyName = '';

                                if (!empty($property)) {

                                    $propertyName =
                                        pp_units_value(
                                            $property,
                                            [
                                                'name',
                                                'propertyName',
                                                'title'
                                            ],
                                            ''
                                        );
                                }

                                if ($propertyName === '') {

                                    if (
                                        isset($unit['property']) &&
                                        is_string(
                                            $unit['property']
                                        )
                                    ) {
                                        $propertyName =
                                            $unit['property'];

                                    } elseif (
                                        isset($unit['property']) &&
                                        is_array(
                                            $unit['property']
                                        )
                                    ) {
                                        $propertyName =
                                            pp_units_value(
                                                $unit['property'],
                                                [
                                                    'name',
                                                    'propertyName',
                                                    'title'
                                                ],
                                                ''
                                            );
                                    }
                                }

                                if ($propertyName === '') {
                                    $propertyName =
                                        'Unknown Property';
                                }

                                /*
                                 * Tenant
                                 */
                                $tenantName =
                                    pp_units_tenant_name($unit);

                                /*
                                 * Rent
                                 */
                                $rent = pp_units_value(
                                    $unit,
                                    [
                                        'rent',
                                        'monthlyRent',
                                        'rentAmount',
                                        'amount'
                                    ],
                                    0
                                );

                                if (is_array($rent)) {
                                    $rent = 0;
                                }

                                $rent = (float) $rent;

                                /*
                                 * Status
                                 */
                                $occupied =
                                    pp_units_is_occupied(
                                        $unit
                                    );

                                $status = $occupied
                                    ? 'Occupied'
                                    : 'Vacant';

                                $statusClass = $occupied
                                    ? 'bg-indigo-100 text-indigo-700'
                                    : 'bg-emerald-100 text-emerald-700';
                                ?>

                                <tr class="hover:bg-slate-50 transition">

                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900">
                                            <?= e($unitNumber) ?>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="font-medium text-slate-700">
                                            <?= e($propertyName) ?>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">

                                        <div
                                            class="<?= $tenantName === 'Vacant'
                                                ? 'text-slate-400'
                                                : 'font-medium text-slate-700' ?>"
                                        >
                                            <?= e($tenantName) ?>
                                        </div>

                                    </td>

                                    <td class="px-6 py-4">

                                        <div class="font-semibold text-slate-900">
                                            KES <?= number_format($rent, 2) ?>
                                        </div>

                                    </td>

                                    <td class="px-6 py-4">

                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?= e($statusClass) ?>">
                                            <?= e($status) ?>
                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>

</main>


<!-- ============================================================
     ADD UNIT MODAL
============================================================ -->

<div
    id="addUnitModal"
    class="hidden fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="addUnitModalTitle"
    aria-modal="true"
    role="dialog"
>

    <div
        class="fixed inset-0 bg-slate-900/50"
        onclick="closeAddUnitModal()"
    ></div>

    <div class="relative min-h-screen flex items-center justify-center p-4">

        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl">

            <!-- Modal header -->
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">

                <div>

                    <h2
                        id="addUnitModalTitle"
                        class="text-xl font-bold text-slate-900"
                    >
                        Add Unit
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Add a new rental unit to a property.
                    </p>

                </div>

                <button
                    type="button"
                    onclick="closeAddUnitModal()"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                >
                    ✕
                </button>

            </div>

            <!-- Form -->
            <form
                method="POST"
                action="units.php"
                class="px-6 py-6 space-y-5"
            >

                <input
                    type="hidden"
                    name="action"
                    value="create_unit"
                >

                <!-- Unit number -->
                <div>

                    <label
                        for="unitNumber"
                        class="block text-sm font-medium text-slate-700 mb-1.5"
                    >
                        Unit Number
                    </label>

                    <input
                        type="text"
                        id="unitNumber"
                        name="unitNumber"
                        placeholder="e.g. A-103"
                        required
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                </div>

                <!-- Property -->
                <div>

                    <label
                        for="propertyId"
                        class="block text-sm font-medium text-slate-700 mb-1.5"
                    >
                        Property
                    </label>

                    <select
                        id="propertyId"
                        name="propertyId"
                        required
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                        <option value="">
                            Select property
                        </option>

                        <?php foreach ($properties as $property): ?>

                            <?php
                            if (!is_array($property)) {
                                continue;
                            }

                            $propertyId =
                                pp_units_get_id(
                                    $property,
                                    [
                                        '_id',
                                        'id',
                                        'propertyId'
                                    ]
                                );

                            $propertyName =
                                pp_units_value(
                                    $property,
                                    [
                                        'name',
                                        'propertyName',
                                        'title'
                                    ],
                                    'Unnamed Property'
                                );
                            ?>

                            <?php if ($propertyId !== ''): ?>

                                <option value="<?= e($propertyId) ?>">
                                    <?= e($propertyName) ?>
                                </option>

                            <?php endif; ?>

                        <?php endforeach; ?>

                    </select>

                </div>

                <!-- Rent -->
                <div>

                    <label
                        for="rent"
                        class="block text-sm font-medium text-slate-700 mb-1.5"
                    >
                        Monthly Rent (KES)
                    </label>

                    <input
                        type="number"
                        id="rent"
                        name="rent"
                        min="0"
                        step="0.01"
                        placeholder="25000"
                        required
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                </div>

                <!-- Status -->
                <div>

                    <label
                        for="unitStatus"
                        class="block text-sm font-medium text-slate-700 mb-1.5"
                    >
                        Status
                    </label>

                    <select
                        id="unitStatus"
                        name="status"
                        required
                        onchange="toggleTenantField()"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                        <option value="Vacant">
                            Vacant
                        </option>

                        <option value="Occupied">
                            Occupied
                        </option>

                    </select>

                </div>

                <!-- Tenant -->
                <div
                    id="tenantField"
                    class="hidden"
                >

                    <label
                        for="tenantId"
                        class="block text-sm font-medium text-slate-700 mb-1.5"
                    >
                        Tenant
                    </label>

                    <select
                        id="tenantId"
                        name="tenantId"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                        <option value="">
                            Select tenant
                        </option>

                        <?php foreach ($tenants as $tenant): ?>

                            <?php
                            if (!is_array($tenant)) {
                                continue;
                            }

                            $tenantId =
                                pp_units_get_id(
                                    $tenant,
                                    [
                                        '_id',
                                        'id',
                                        'tenantId'
                                    ]
                                );

                            $tenantName =
                                pp_units_value(
                                    $tenant,
                                    [
                                        'name',
                                        'fullName',
                                        'tenantName'
                                    ],
                                    'Unnamed Tenant'
                                );
                            ?>

                            <?php if ($tenantId !== ''): ?>

                                <option value="<?= e($tenantId) ?>">
                                    <?= e($tenantName) ?>
                                </option>

                            <?php endif; ?>

                        <?php endforeach; ?>

                    </select>

                    <p class="mt-1.5 text-xs text-slate-500">
                        A tenant is required when the unit is marked occupied.
                    </p>

                </div>

                <!-- Buttons -->
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-2">

                    <button
                        type="button"
                        onclick="closeAddUnitModal()"
                        class="w-full sm:w-auto rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="w-full sm:w-auto rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        Add Unit
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- ============================================================
     MODAL JAVASCRIPT
============================================================ -->

<script>

function openAddUnitModal() {
    const modal = document.getElementById('addUnitModal');

    if (!modal) {
        return;
    }

    modal.classList.remove('hidden');

    document.body.classList.add('overflow-hidden');

    const unitNumber = document.getElementById('unitNumber');

    if (unitNumber) {
        setTimeout(() => {
            unitNumber.focus();
        }, 100);
    }
}

function closeAddUnitModal() {
    const modal = document.getElementById('addUnitModal');

    if (!modal) {
        return;
    }

    modal.classList.add('hidden');

    document.body.classList.remove('overflow-hidden');
}

function toggleTenantField() {

    const status =
        document.getElementById('unitStatus');

    const tenantField =
        document.getElementById('tenantField');

    const tenantSelect =
        document.getElementById('tenantId');

    if (
        !status ||
        !tenantField ||
        !tenantSelect
    ) {
        return;
    }

    if (status.value === 'Occupied') {

        tenantField.classList.remove('hidden');

        tenantSelect.required = true;

    } else {

        tenantField.classList.add('hidden');

        tenantSelect.required = false;

        tenantSelect.value = '';
    }
}

/*
 * Close with Escape.
 */
document.addEventListener('keydown', function(event) {

    if (event.key === 'Escape') {
        closeAddUnitModal();
    }

});

/*
 * If there was a validation error, reopen the form.
 */
<?php if ($errorMessage): ?>

document.addEventListener('DOMContentLoaded', function() {

    openAddUnitModal();

    toggleTenantField();

});

<?php endif; ?>

</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>