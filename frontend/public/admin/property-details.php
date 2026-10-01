<?php

/**
 * ============================================================
 * PropertyPro - Admin Property Details
 * ============================================================
 */

require_once __DIR__ . '/../../includes/api.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

require_once __DIR__ . '/../../includes/data.php';

$pageTitle = 'Property Details';

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function pp_details_id($value): string
{
    if (is_array($value)) {

        return (string) (
            $value['_id']
            ?? $value['id']
            ?? $value['propertyId']
            ?? ''
        );
    }

    return (string) ($value ?? '');
}

function pp_details_aliases(array $property): array
{
    $aliases = [];

    foreach (
        [
            $property['_id'] ?? null,
            $property['id'] ?? null,
            $property['propertyId'] ?? null,
        ]
        as $value
    ) {

        $id = pp_details_id($value);

        if (
            $id !== '' &&
            !in_array($id, $aliases, true)
        ) {
            $aliases[] = $id;
        }
    }

    return $aliases;
}

function pp_details_unit_aliases(array $unit): array
{
    $aliases = [];

    foreach (
        [
            $unit['propertyId'] ?? null,
            $unit['propertyIdValue'] ?? null,
            $unit['property'] ?? null,
            $unit['property_id'] ?? null,
        ]
        as $value
    ) {

        $id = pp_details_id($value);

        if (
            $id !== '' &&
            !in_array($id, $aliases, true)
        ) {
            $aliases[] = $id;
        }
    }

    return $aliases;
}

function pp_details_occupied(array $unit): bool
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
            [
                'occupied',
                'rented',
                'leased',
            ],
            true
        )
    ) {
        return true;
    }

    if (
        in_array(
            $status,
            [
                'vacant',
                'available',
                'empty',
            ],
            true
        )
    ) {
        return false;
    }

    return !empty(
        $unit['tenantId']
    );
}

/*
|--------------------------------------------------------------------------
| Requested ID
|--------------------------------------------------------------------------
*/

$requestedId =
    trim(
        (string) (
            $_GET['id']
            ?? ''
        )
    );

if ($requestedId === '') {

    http_response_code(400);

    exit(
        'Property ID is missing.'
    );
}

/*
|--------------------------------------------------------------------------
| Find Property
|--------------------------------------------------------------------------
*/

$property = null;
$propertyAliases = [];

foreach ($properties as $item) {

    if (!is_array($item)) {
        continue;
    }

    $aliases =
        pp_details_aliases($item);

    if (
        in_array(
            $requestedId,
            $aliases,
            true
        )
    ) {

        $property = $item;
        $propertyAliases = $aliases;

        break;
    }
}

/*
|--------------------------------------------------------------------------
| API fallback
|--------------------------------------------------------------------------
*/

if (!$property) {

    $response = api_get(
        '/properties/' .
        rawurlencode($requestedId)
    );

    if (
        is_array($response) &&
        !empty($response['success']) &&
        isset($response['data']) &&
        is_array($response['data'])
    ) {

        $property =
            $response['data'];

        $propertyAliases =
            pp_details_aliases(
                $property
            );
    }
}

/*
|--------------------------------------------------------------------------
| Not Found
|--------------------------------------------------------------------------
*/

if (!$property) {

    http_response_code(404);

    require_once __DIR__ . '/../../includes/header.php';
    require_once __DIR__ . '/../../includes/sidebar.php';

    ?>

    <main class="min-h-screen bg-slate-50 lg:ml-64">

        <div class="p-4 sm:p-6 lg:p-8">

            <div class="mx-auto max-w-2xl rounded-2xl border border-red-200 bg-white p-8 text-center shadow-sm">

                <div class="text-5xl">
                    ⚠️
                </div>

                <h1 class="mt-4 text-2xl font-bold text-slate-900">
                    Property Not Found
                </h1>

                <p class="mt-2 text-sm text-slate-500">
                    The requested property could not be found.
                </p>

                <p class="mt-3 break-all text-xs text-slate-400">
                    ID:
                    <?= e($requestedId) ?>
                </p>

                <a
                    href="properties.php"
                    class="mt-6 inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                >
                    ← Back to Properties
                </a>

            </div>

        </div>

    </main>

    <?php

    require_once __DIR__ . '/../../includes/footer.php';

    exit;
}

