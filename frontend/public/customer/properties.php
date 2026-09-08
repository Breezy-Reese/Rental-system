<?php

$pageTitle = "Properties";

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

/*
|--------------------------------------------------------------------------
| Property Data
|--------------------------------------------------------------------------
| Static for now.
| Later this array will be replaced with MongoDB data.
|--------------------------------------------------------------------------
*/

$properties = [

    [
        'id' => 'PROP-001',
        'name' => 'Greenview Apartments',
        'location' => 'Nairobi',
        'address' => 'Kilimani, Nairobi',
        'units' => 48,
        'occupied' => 43,
        'vacant' => 5,
        'type' => 'Apartment',
        'income' => 'KSh 1,075,000',
    ],

    [
        'id' => 'PROP-002',
        'name' => 'Sunrise Estate',
        'location' => 'Mombasa',
        'address' => 'Nyali, Mombasa',
        'units' => 72,
        'occupied' => 68,
        'vacant' => 4,
        'type' => 'Residential Estate',
        'income' => 'KSh 2,380,000',
    ],

    [
        'id' => 'PROP-003',
        'name' => 'Palm Heights',
        'location' => 'Kilifi',
        'address' => 'Kilifi Town, Kilifi',
        'units' => 36,
        'occupied' => 29,
        'vacant' => 7,
        'type' => 'Apartment',
        'income' => 'KSh 725,000',
    ],

];

?>

