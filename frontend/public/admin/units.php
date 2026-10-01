<?php

require_once __DIR__ . '/../../includes/api.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/data.php';

require_admin();

$pageTitle = 'Units';

/*
|--------------------------------------------------------------------------
| Unit page helper functions
|--------------------------------------------------------------------------
| All helpers use the pp_units_ prefix to avoid function-name conflicts
| with includes/data.php.
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

function pp_units_id_from_item(array $item, array $keys): string
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

    $propertyId = pp_units_id_from_item(
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

        $candidateId = pp_units_id_from_item(
            $property,
            ['_id', 'id', 'propertyId']
        );

        if ($candidateId !== '' && $candidateId === $propertyId) {
            return $property;
        }
    }

    return [];
}

function pp_units_find_tenant(array $unit): array
{
    global $tenants;

    /*
     * First try tenantId directly from the unit.
     */
    $tenantId = pp_units_id_from_item(
        $unit,
        ['tenantId', 'tenant_id']
    );

    if ($tenantId !== '') {

        foreach ($tenants as $tenant) {
            if (!is_array($tenant)) {
                continue;
            }

            $candidateId = pp_units_id_from_item(
                $tenant,
                ['_id', 'id', 'tenantId']
            );

            if ($candidateId !== '' && $candidateId === $tenantId) {
                return $tenant;
            }
        }
    }

    /*
     * Some systems store the unitId on the tenant instead.
     */
    $unitId = pp_units_id_from_item(
        $unit,
        ['_id', 'id', 'unitId']
    );

    if ($unitId !== '') {

        foreach ($tenants as $tenant) {
            if (!is_array($tenant)) {
                continue;
            }

            $tenantUnitId = pp_units_id_from_item(
                $tenant,
                ['unitId', 'unit_id', 'unit']
            );

            if ($tenantUnitId !== '' && $tenantUnitId === $unitId) {
                return $tenant;
            }
        }
    }

    return [];
}

function pp_units_tenant_name(array $unit): string
{
    /*
     * If the unit already contains tenant information, use it.
     */
    if (isset($unit['tenant']) && is_array($unit['tenant'])) {
        $name = pp_units_value(
            $unit['tenant'],
            ['name', 'fullName', 'tenantName'],
            ''
        );

        if ($name !== '') {
            return (string) $name;
        }
    }

    if (isset($unit['tenant']) && is_string($unit['tenant'])) {
        return $unit['tenant'];
    }

    /*
     * Otherwise find the tenant record.
     */
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

    if (in_array($status, ['occupied', 'rented', 'leased'], true)) {
        return true;
    }

    if (in_array($status, ['vacant', 'available', 'empty'], true)) {
        return false;
    }

    /*
     * Check for tenant assignment.
     */
    if (!empty($unit['tenantId'])) {
        return true;
    }

    if (!empty($unit['tenant'])) {
        return true;
    }

    $tenant = pp_units_find_tenant($unit);

    return !empty($tenant);
}

/*
|--------------------------------------------------------------------------
| Page data
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

            <a
                href="#"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition"
            >
                <span class="text-lg leading-none">+</span>
                Add Unit
            </a>

        </div>

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
                                    $unitNumber = pp_units_normalize_id($unitNumber);
                                }

                                if ((string) $unitNumber === '') {
                                    $unitNumber = 'Unit';
                                }

                                /*
                                 * Property
                                 */
                                $property = pp_units_find_property($unit);

                                $propertyName = '';

                                if (!empty($property)) {
                                    $propertyName = pp_units_value(
                                        $property,
                                        ['name', 'propertyName', 'title'],
                                        ''
                                    );
                                }

                                if ($propertyName === '') {
                                    if (isset($unit['property']) && is_string($unit['property'])) {
                                        $propertyName = $unit['property'];
                                    } elseif (
                                        isset($unit['property']) &&
                                        is_array($unit['property'])
                                    ) {
                                        $propertyName = pp_units_value(
                                            $unit['property'],
                                            ['name', 'propertyName', 'title'],
                                            ''
                                        );
                                    }
                                }

                                if ($propertyName === '') {
                                    $propertyName = 'Unknown Property';
                                }

                                /*
                                 * Tenant
                                 */
                                $tenantName = pp_units_tenant_name($unit);

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
                                $occupied = pp_units_is_occupied($unit);

                                $status = $occupied
                                    ? 'Occupied'
                                    : 'Vacant';

                                if ($occupied) {
                                    $statusClass = 'bg-indigo-100 text-indigo-700';
                                } else {
                                    $statusClass = 'bg-emerald-100 text-emerald-700';
                                }
                                ?>

                                <tr class="hover:bg-slate-50 transition">

                                    <!-- Unit -->
                                    <td class="px-6 py-4">

                                        <div class="font-semibold text-slate-900">
                                            <?= e($unitNumber) ?>
                                        </div>

                                    </td>

                                    <!-- Property -->
                                    <td class="px-6 py-4">

                                        <div class="font-medium text-slate-700">
                                            <?= e($propertyName) ?>
                                        </div>

                                    </td>

                                    <!-- Tenant -->
                                    <td class="px-6 py-4">

                                        <div class="<?= $tenantName === 'Vacant'
                                            ? 'text-slate-400'
                                            : 'font-medium text-slate-700' ?>">
                                            <?= e($tenantName) ?>
                                        </div>

                                    </td>

                                    <!-- Rent -->
                                    <td class="px-6 py-4">

                                        <div class="font-semibold text-slate-900">
                                            KES <?= number_format($rent, 2) ?>
                                        </div>

                                    </td>

                                    <!-- Status -->
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

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>