/*
|--------------------------------------------------------------------------
| Property Information
|--------------------------------------------------------------------------
*/

$propertyName =
    $property['name']
    ?? $property['propertyName']
    ?? 'Unnamed Property';

$location =
    $property['location']
    ?? 'Location not specified';

$address =
    $property['address']
    ?? '';

$description =
    $property['description']
    ?? '';

$status =
    $property['status']
    ?? 'Active';

$propertyId =
    $property['propertyId']
    ?? '';

$mongoId =
    pp_details_id(
        $property['_id']
        ?? ''
    );

/*
|--------------------------------------------------------------------------
| Unit Statistics
|--------------------------------------------------------------------------
*/

$propertyUnits = [];

$occupiedUnits = 0;
$vacantUnits = 0;
$monthlyIncome = 0;

foreach ($units as $unit) {

    if (!is_array($unit)) {
        continue;
    }

    $unitAliases =
        pp_details_unit_aliases(
            $unit
        );

    $matches = false;

    foreach ($propertyAliases as $alias) {

        if (
            in_array(
                $alias,
                $unitAliases,
                true
            )
        ) {

            $matches = true;

            break;
        }
    }

    if (!$matches) {
        continue;
    }

    $propertyUnits[] =
        $unit;

    if (
        pp_details_occupied($unit)
    ) {

        $occupiedUnits++;

        $monthlyIncome +=
            (float) (
                $unit['rent']
                ?? 0
            );

    } else {

        $vacantUnits++;
    }
}

$totalUnits =
    count($propertyUnits);

$occupancyRate = 0;

if ($totalUnits > 0) {

    $occupancyRate =
        (
            $occupiedUnits /
            $totalUnits
        ) * 100;
}

$pageTitle = $propertyName;

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

?>

