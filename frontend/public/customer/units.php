<?php

$pageTitle = "Units";

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

/*
|--------------------------------------------------------------------------
| Unit Data
|--------------------------------------------------------------------------
| Static for now.
| Later this will be replaced with MongoDB data.
|--------------------------------------------------------------------------
*/

$units = [

    [
        'id' => 'UNIT-00101',
        'unit_number' => 'A-101',
        'property_id' => 'PROP-001',
        'property' => 'Greenview Apartments',
        'property_location' => 'Nairobi',
        'type' => '2 Bedroom',
        'rent' => 25000,
        'tenant_id' => 'TEN-00124',
        'tenant' => 'John Mwangi',
        'status' => 'Occupied',
        'status_class' => 'bg-emerald-100 text-emerald-700',
    ],

    [
        'id' => 'UNIT-00102',
        'unit_number' => 'A-102',
        'property_id' => 'PROP-001',
        'property' => 'Greenview Apartments',
        'property_location' => 'Nairobi',
        'type' => '1 Bedroom',
        'rent' => 18000,
        'tenant_id' => null,
        'tenant' => null,
        'status' => 'Vacant',
        'status_class' => 'bg-slate-100 text-slate-600',
    ],

    [
        'id' => 'UNIT-00204',
        'unit_number' => 'B-204',
        'property_id' => 'PROP-002',
        'property' => 'Sunrise Estate',
        'property_location' => 'Mombasa',
        'type' => '3 Bedroom',
        'rent' => 35000,
        'tenant_id' => 'TEN-00125',
        'tenant' => 'Mary Wanjiku',
        'status' => 'Occupied',
        'status_class' => 'bg-emerald-100 text-emerald-700',
    ],

    [
        'id' => 'UNIT-00302',
        'unit_number' => 'C-302',
        'property_id' => 'PROP-003',
        'property' => 'Palm Heights',
        'property_location' => 'Kilifi',
        'type' => '2 Bedroom',
        'rent' => 30000,
        'tenant_id' => 'TEN-00126',
        'tenant' => 'Peter Kamau',
        'status' => 'Occupied',
        'status_class' => 'bg-emerald-100 text-emerald-700',
    ],

    [
        'id' => 'UNIT-00312',
        'unit_number' => 'C-312',
        'property_id' => 'PROP-003',
        'property' => 'Palm Heights',
        'property_location' => 'Kilifi',
        'type' => '1 Bedroom',
        'rent' => 20000,
        'tenant_id' => null,
        'tenant' => null,
        'status' => 'Maintenance',
        'status_class' => 'bg-amber-100 text-amber-700',
    ],

];

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

$totalUnits = count($units);

$occupiedUnits = count(
    array_filter(
        $units,
        fn($unit) => $unit['status'] === 'Occupied'
    )
);

$vacantUnits = count(
    array_filter(
        $units,
        fn($unit) => $unit['status'] === 'Vacant'
    )
);

$maintenanceUnits = count(
    array_filter(
        $units,
        fn($unit) => $unit['status'] === 'Maintenance'
    )
);

?>

