<?php

/**
 * ============================================================
 * PropertyPro - Admin Properties
 * ============================================================
 */

require_once __DIR__ . '/../../includes/api.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

/*
|--------------------------------------------------------------------------
| Create Property
|--------------------------------------------------------------------------
*/

$createError = '';
$createSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'create_property') {

        $name = trim((string) ($_POST['name'] ?? ''));
        $location = trim((string) ($_POST['location'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? 'Active'));

        if ($name === '') {
            $createError = 'Property name is required.';
        } elseif ($location === '') {
            $createError = 'Property location is required.';
        } elseif (!in_array($status, ['Active', 'Inactive'], true)) {
            $createError = 'Invalid property status.';
        } else {

            /*
             * Your current backend requires propertyId.
             * Generate one automatically so the admin does not
             * have to type it manually.
             */
            try {
                $propertyId =
                    'PROP-' .
                    date('YmdHis') .
                    '-' .
                    random_int(100, 999);
            } catch (Throwable $exception) {
                $propertyId =
                    'PROP-' .
                    date('YmdHis');
            }

            $payload = [
                'propertyId' => $propertyId,
                'name' => $name,
                'location' => $location,
                'address' => $address,
                'description' => $description,
                'totalUnits' => 0,
                'status' => $status,
            ];

            $result = api_post(
                '/properties',
                $payload
            );

            if (
                is_array($result) &&
                !empty($result['success'])
            ) {

                header(
                    'Location: properties.php?created=1'
                );

                exit;

            }

            $createError =
                is_array($result) &&
                !empty($result['message'])
                    ? (string) $result['message']
                    : 'Unable to create property. Please try again.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| Data
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../includes/data.php';

$pageTitle = 'Properties';

if (isset($_GET['created'])) {
    $createSuccess = 'Property created successfully.';
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
|
| These names are intentionally prefixed so they do not conflict
| with functions already declared inside data.php.
|
|--------------------------------------------------------------------------
*/

function pp_properties_value(
    array $property,
    array $keys,
    $default = ''
) {
    foreach ($keys as $key) {

        if (
            array_key_exists($key, $property) &&
            $property[$key] !== null &&
            $property[$key] !== ''
        ) {
            return $property[$key];
        }
    }

    return $default;
}

function pp_properties_id($value): string
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

function pp_properties_aliases(array $item): array
{
    $aliases = [];

    foreach (
        [
            $item['_id'] ?? null,
            $item['id'] ?? null,
            $item['propertyId'] ?? null,
        ]
        as $value
    ) {

        $id = pp_properties_id($value);

        if (
            $id !== '' &&
            !in_array($id, $aliases, true)
        ) {
            $aliases[] = $id;
        }
    }

    return $aliases;
}

function pp_properties_unit_aliases(array $unit): array
{
    $aliases = [];

    foreach (
        [
            $unit['propertyId'] ?? null,
            $unit['property'] ?? null,
            $unit['property_id'] ?? null,
            $unit['propertyIdValue'] ?? null,
        ]
        as $value
    ) {

        $id = pp_properties_id($value);

        if (
            $id !== '' &&
            !in_array($id, $aliases, true)
        ) {
            $aliases[] = $id;
        }
    }

    return $aliases;
}

function pp_properties_is_occupied(
    array $unit
): bool {

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

    if (!empty($unit['tenantId'])) {
        return true;
    }

    if (!empty($unit['tenant'])) {
        return true;
    }

    return false;
}

/*
|--------------------------------------------------------------------------
| Property Statistics
|--------------------------------------------------------------------------
*/

$propertyStats = [];

foreach ($units as $unit) {

    if (!is_array($unit)) {
        continue;
    }

    $unitAliases =
        pp_properties_unit_aliases($unit);

    if (empty($unitAliases)) {
        continue;
    }

    /*
     * Use the first available alias as the key.
     */
    $propertyKey = $unitAliases[0];

    if (!isset($propertyStats[$propertyKey])) {

        $propertyStats[$propertyKey] = [
            'units' => 0,
            'occupied' => 0,
            'vacant' => 0,
        ];
    }

    $propertyStats[$propertyKey]['units']++;

    if (
        pp_properties_is_occupied($unit)
    ) {

        $propertyStats[$propertyKey]['occupied']++;

    } else {

        $propertyStats[$propertyKey]['vacant']++;
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

    <!-- ========================================================
         Header
    ========================================================= -->

    <header class="border-b border-slate-200 bg-white">

        <div class="flex min-h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">

            <div>

                <h1 class="text-xl font-bold text-slate-900">
                    Properties
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Manage your rental properties.
                </p>

            </div>

            <button
                id="openPropertyModal"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
            >
                <span class="text-lg leading-none">+</span>
                Add Property
            </button>

        </div>

    </header>

    <!-- ========================================================
         Main Content
    ========================================================= -->

    <div class="p-4 sm:p-6 lg:p-8">

        <?php if ($createSuccess !== ''): ?>

            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm font-medium text-emerald-700">
                <?= e($createSuccess) ?>
            </div>

        <?php endif; ?>

        <?php if ($createError !== ''): ?>

            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-4 text-sm font-medium text-red-700">
                <?= e($createError) ?>
            </div>

        <?php endif; ?>

        <!-- ====================================================
             Summary Cards
        ===================================================== -->

        <?php
        $totalProperties = count($properties);

        $totalUnitsAll = 0;
        $totalOccupiedAll = 0;
        $totalVacantAll = 0;

        foreach ($propertyStats as $stats) {

            $totalUnitsAll +=
                (int) ($stats['units'] ?? 0);

            $totalOccupiedAll +=
                (int) ($stats['occupied'] ?? 0);

            $totalVacantAll +=
                (int) ($stats['vacant'] ?? 0);
        }

        $overallOccupancy = 0;

        if ($totalUnitsAll > 0) {
            $overallOccupancy =
                ($totalOccupiedAll / $totalUnitsAll) * 100;
        }
        ?>

        <div class="mb-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

            <!-- Properties -->

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-sm font-medium text-slate-500">
                            Total Properties
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            <?= $totalProperties ?>
                        </p>

                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-2xl">
                        🏢
                    </div>

                </div>

            </div>

            <!-- Units -->

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-sm font-medium text-slate-500">
                            Total Units
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            <?= $totalUnitsAll ?>
                        </p>

                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-2xl">
                        🚪
                    </div>

                </div>

            </div>

            <!-- Occupied -->

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-sm font-medium text-slate-500">
                            Occupied Units
                        </p>

                        <p class="mt-2 text-3xl font-bold text-indigo-600">
                            <?= $totalOccupiedAll ?>
                        </p>

                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-2xl">
                        👤
                    </div>

                </div>

            </div>

            <!-- Occupancy -->

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-sm font-medium text-slate-500">
                            Occupancy
                        </p>

                        <p class="mt-2 text-3xl font-bold text-emerald-600">
                            <?= number_format($overallOccupancy, 1) ?>%
                        </p>

                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-2xl">
                        📊
                    </div>

                </div>

            </div>

        </div>

        <!-- ====================================================
             Properties
        ===================================================== -->

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-5 sm:px-6">

                <h2 class="text-lg font-semibold text-slate-900">
                    All Properties
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    View and manage all properties in the system.
                </p>

            </div>

            <div class="p-5 sm:p-6">

                <?php if (empty($properties)): ?>

                    <div class="py-12 text-center">

                        <div class="text-5xl">
                            🏢
                        </div>

                        <h3 class="mt-4 text-lg font-semibold text-slate-900">
                            No properties found
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Add your first property to get started.
                        </p>

                        <button
                            id="openPropertyModalEmpty"
                            type="button"
                            class="mt-5 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                        >
                            + Add Property
                        </button>

                    </div>

                <?php else: ?>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">

                        <?php foreach ($properties as $property): ?>

                            <?php
                            if (!is_array($property)) {
                                continue;
                            }

                            /*
                             * Keep all property IDs.
                             */
                            $propertyAliases =
                                pp_properties_aliases($property);

                            /*
                             * IMPORTANT:
                             * Prefer MongoDB _id for the details page.
                             */
                            $mongoId =
                                pp_properties_id(
                                    $property['_id']
                                    ?? ''
                                );

                            if ($mongoId === '') {
                                $mongoId =
                                    pp_properties_id(
                                        $property['id']
                                        ?? $property['propertyId']
                                        ?? ''
                                    );
                            }

                            $propertyName =
                                pp_properties_value(
                                    $property,
                                    [
                                        'name',
                                        'propertyName',
                                        'title',
                                    ],
                                    'Unnamed Property'
                                );

                            $location =
                                pp_properties_value(
                                    $property,
                                    [
                                        'location',
                                        'address',
                                    ],
                                    'Location not specified'
                                );

                            $address =
                                pp_properties_value(
                                    $property,
                                    [
                                        'address',
                                    ],
                                    ''
                                );

                            $customPropertyId =
                                pp_properties_value(
                                    $property,
                                    [
                                        'propertyId',
                                    ],
                                    ''
                                );

                            $status =
                                pp_properties_value(
                                    $property,
                                    [
                                        'status',
                                    ],
                                    'Active'
                                );

                            /*
                             * Find statistics by matching every known
                             * identifier for the property.
                             */
                            $stats = [
                                'units' => 0,
                                'occupied' => 0,
                                'vacant' => 0,
                            ];

                            foreach ($propertyAliases as $alias) {

                                if (
                                    isset(
                                        $propertyStats[$alias]
                                    )
                                ) {

                                    $stats =
                                        $propertyStats[$alias];

                                    break;
                                }
                            }

                            $totalUnits =
                                (int) (
                                    $stats['units']
                                    ?? 0
                                );

                            $occupiedUnits =
                                (int) (
                                    $stats['occupied']
                                    ?? 0
                                );

                            $vacantUnits =
                                (int) (
                                    $stats['vacant']
                                    ?? 0
                                );

                            $statusLower =
                                strtolower(
                                    (string) $status
                                );

                            if (
                                $statusLower === 'active'
                            ) {

                                $statusClass =
                                    'bg-emerald-100 text-emerald-700';

                            } elseif (
                                $statusLower === 'inactive'
                            ) {

                                $statusClass =
                                    'bg-slate-100 text-slate-700';

                            } else {

                                $statusClass =
                                    'bg-amber-100 text-amber-700';
                            }

                            $detailsUrl =
                                'property-details.php?id=' .
                                urlencode($mongoId);
                            ?>

                            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:-translate-y-0.5 hover:shadow-md">

                                <!-- Property Header -->

                                <div class="border-b border-slate-200 p-5">

                                    <div class="flex items-start justify-between gap-3">

                                        <div class="min-w-0">

                                            <h3 class="truncate text-lg font-bold text-slate-900">
                                                <?= e($propertyName) ?>
                                            </h3>

                                            <p class="mt-1 text-sm text-slate-500">
                                                <?= e($location) ?>
                                            </p>

                                        </div>

                                        <span
                                            class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold <?= e($statusClass) ?>"
                                        >
                                            <?= e($status) ?>
                                        </span>

                                    </div>

                                    <?php if ($customPropertyId !== ''): ?>

                                        <p class="mt-3 text-xs text-slate-400">
                                            ID:
                                            <span class="font-medium text-slate-500">
                                                <?= e($customPropertyId) ?>
                                            </span>
                                        </p>

                                    <?php endif; ?>

                                    <?php if (
                                        $address !== '' &&
                                        $address !== $location
                                    ): ?>

                                        <p class="mt-1 text-xs text-slate-400">
                                            <?= e($address) ?>
                                        </p>

                                    <?php endif; ?>

                                </div>

                                <!-- Statistics -->

                                <div class="grid grid-cols-3 divide-x divide-slate-200">

                                    <div class="p-4 text-center">

                                        <p class="text-2xl font-bold text-slate-900">
                                            <?= $totalUnits ?>
                                        </p>

                                        <p class="mt-1 text-xs font-medium text-slate-500">
                                            Units
                                        </p>

                                    </div>

                                    <div class="p-4 text-center">

                                        <p class="text-2xl font-bold text-indigo-600">
                                            <?= $occupiedUnits ?>
                                        </p>

                                        <p class="mt-1 text-xs font-medium text-slate-500">
                                            Occupied
                                        </p>

                                    </div>

                                    <div class="p-4 text-center">

                                        <p class="text-2xl font-bold text-emerald-600">
                                            <?= $vacantUnits ?>
                                        </p>

                                        <p class="mt-1 text-xs font-medium text-slate-500">
                                            Vacant
                                        </p>

                                    </div>

                                </div>

                                <!-- Action -->

                                <div class="border-t border-slate-200 bg-slate-50 p-4">

                                    <a
                                        href="<?= e($detailsUrl) ?>"
                                        class="inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                                    >
                                        View Property
                                    </a>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

</main>

<!-- ============================================================
     Add Property Modal
============================================================= -->

<div
    id="propertyModal"
    class="fixed inset-0 z-50 hidden"
    aria-hidden="true"
>

    <!-- Overlay -->

    <div
        id="propertyModalOverlay"
        class="absolute inset-0 bg-slate-900/50"
    ></div>

    <!-- Modal -->

    <div class="relative flex min-h-full items-center justify-center p-4">

        <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl">

            <!-- Modal Header -->

            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">

                <div>

                    <h2 class="text-lg font-bold text-slate-900">
                        Add Property
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Enter the details of the rental property.
                    </p>

                </div>

                <button
                    id="closePropertyModal"
                    type="button"
                    class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700"
                >
                    ✕
                </button>

            </div>

            <!-- Form -->

            <form
                method="POST"
                action="properties.php"
                class="p-5 sm:p-6"
            >

                <input
                    type="hidden"
                    name="action"
                    value="create_property"
                >

                <div class="grid gap-5 sm:grid-cols-2">

                    <!-- Name -->

                    <div class="sm:col-span-2">

                        <label
                            for="propertyName"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Property Name
                        </label>

                        <input
                            id="propertyName"
                            name="name"
                            type="text"
                            required
                            maxlength="150"
                            placeholder="e.g. Greenview Apartments"
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >

                    </div>

                    <!-- Location -->

                    <div>

                        <label
                            for="propertyLocation"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Location
                        </label>

                        <input
                            id="propertyLocation"
                            name="location"
                            type="text"
                            required
                            maxlength="150"
                            placeholder="e.g. Nairobi, Kilimani"
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >

                    </div>

                    <!-- Address -->

                    <div>

                        <label
                            for="propertyAddress"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Address
                            <span class="font-normal text-slate-400">
                                (Optional)
                            </span>
                        </label>

                        <input
                            id="propertyAddress"
                            name="address"
                            type="text"
                            maxlength="200"
                            placeholder="e.g. Mombasa Road"
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >

                    </div>

                    <!-- Status -->

                    <div>

                        <label
                            for="propertyStatus"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Status
                        </label>

                        <select
                            id="propertyStatus"
                            name="status"
                            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >

                            <option value="Active">
                                Active
                            </option>

                            <option value="Inactive">
                                Inactive
                            </option>

                        </select>

                    </div>

                    <!-- Description -->

                    <div class="sm:col-span-2">

                        <label
                            for="propertyDescription"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Description
                            <span class="font-normal text-slate-400">
                                (Optional)
                            </span>
                        </label>

                        <textarea
                            id="propertyDescription"
                            name="description"
                            rows="4"
                            maxlength="1000"
                            placeholder="Describe the property..."
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        ></textarea>

                    </div>

                </div>

                <div class="mt-6 rounded-lg border border-indigo-100 bg-indigo-50 px-4 py-3">

                    <p class="text-xs leading-5 text-indigo-700">
                        A unique property ID will be generated automatically.
                        Units can be added to this property later from the
                        <strong>Units</strong> section.
                    </p>

                </div>

                <!-- Buttons -->

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

                    <button
                        id="cancelPropertyModal"
                        type="button"
                        class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"
                    >
                        Create Property
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const modal =
        document.getElementById('propertyModal');

    const openButton =
        document.getElementById('openPropertyModal');

    const openEmptyButton =
        document.getElementById('openPropertyModalEmpty');

    const closeButton =
        document.getElementById('closePropertyModal');

    const cancelButton =
        document.getElementById('cancelPropertyModal');

    const overlay =
        document.getElementById('propertyModalOverlay');

    function openModal() {

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

        const nameInput =
            document.getElementById(
                'propertyName'
            );

        if (nameInput) {
            setTimeout(function () {
                nameInput.focus();
            }, 100);
        }
    }

    function closeModal() {

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

    if (openButton) {
        openButton.addEventListener(
            'click',
            openModal
        );
    }

    if (openEmptyButton) {
        openEmptyButton.addEventListener(
            'click',
            openModal
        );
    }

    if (closeButton) {
        closeButton.addEventListener(
            'click',
            closeModal
        );
    }

    if (cancelButton) {
        cancelButton.addEventListener(
            'click',
            closeModal
        );
    }

    if (overlay) {
        overlay.addEventListener(
            'click',
            closeModal
        );
    }

    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape'
            ) {
                closeModal();
            }
        }
    );

});

</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>