<?php

require_once "../../includes/admin.php";
require_admin();

require_once "../../includes/data.php";

$pageTitle = "Properties";

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";

/*
|--------------------------------------------------------------------------
| Property Helpers
|--------------------------------------------------------------------------
*/

function property_value(array $property, string $key, $default = '-')
{
    if (array_key_exists($key, $property) && $property[$key] !== null) {
        return $property[$key];
    }

    return $default;
}

function property_id(array $property): string
{
    return (string) (
        $property['id']
        ?? $property['_id']
        ?? $property['propertyId']
        ?? ''
    );
}

function property_name(array $property): string
{
    return (string) (
        $property['name']
        ?? $property['propertyName']
        ?? 'Unnamed Property'
    );
}

function property_location(array $property): string
{
    $city = $property['city'] ?? '';
    $area = $property['area'] ?? ($property['location'] ?? '');

    if (is_array($area)) {
        $area = $area['name']
            ?? $area['area']
            ?? $area['location']
            ?? '';
    }

    if ($city !== '' && $area !== '' && $city !== $area) {
        return $city . ', ' . $area;
    }

    if ($city !== '') {
        return $city;
    }

    if ($area !== '') {
        return $area;
    }

    return '-';
}

function property_units(array $property): int
{
    if (isset($property['units']) && is_numeric($property['units'])) {
        return (int) $property['units'];
    }

    if (isset($property['unitCount']) && is_numeric($property['unitCount'])) {
        return (int) $property['unitCount'];
    }

    if (isset($property['units']) && is_array($property['units'])) {
        return count($property['units']);
    }

    return 0;
}

function property_occupied(array $property): int
{
    if (isset($property['occupied']) && is_numeric($property['occupied'])) {
        return (int) $property['occupied'];
    }

    if (isset($property['occupiedUnits']) && is_numeric($property['occupiedUnits'])) {
        return (int) $property['occupiedUnits'];
    }

    return 0;
}

function property_vacant(array $property): int
{
    if (isset($property['vacant']) && is_numeric($property['vacant'])) {
        return (int) $property['vacant'];
    }

    if (isset($property['vacantUnits']) && is_numeric($property['vacantUnits'])) {
        return (int) $property['vacantUnits'];
    }

    $total = property_units($property);
    $occupied = property_occupied($property);

    return max(0, $total - $occupied);
}

function property_status(array $property): string
{
    return (string) (
        $property['status']
        ?? 'Active'
    );
}

function status_classes(string $status): string
{
    $statusLower = strtolower($status);

    if ($statusLower === 'inactive') {
        return 'bg-slate-100 text-slate-600';
    }

    if ($statusLower === 'maintenance') {
        return 'bg-amber-100 text-amber-700';
    }

    return 'bg-green-100 text-green-700';
}

?>

<main class="min-h-screen bg-slate-50 lg:ml-64">

    <!-- Header -->
    <header class="border-b border-slate-200 bg-white">
        <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">

            <button
                id="mobileMenuButton"
                type="button"
                class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden"
                aria-label="Open navigation"
                aria-expanded="false"
            >
                ☰
            </button>

            <div>
                <h1 class="text-lg font-semibold text-slate-900">
                    Properties
                </h1>

                <p class="hidden text-xs text-slate-500 sm:block">
                    Manage your rental properties
                </p>
            </div>

            <a
                href="#"
                class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
            >
                + Add Property
            </a>

        </div>
    </header>


    <!-- Content -->
    <div class="p-4 sm:p-6 lg:p-8">

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-slate-900">
                All Properties
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                View and manage all properties in your portfolio.
            </p>
        </div>


        <?php if (empty($properties)): ?>

            <div class="rounded-xl border border-slate-200 bg-white p-10 text-center shadow-sm">

                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-xl">
                    🏢
                </div>

                <h3 class="text-lg font-semibold text-slate-900">
                    No properties found
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    There are currently no properties registered in the system.
                </p>

            </div>

        <?php else: ?>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">

                <?php foreach ($properties as $property): ?>

                    <?php
                    if (!is_array($property)) {
                        continue;
                    }

                    $name = property_name($property);
                    $location = property_location($property);
                    $status = property_status($property);
                    $unitsCount = property_units($property);
                    $occupiedCount = property_occupied($property);
                    $vacantCount = property_vacant($property);
                    $id = property_id($property);
                    ?>

                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">

                        <!-- Property Header -->
                        <div class="border-b border-slate-100 p-6">

                            <div class="flex items-start justify-between gap-4">

                                <div>
                                    <h3 class="text-lg font-bold text-slate-900">
                                        <?= e($name) ?>
                                    </h3>

                                    <p class="mt-1 text-sm text-slate-500">
                                        <?= e($location) ?>
                                    </p>
                                </div>

                                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-medium <?= e(status_classes($status)) ?>">
                                    <?= e($status) ?>
                                </span>

                            </div>

                        </div>


                        <!-- Statistics -->
                        <div class="grid grid-cols-3 divide-x divide-slate-100 border-b border-slate-100">

                            <div class="p-5 text-center">

                                <p class="text-2xl font-bold text-slate-900">
                                    <?= e($unitsCount) ?>
                                </p>

                                <p class="mt-1 text-xs font-medium uppercase tracking-wide text-slate-500">
                                    Units
                                </p>

                            </div>


                            <div class="p-5 text-center">

                                <p class="text-2xl font-bold text-slate-900">
                                    <?= e($occupiedCount) ?>
                                </p>

                                <p class="mt-1 text-xs font-medium uppercase tracking-wide text-slate-500">
                                    Occupied
                                </p>

                            </div>


                            <div class="p-5 text-center">

                                <p class="text-2xl font-bold text-slate-900">
                                    <?= e($vacantCount) ?>
                                </p>

                                <p class="mt-1 text-xs font-medium uppercase tracking-wide text-slate-500">
                                    Vacant
                                </p>

                            </div>

                        </div>


                        <!-- Footer -->
                        <div class="p-5">

                            <?php if ($id !== ''): ?>

                                <a
                                    href="property-details.php?id=<?= urlencode($id) ?>"
                                    class="block w-full rounded-lg border border-slate-200 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700"
                                >
                                    View Property
                                </a>

                            <?php else: ?>

                                <span class="block w-full rounded-lg bg-slate-100 px-4 py-2.5 text-center text-sm font-semibold text-slate-400">
                                    View Property
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>

</main>

<?php require_once "../../includes/footer.php"; ?>