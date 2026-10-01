<?php

require_once __DIR__ . '/../../includes/api.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/data.php';

require_admin();

$pageTitle = 'Properties';

/*
|--------------------------------------------------------------------------
| Helper functions
|--------------------------------------------------------------------------
*/

function property_value(array $property, array $keys, $default = '')
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $property) && $property[$key] !== null) {
            return $property[$key];
        }
    }

    return $default;
}

function normalize_id($value): string
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

function get_property_id(array $property): string
{
    return normalize_id(
        property_value(
            $property,
            ['_id', 'id', 'propertyId'],
            ''
        )
    );
}

function get_unit_property_id(array $unit): string
{
    foreach (['propertyId', 'property', 'property_id'] as $key) {
        if (array_key_exists($key, $unit)) {
            return normalize_id($unit[$key]);
        }
    }

    return '';
}

function unit_is_occupied(array $unit): bool
{
    $status = strtolower(trim((string) (
        $unit['status']
        ?? $unit['unitStatus']
        ?? ''
    )));

    if (in_array($status, ['occupied', 'rented', 'leased'], true)) {
        return true;
    }

    if (in_array($status, ['vacant', 'available', 'empty'], true)) {
        return false;
    }

    /*
     * Some unit records may indicate occupancy using a tenant.
     */
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
| Calculate statistics from the actual units
|--------------------------------------------------------------------------
*/

$propertyStats = [];

foreach ($units as $unit) {
    if (!is_array($unit)) {
        continue;
    }

    $propertyId = get_unit_property_id($unit);

    if ($propertyId === '') {
        continue;
    }

    if (!isset($propertyStats[$propertyId])) {
        $propertyStats[$propertyId] = [
            'units' => 0,
            'occupied' => 0,
            'vacant' => 0,
        ];
    }

    $propertyStats[$propertyId]['units']++;

    if (unit_is_occupied($unit)) {
        $propertyStats[$propertyId]['occupied']++;
    } else {
        $propertyStats[$propertyId]['vacant']++;
    }
}

/*
|--------------------------------------------------------------------------
| Page
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
                    Properties
                </h1>

                <p class="mt-1 text-sm text-slate-600">
                    Manage your rental properties
                </p>
            </div>

            <a
                href="#"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition"
            >
                <span class="text-lg leading-none">+</span>
                Add Property
            </a>

        </div>

        <!-- Section -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200">

            <div class="px-5 py-5 sm:px-6 border-b border-slate-200">

                <h2 class="text-lg font-semibold text-slate-900">
                    All Properties
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    View and manage all properties in your portfolio.
                </p>

            </div>

            <!-- Properties -->
            <div class="p-5 sm:p-6">

                <?php if (empty($properties)): ?>

                    <div class="text-center py-12">

                        <div class="text-slate-400 text-4xl mb-3">
                            🏢
                        </div>

                        <h3 class="text-lg font-semibold text-slate-900">
                            No properties found
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            There are currently no properties in the system.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

                        <?php foreach ($properties as $property): ?>

                            <?php
                            if (!is_array($property)) {
                                continue;
                            }

                            $propertyId = get_property_id($property);

                            $propertyName = property_value(
                                $property,
                                ['name', 'propertyName', 'title'],
                                'Unnamed Property'
                            );

                            $city = property_value(
                                $property,
                                ['city'],
                                ''
                            );

                            $area = property_value(
                                $property,
                                ['area', 'location', 'address'],
                                ''
                            );

                            $status = property_value(
                                $property,
                                ['status'],
                                'Active'
                            );

                            /*
                             * Get statistics calculated from $units.
                             */
                            $stats = $propertyStats[$propertyId] ?? [
                                'units' => 0,
                                'occupied' => 0,
                                'vacant' => 0,
                            ];

                            $totalUnits = (int) $stats['units'];
                            $occupiedUnits = (int) $stats['occupied'];
                            $vacantUnits = (int) $stats['vacant'];

                            $statusLower = strtolower((string) $status);

                            if ($statusLower === 'active') {
                                $statusClass = 'bg-emerald-100 text-emerald-700';
                            } elseif ($statusLower === 'inactive') {
                                $statusClass = 'bg-slate-100 text-slate-700';
                            } elseif ($statusLower === 'maintenance') {
                                $statusClass = 'bg-amber-100 text-amber-700';
                            } else {
                                $statusClass = 'bg-blue-100 text-blue-700';
                            }

                            $locationParts = array_filter([
                                $city,
                                $area
                            ]);

                            $location = !empty($locationParts)
                                ? implode(', ', $locationParts)
                                : 'Location not specified';

                            $detailsUrl = 'property-details.php';

                            if ($propertyId !== '') {
                                $detailsUrl .= '?id=' . urlencode($propertyId);
                            }
                            ?>

                            <div class="border border-slate-200 rounded-xl overflow-hidden hover:shadow-md transition">

                                <!-- Property header -->
                                <div class="p-5 border-b border-slate-200">

                                    <div class="flex items-start justify-between gap-3">

                                        <div class="min-w-0">

                                            <h3 class="text-lg font-bold text-slate-900 truncate">
                                                <?= e($propertyName) ?>
                                            </h3>

                                            <p class="mt-1 text-sm text-slate-500">
                                                <?= e($location) ?>
                                            </p>

                                        </div>

                                        <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= e($statusClass) ?>">
                                            <?= e($status) ?>
                                        </span>

                                    </div>

                                </div>

                                <!-- Statistics -->
                                <div class="grid grid-cols-3 divide-x divide-slate-200">

                                    <!-- Units -->
                                    <div class="p-4 text-center">

                                        <div class="text-2xl font-bold text-slate-900">
                                            <?= $totalUnits ?>
                                        </div>

                                        <div class="mt-1 text-xs font-medium text-slate-500">
                                            Units
                                        </div>

                                    </div>

                                    <!-- Occupied -->
                                    <div class="p-4 text-center">

                                        <div class="text-2xl font-bold text-indigo-600">
                                            <?= $occupiedUnits ?>
                                        </div>

                                        <div class="mt-1 text-xs font-medium text-slate-500">
                                            Occupied
                                        </div>

                                    </div>

                                    <!-- Vacant -->
                                    <div class="p-4 text-center">

                                        <div class="text-2xl font-bold text-emerald-600">
                                            <?= $vacantUnits ?>
                                        </div>

                                        <div class="mt-1 text-xs font-medium text-slate-500">
                                            Vacant
                                        </div>

                                    </div>

                                </div>

                                <!-- Footer -->
                                <div class="px-5 py-4 bg-slate-50 border-t border-slate-200">

                                    <a
                                        href="<?= e($detailsUrl) ?>"
                                        class="inline-flex items-center justify-center w-full px-4 py-2 rounded-lg border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-100 transition"
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

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>