<main class="flex-1 lg:ml-64">

    <?php require_once "../includes/navbar.php"; ?>

    <div class="p-4 sm:p-6 lg:p-8">

        <!-- Page Header -->
        <div class="mb-6 flex flex-col justify-between gap-4
                    sm:flex-row sm:items-center">

            <div>

                <h1 class="text-2xl font-bold text-slate-900">
                    Properties
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Manage your properties, units and rental information.
                </p>

            </div>


            <button
                type="button"
                class="rounded-lg bg-primary-600
                       px-4 py-2.5 text-sm font-semibold
                       text-white transition
                       hover:bg-primary-700">

                + Add Property

            </button>

        </div>


        <!-- Property Statistics -->
        <div class="mb-6 grid gap-4 sm:grid-cols-3">


            <!-- Total Properties -->
            <div
                class="rounded-xl border border-slate-200
                       bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Total Properties
                </p>

                <p class="mt-2 text-2xl font-bold text-slate-900">
                    <?= count($properties) ?>
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Managed properties
                </p>

            </div>


            <!-- Total Units -->
            <div
                class="rounded-xl border border-slate-200
                       bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Total Units
                </p>

                <p class="mt-2 text-2xl font-bold text-slate-900">

                    <?php
                    $totalUnits = 0;

                    foreach ($properties as $property) {
                        $totalUnits += $property['units'];
                    }

                    echo $totalUnits;
                    ?>

                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Across all properties
                </p>

            </div>


            <!-- Occupied Units -->
            <div
                class="rounded-xl border border-slate-200
                       bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Occupied Units
                </p>

                <p class="mt-2 text-2xl font-bold text-emerald-600">

                    <?php
                    $totalOccupied = 0;

                    foreach ($properties as $property) {
                        $totalOccupied += $property['occupied'];
                    }

                    echo $totalOccupied;
                    ?>

                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Currently occupied
                </p>

            </div>

        </div>


        <!-- Properties Grid -->
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">


            <?php foreach ($properties as $property): ?>

                <?php

                $occupancyRate = $property['units'] > 0
                    ? round(
                        ($property['occupied'] / $property['units']) * 100
                    )
                    : 0;

                ?>

                <!-- Property Card -->
                <div
                    class="overflow-hidden rounded-xl
                           border border-slate-200
                           bg-white shadow-sm
                           transition hover:-translate-y-1
                           hover:shadow-md">


                    <!-- Property Header -->
                    <div
                        class="flex h-40 items-center
                               justify-center bg-gradient-to-br
                               from-primary-600 to-primary-800">

                        <div class="text-center text-white">

                            <div
                                class="mx-auto mb-3 flex h-14 w-14
                                       items-center justify-center
                                       rounded-2xl bg-white/20
                                       text-3xl">

                                🏢

                            </div>

                            <p class="text-sm font-medium">
                                <?= htmlspecialchars($property['type']) ?>
                            </p>

                        </div>

                    </div>


                    <!-- Property Content -->
                    <div class="p-5">


                        <!-- Name -->
                        <div class="mb-4">

                            <a
                                href="property-details.php?id=<?= urlencode($property['id']) ?>"
                                class="text-lg font-bold text-slate-900
                                       hover:text-primary-600">

                                <?= htmlspecialchars($property['name']) ?>

                            </a>

                            <p class="mt-1 text-sm text-slate-500">

                                <?= htmlspecialchars($property['address']) ?>

                            </p>

                        </div>


                        <!-- Occupancy -->
                        <div class="mb-5">

                            <div
                                class="mb-2 flex items-center
                                       justify-between">

                                <span class="text-sm text-slate-500">
                                    Occupancy
                                </span>

                                <span
                                    class="text-sm font-semibold
                                           text-slate-900">

                                    <?= $occupancyRate ?>%

                                </span>

                            </div>


                            <div
                                class="h-2 overflow-hidden rounded-full
                                       bg-slate-100">

                                <div
                                    class="h-full rounded-full
                                           bg-emerald-500"
                                    style="width: <?= $occupancyRate ?>%">
                                </div>

                            </div>

                        </div>


                        <!-- Statistics -->
                        <div class="mb-5 grid grid-cols-3 gap-3">


                            <div
                                class="rounded-lg bg-slate-50 p-3
                                       text-center">

                                <p class="text-xs text-slate-500">
                                    Units
                                </p>

                                <p class="mt-1 font-bold text-slate-900">
                                    <?= $property['units'] ?>
                                </p>

                            </div>


                            <div
                                class="rounded-lg bg-emerald-50 p-3
                                       text-center">

                                <p class="text-xs text-emerald-600">
                                    Occupied
                                </p>

                                <p class="mt-1 font-bold text-emerald-700">
                                    <?= $property['occupied'] ?>
                                </p>

                            </div>


                            <div
                                class="rounded-lg bg-amber-50 p-3
                                       text-center">

                                <p class="text-xs text-amber-600">
                                    Vacant
                                </p>

                                <p class="mt-1 font-bold text-amber-700">
                                    <?= $property['vacant'] ?>
                                </p>

                            </div>

                        </div>


                        <!-- Monthly Income -->
                        <div
                            class="mb-5 rounded-lg border
                                   border-slate-100 bg-slate-50 p-4">

                            <p class="text-xs text-slate-500">
                                Monthly Rental Income
                            </p>

                            <p class="mt-1 text-lg font-bold text-slate-900">
                                <?= htmlspecialchars($property['income']) ?>
                            </p>

                        </div>


                        <!-- Actions -->
                        <div class="flex gap-3">


                            <a
                                href="property-details.php?id=<?= urlencode($property['id']) ?>"
                                class="flex-1 rounded-lg
                                       bg-primary-600 px-4 py-2.5
                                       text-center text-sm
                                       font-semibold text-white
                                       hover:bg-primary-700">

                                View Property

                            </a>


                            <a
                                href="units.php"
                                class="rounded-lg border
                                       border-slate-300
                                       px-4 py-2.5 text-sm
                                       font-semibold text-slate-700
                                       hover:bg-slate-50">

                                Units

                            </a>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>


        <!-- Property Information -->
        <div
            class="mt-8 rounded-xl border border-slate-200
                   bg-white p-6 shadow-sm">

            <div class="flex flex-col gap-4 sm:flex-row
                        sm:items-center sm:justify-between">

                <div>

                    <h2 class="font-semibold text-slate-900">
                        Property Management
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Select a property above to view detailed
                        information, tenants, units and payments.
                    </p>

                </div>


                <a
                    href="units.php"
                    class="inline-flex w-fit items-center
                           gap-2 rounded-lg border
                           border-slate-300 px-4 py-2.5
                           text-sm font-semibold text-slate-700
                           hover:bg-slate-50">

                    Manage Units

                    <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 18l6-6-6-6"/>

                    </svg>

                </a>

            </div>

        </div>

    </div>

</main>


<?php require_once "../includes/footer.php"; ?>