<main class="min-h-screen bg-slate-50 lg:ml-64">

    <!-- Header -->

    <header class="border-b border-slate-200 bg-white">

        <div class="flex min-h-16 items-center gap-4 px-4 sm:px-6 lg:px-8">

            <a
                href="properties.php"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-700"
            >
                ← Properties
            </a>

            <span class="text-slate-300">
                /
            </span>

            <h1 class="truncate text-lg font-semibold text-slate-900">
                <?= e($propertyName) ?>
            </h1>

        </div>

    </header>

    <!-- Content -->

    <div class="p-4 sm:p-6 lg:p-8">

        <!-- Property -->

        <div class="mb-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                <div>

                    <div class="flex flex-wrap items-center gap-3">

                        <h2 class="text-2xl font-bold text-slate-900">
                            <?= e($propertyName) ?>
                        </h2>

                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                            <?= e($status) ?>
                        </span>

                    </div>

                    <p class="mt-2 text-sm text-slate-500">
                        <?= e($location) ?>
                    </p>

                    <?php if ($propertyId !== ''): ?>

                        <p class="mt-2 text-xs text-slate-400">
                            Property ID:
                            <span class="font-medium text-slate-500">
                                <?= e($propertyId) ?>
                            </span>
                        </p>

                    <?php endif; ?>

                </div>

                <a
                    href="units.php"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Manage Units
                </a>

            </div>

            <?php if ($address !== ''): ?>

                <div class="mt-6">

                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Address
                    </p>

                    <p class="mt-2 text-sm text-slate-700">
                        <?= e($address) ?>
                    </p>

                </div>

            <?php endif; ?>

            <?php if ($description !== ''): ?>

                <div class="mt-5">

                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Description
                    </p>

                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        <?= e($description) ?>
                    </p>

                </div>

            <?php endif; ?>

        </div>

        <!-- Statistics -->

        <div class="mb-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm font-medium text-slate-500">
                    Total Units
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-900">
                    <?= $totalUnits ?>
                </p>

            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm font-medium text-slate-500">
                    Occupied
                </p>

                <p class="mt-2 text-3xl font-bold text-indigo-600">
                    <?= $occupiedUnits ?>
                </p>

            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm font-medium text-slate-500">
                    Vacant
                </p>

                <p class="mt-2 text-3xl font-bold text-emerald-600">
                    <?= $vacantUnits ?>
                </p>

            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm font-medium text-slate-500">
                    Monthly Rent
                </p>

                <p class="mt-2 text-2xl font-bold text-slate-900">
                    <?= e(money($monthlyIncome)) ?>
                </p>

            </div>

        </div>

        <!-- Occupancy -->

        <div class="mb-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex items-center justify-between">

                <div>

                    <h3 class="text-lg font-semibold text-slate-900">
                        Occupancy
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Current occupancy for this property.
                    </p>

                </div>

                <p class="text-2xl font-bold text-slate-900">
                    <?= number_format($occupancyRate, 1) ?>%
                </p>

            </div>

            <div class="mt-5 h-3 overflow-hidden rounded-full bg-slate-100">

                <div
                    class="h-full rounded-full bg-indigo-600"
                    style="width: <?= min(100, max(0, $occupancyRate)) ?>%;"
                ></div>

            </div>

        </div>

        <!-- Units -->

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-5 sm:px-6">

                <h3 class="text-lg font-semibold text-slate-900">
                    Property Units
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Units belonging to this property.
                </p>

            </div>

            <?php if (empty($propertyUnits)): ?>

                <div class="px-6 py-12 text-center">

                    <div class="text-4xl">
                        🚪
                    </div>

                    <h4 class="mt-3 text-lg font-semibold text-slate-900">
                        No units yet
                    </h4>

                    <p class="mt-1 text-sm text-slate-500">
                        Add units from the Units page.
                    </p>

                    <a
                        href="units.php"
                        class="mt-5 inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        Add Unit
                    </a>

                </div>

            <?php else: ?>

                <div class="overflow-x-auto">

                    <table class="min-w-full text-left text-sm">

                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">

                            <tr>

                                <th class="px-5 py-4">
                                    Unit
                                </th>

                                <th class="px-5 py-4">
                                    Type
                                </th>

                                <th class="px-5 py-4">
                                    Rent
                                </th>

                                <th class="px-5 py-4">
                                    Status
                                </th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            <?php foreach ($propertyUnits as $unit): ?>

                                <?php

                                $unitNumber =
                                    $unit['unitNumber']
                                    ?? $unit['unitId']
                                    ?? '-';

                                $unitType =
                                    $unit['type']
                                    ?? '-';

                                $rent =
                                    (float) (
                                        $unit['rent']
                                        ?? 0
                                    );

                                $isOccupied =
                                    pp_details_occupied(
                                        $unit
                                    );

                                if ($isOccupied) {

                                    $unitStatus =
                                        'Occupied';

                                    $unitStatusClass =
                                        'bg-indigo-100 text-indigo-700';

                                } else {

                                    $unitStatus =
                                        'Vacant';

                                    $unitStatusClass =
                                        'bg-emerald-100 text-emerald-700';
                                }

                                ?>

                                <tr class="hover:bg-slate-50">

                                    <td class="px-5 py-4 font-semibold text-slate-900">
                                        <?= e($unitNumber) ?>
                                    </td>

                                    <td class="px-5 py-4 text-slate-600">
                                        <?= e($unitType) ?>
                                    </td>

                                    <td class="px-5 py-4 font-medium text-slate-900">
                                        <?= e(money($rent)) ?>
                                    </td>

                                    <td class="px-5 py-4">

                                        <span
                                            class="rounded-full px-2.5 py-1 text-xs font-semibold <?= e($unitStatusClass) ?>"
                                        >
                                            <?= e($unitStatus) ?>
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