<main class="flex-1 lg:ml-64">

    <?php require_once "../includes/navbar.php"; ?>

    <div class="p-4 sm:p-6 lg:p-8">

        <!-- Page Header -->
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Units
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Manage rental units across all properties.
                </p>
            </div>

            <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-lg
                       bg-primary-600 px-4 py-2.5 text-sm font-semibold
                       text-white shadow-sm transition
                       hover:bg-primary-700">

                <span class="text-lg">+</span>
                Add Unit

            </button>

        </div>

        <!-- Statistics -->
        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            <!-- Total -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm text-slate-500">
                            Total Units
                        </p>

                        <p class="mt-2 text-2xl font-bold text-slate-900">
                            <?= $totalUnits ?>
                        </p>
                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-50 text-2xl">
                        🚪
                    </div>

                </div>

            </div>

            <!-- Occupied -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm text-slate-500">
                            Occupied
                        </p>

                        <p class="mt-2 text-2xl font-bold text-emerald-600">
                            <?= $occupiedUnits ?>
                        </p>
                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-2xl">
                        ✓
                    </div>

                </div>

            </div>

            <!-- Vacant -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm text-slate-500">
                            Vacant
                        </p>

                        <p class="mt-2 text-2xl font-bold text-slate-600">
                            <?= $vacantUnits ?>
                        </p>
                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-2xl">
                        ○
                    </div>

                </div>

            </div>

            <!-- Maintenance -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm text-slate-500">
                            Maintenance
                        </p>

                        <p class="mt-2 text-2xl font-bold text-amber-600">
                            <?= $maintenanceUnits ?>
                        </p>
                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-2xl">
                        🔧
                    </div>

                </div>

            </div>

        </div>

        <!-- Filters -->
        <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

                <!-- Search -->
                <div class="relative">

                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                        🔍
                    </span>

                    <input
                        type="text"
                        placeholder="Search unit or tenant..."
                        class="w-full rounded-lg border border-slate-200
                               bg-slate-50 py-2.5 pl-10 pr-4 text-sm
                               outline-none transition
                               focus:border-primary-500
                               focus:ring-2 focus:ring-primary-100">

                </div>

                <!-- Property -->
                <select
                    class="rounded-lg border border-slate-200
                           bg-slate-50 px-4 py-2.5 text-sm
                           text-slate-700 outline-none
                           focus:border-primary-500
                           focus:ring-2 focus:ring-primary-100">

                    <option value="">
                        All Properties
                    </option>

                    <option value="PROP-001">
                        Greenview Apartments
                    </option>

                    <option value="PROP-002">
                        Sunrise Estate
                    </option>

                    <option value="PROP-003">
                        Palm Heights
                    </option>

                </select>

                <!-- Status -->
                <select
                    class="rounded-lg border border-slate-200
                           bg-slate-50 px-4 py-2.5 text-sm
                           text-slate-700 outline-none
                           focus:border-primary-500
                           focus:ring-2 focus:ring-primary-100">

                    <option value="">
                        All Statuses
                    </option>

                    <option value="Occupied">
                        Occupied
                    </option>

                    <option value="Vacant">
                        Vacant
                    </option>

                    <option value="Maintenance">
                        Maintenance
                    </option>

                </select>

            </div>

        </div>

        <!-- Units Table -->
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-50">

                        <tr>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Unit
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Property
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Type
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Rent
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Tenant
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Status
                            </th>

                            <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Action
                            </th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        <?php foreach ($units as $unit): ?>

                            <tr class="transition hover:bg-slate-50">

                                <!-- Unit -->
                                <td class="whitespace-nowrap px-6 py-4">

                                    <a
                                        href="unit-details.php?id=<?= urlencode($unit['id']) ?>"
                                        class="font-semibold text-primary-600 hover:text-primary-800 hover:underline">

                                        <?= htmlspecialchars($unit['unit_number']) ?>

                                    </a>

                                </td>

                                <!-- Property -->
                                <td class="px-6 py-4">

                                    <a
                                        href="property-details.php?id=<?= urlencode($unit['property_id']) ?>"
                                        class="font-medium text-slate-800 hover:text-primary-600">

                                        <?= htmlspecialchars($unit['property']) ?>

                                    </a>

                                    <p class="mt-1 text-xs text-slate-400">
                                        <?= htmlspecialchars($unit['property_location']) ?>
                                    </p>

                                </td>

                                <!-- Type -->
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">

                                    <?= htmlspecialchars($unit['type']) ?>

                                </td>

                                <!-- Rent -->
                                <td class="whitespace-nowrap px-6 py-4">

                                    <span class="font-semibold text-slate-800">
                                        KSh <?= number_format($unit['rent']) ?>
                                    </span>

                                    <span class="text-xs text-slate-400">
                                        /month
                                    </span>

                                </td>

                                <!-- Tenant -->
                                <td class="px-6 py-4">

                                    <?php if ($unit['tenant_id']): ?>

                                        <a
                                            href="tenant-details.php?id=<?= urlencode($unit['tenant_id']) ?>"
                                            class="font-medium text-slate-800 hover:text-primary-600">

                                            <?= htmlspecialchars($unit['tenant']) ?>

                                        </a>

                                    <?php else: ?>

                                        <span class="text-sm text-slate-400">
                                            No tenant
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <!-- Status -->
                                <td class="whitespace-nowrap px-6 py-4">

                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold <?= $unit['status_class'] ?>">

                                        <?= htmlspecialchars($unit['status']) ?>

                                    </span>

                                </td>

                                <!-- Action -->
                                <td class="whitespace-nowrap px-6 py-4 text-right">

                                    <a
                                        href="unit-details.php?id=<?= urlencode($unit['id']) ?>"
                                        class="text-sm font-semibold text-primary-600 hover:text-primary-800">

                                        View

                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <!-- Footer -->
            <div class="flex flex-col gap-3 border-t border-slate-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">

                <p class="text-sm text-slate-500">
                    Showing
                    <span class="font-semibold text-slate-700">
                        <?= $totalUnits ?>
                    </span>
                    units
                </p>

                <div class="flex items-center gap-2">

                    <button
                        type="button"
                        disabled
                        class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-400">

                        Previous

                    </button>

                    <span class="rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white">
                        1
                    </span>

                    <button
                        type="button"
                        disabled
                        class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-400">

                        Next

                    </button>

                </div>

            </div>

        </div>

    </div>

</main>

<?php require_once "../includes/footer.php"; ?>