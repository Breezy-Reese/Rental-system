<?php

$pageTitle = "Property Details";

/*
|--------------------------------------------------------------------------
| Property Selection
|--------------------------------------------------------------------------
| properties.php sends:
|
| property-details.php?id=PROP-001
| property-details.php?id=PROP-002
| property-details.php?id=PROP-003
|
| For now, the data is static.
| Later we will replace this with MongoDB data.
|--------------------------------------------------------------------------
*/

$propertyId = $_GET['id'] ?? 'PROP-001';


/*
|--------------------------------------------------------------------------
| Property Data
|--------------------------------------------------------------------------
*/

$properties = [

    'PROP-001' => [
        'id' => 'PROP-001',
        'name' => 'Greenview Apartments',
        'location' => 'Nairobi',
        'address' => 'Kilimani, Nairobi',
        'type' => 'Apartment',
        'units' => 48,
        'occupied' => 43,
        'vacant' => 5,
        'maintenance' => 2,
        'monthly_income' => 'KSh 1,075,000',
        'status' => 'Active',
        'manager' => 'Property Manager',
        'phone' => '+254 700 000 001',
        'created' => 'January 1, 2026',
    ],

    'PROP-002' => [
        'id' => 'PROP-002',
        'name' => 'Sunrise Estate',
        'location' => 'Mombasa',
        'address' => 'Nyali, Mombasa',
        'type' => 'Residential Estate',
        'units' => 72,
        'occupied' => 68,
        'vacant' => 4,
        'maintenance' => 3,
        'monthly_income' => 'KSh 2,380,000',
        'status' => 'Active',
        'manager' => 'Property Manager',
        'phone' => '+254 700 000 002',
        'created' => 'February 1, 2026',
    ],

    'PROP-003' => [
        'id' => 'PROP-003',
        'name' => 'Palm Heights',
        'location' => 'Kilifi',
        'address' => 'Kilifi Town, Kilifi',
        'type' => 'Apartment',
        'units' => 36,
        'occupied' => 29,
        'vacant' => 7,
        'maintenance' => 2,
        'monthly_income' => 'KSh 725,000',
        'status' => 'Active',
        'manager' => 'Property Manager',
        'phone' => '+254 700 000 003',
        'created' => 'March 1, 2026',
    ],

];


/*
|--------------------------------------------------------------------------
| Validate Property
|--------------------------------------------------------------------------
*/

if (!isset($properties[$propertyId])) {
    $propertyId = 'PROP-001';
}

$property = $properties[$propertyId];


/*
|--------------------------------------------------------------------------
| Calculations
|--------------------------------------------------------------------------
*/

$occupancyRate = $property['units'] > 0
    ? round(($property['occupied'] / $property['units']) * 100)
    : 0;

$vacancyRate = $property['units'] > 0
    ? round(($property['vacant'] / $property['units']) * 100)
    : 0;


require_once "../includes/header.php";
require_once "../includes/sidebar.php";

?>

<main class="flex-1 lg:ml-64">

    <?php require_once "../includes/navbar.php"; ?>

    <div class="p-4 sm:p-6 lg:p-8">

        <!-- Back -->
        <div class="mb-6">

            <a
                href="properties.php"
                class="inline-flex items-center gap-2 text-sm
                       font-medium text-slate-500
                       hover:text-primary-600">

                <svg
                    class="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24">

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 19l-7-7 7-7"/>

                </svg>

                Back to Properties

            </a>

        </div>


        <!-- Page Header -->
        <div class="mb-6 flex flex-col justify-between gap-4
                    sm:flex-row sm:items-center">

            <div>

                <p class="text-sm text-slate-500">
                    Property ID:
                    <?= htmlspecialchars($property['id']) ?>
                </p>

                <h1 class="mt-1 text-2xl font-bold text-slate-900">
                    <?= htmlspecialchars($property['name']) ?>
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    <?= htmlspecialchars($property['address']) ?>
                </p>

            </div>


            <div class="flex flex-wrap gap-3">

                <a
                    href="units.php"
                    class="rounded-lg border border-slate-300
                           bg-white px-4 py-2.5 text-sm
                           font-semibold text-slate-700
                           hover:bg-slate-50">

                    View Units

                </a>

                <button
                    type="button"
                    class="rounded-lg bg-primary-600
                           px-4 py-2.5 text-sm font-semibold
                           text-white hover:bg-primary-700">

                    Edit Property

                </button>

            </div>

        </div>


        <!-- Property Overview -->
        <div class="mb-6 rounded-xl border border-slate-200
                    bg-white p-6 shadow-sm">

            <div class="flex flex-col gap-6 lg:flex-row
                        lg:items-center lg:justify-between">

                <div class="flex items-center gap-4">

                    <div
                        class="flex h-16 w-16 shrink-0
                               items-center justify-center
                               rounded-2xl bg-primary-100
                               text-2xl">

                        🏢

                    </div>

                    <div>

                        <h2 class="text-xl font-bold text-slate-900">
                            <?= htmlspecialchars($property['name']) ?>
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            <?= htmlspecialchars($property['location']) ?>
                            ·
                            <?= htmlspecialchars($property['type']) ?>
                        </p>

                    </div>

                </div>


                <span
                    class="inline-flex w-fit rounded-full
                           bg-emerald-50 px-3 py-1.5
                           text-xs font-semibold
                           text-emerald-700">

                    <?= htmlspecialchars($property['status']) ?>

                </span>

            </div>

        </div>


        <!-- Statistics -->
        <div class="mb-6 grid gap-4 sm:grid-cols-2
                    lg:grid-cols-4">


            <!-- Total Units -->
            <div
                class="rounded-xl border border-slate-200
                       bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Total Units
                </p>

                <p class="mt-2 text-2xl font-bold text-slate-900">
                    <?= htmlspecialchars($property['units']) ?>
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Units in property
                </p>

            </div>


            <!-- Occupied -->
            <div
                class="rounded-xl border border-slate-200
                       bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Occupied
                </p>

                <p class="mt-2 text-2xl font-bold text-emerald-600">
                    <?= htmlspecialchars($property['occupied']) ?>
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    <?= $occupancyRate ?>% occupancy
                </p>

            </div>


            <!-- Vacant -->
            <div
                class="rounded-xl border border-slate-200
                       bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Vacant
                </p>

                <p class="mt-2 text-2xl font-bold text-amber-600">
                    <?= htmlspecialchars($property['vacant']) ?>
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    <?= $vacancyRate ?>% vacancy
                </p>

            </div>


            <!-- Maintenance -->
            <div
                class="rounded-xl border border-slate-200
                       bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Maintenance
                </p>

                <p class="mt-2 text-2xl font-bold text-red-600">
                    <?= htmlspecialchars($property['maintenance']) ?>
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Open requests
                </p>

            </div>

        </div>


        <!-- Main Content -->
        <div class="grid gap-6 lg:grid-cols-3">


            <!-- Units -->
            <div
                class="overflow-hidden rounded-xl
                       border border-slate-200
                       bg-white shadow-sm lg:col-span-2">

                <div
                    class="flex items-center justify-between
                           border-b border-slate-200 p-5">

                    <div>

                        <h2 class="font-semibold text-slate-900">
                            Units
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Units belonging to this property.
                        </p>

                    </div>

                    <a
                        href="units.php"
                        class="text-sm font-medium
                               text-primary-600
                               hover:text-primary-800">

                        View All

                    </a>

                </div>


                <div class="overflow-x-auto">

                    <table class="w-full text-left text-sm">

                        <thead
                            class="bg-slate-50 text-xs uppercase
                                   text-slate-500">

                            <tr>

                                <th class="px-6 py-4">
                                    Unit
                                </th>

                                <th class="px-6 py-4">
                                    Type
                                </th>

                                <th class="px-6 py-4">
                                    Rent
                                </th>

                                <th class="px-6 py-4">
                                    Tenant
                                </th>

                                <th class="px-6 py-4">
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100">


                            <!-- A-101 -->
                            <tr class="hover:bg-slate-50">

                                <td class="px-6 py-4 font-semibold">
                                    A-101
                                </td>

                                <td class="px-6 py-4 text-slate-600">
                                    2 Bedroom
                                </td>

                                <td class="px-6 py-4 font-medium">
                                    KSh 25,000
                                </td>

                                <td class="px-6 py-4">

                                    <a
                                        href="tenant-details.php?id=TEN-00124"
                                        class="text-primary-600
                                               hover:text-primary-800">

                                        John Mwangi

                                    </a>

                                </td>

                                <td class="px-6 py-4">

                                    <span
                                        class="rounded-full
                                               bg-emerald-50 px-3 py-1
                                               text-xs font-medium
                                               text-emerald-700">

                                        Occupied

                                    </span>

                                </td>

                            </tr>


                            <!-- A-102 -->
                            <tr class="hover:bg-slate-50">

                                <td class="px-6 py-4 font-semibold">
                                    A-102
                                </td>

                                <td class="px-6 py-4 text-slate-600">
                                    1 Bedroom
                                </td>

                                <td class="px-6 py-4 font-medium">
                                    KSh 18,000
                                </td>

                                <td class="px-6 py-4 text-slate-400">
                                    —
                                </td>

                                <td class="px-6 py-4">

                                    <span
                                        class="rounded-full
                                               bg-amber-50 px-3 py-1
                                               text-xs font-medium
                                               text-amber-700">

                                        Vacant

                                    </span>

                                </td>

                            </tr>


                            <!-- A-103 -->
                            <tr class="hover:bg-slate-50">

                                <td class="px-6 py-4 font-semibold">
                                    A-103
                                </td>

                                <td class="px-6 py-4 text-slate-600">
                                    2 Bedroom
                                </td>

                                <td class="px-6 py-4 font-medium">
                                    KSh 25,000
                                </td>

                                <td class="px-6 py-4 text-slate-700">
                                    Peter Kamau
                                </td>

                                <td class="px-6 py-4">

                                    <span
                                        class="rounded-full
                                               bg-emerald-50 px-3 py-1
                                               text-xs font-medium
                                               text-emerald-700">

                                        Occupied

                                    </span>

                                </td>

                            </tr>


                            <!-- A-104 -->
                            <tr class="hover:bg-slate-50">

                                <td class="px-6 py-4 font-semibold">
                                    A-104
                                </td>

                                <td class="px-6 py-4 text-slate-600">
                                    3 Bedroom
                                </td>

                                <td class="px-6 py-4 font-medium">
                                    KSh 32,000
                                </td>

                                <td class="px-6 py-4 text-slate-700">
                                    Jane Njeri
                                </td>

                                <td class="px-6 py-4">

                                    <span
                                        class="rounded-full
                                               bg-emerald-50 px-3 py-1
                                               text-xs font-medium
                                               text-emerald-700">

                                        Occupied

                                    </span>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>


            <!-- Property Summary -->
            <div
                class="rounded-xl border border-slate-200
                       bg-white shadow-sm">

                <div class="border-b border-slate-200 p-5">

                    <h2 class="font-semibold text-slate-900">
                        Property Summary
                    </h2>

                </div>


                <div class="space-y-5 p-5">


                    <div>

                        <p class="text-xs font-medium uppercase
                                  tracking-wide text-slate-400">

                            Property Name

                        </p>

                        <p class="mt-1 font-medium text-slate-900">

                            <?= htmlspecialchars($property['name']) ?>

                        </p>

                    </div>


                    <div>

                        <p class="text-xs font-medium uppercase
                                  tracking-wide text-slate-400">

                            Location

                        </p>

                        <p class="mt-1 text-slate-700">

                            <?= htmlspecialchars($property['address']) ?>

                        </p>

                    </div>


                    <div>

                        <p class="text-xs font-medium uppercase
                                  tracking-wide text-slate-400">

                            Property Type

                        </p>

                        <p class="mt-1 text-slate-700">

                            <?= htmlspecialchars($property['type']) ?>

                        </p>

                    </div>


                    <div>

                        <p class="text-xs font-medium uppercase
                                  tracking-wide text-slate-400">

                            Monthly Income

                        </p>

                        <p class="mt-1 text-lg font-bold
                                  text-slate-900">

                            <?= htmlspecialchars($property['monthly_income']) ?>

                        </p>

                    </div>


                    <div>

                        <p class="text-xs font-medium uppercase
                                  tracking-wide text-slate-400">

                            Property Manager

                        </p>

                        <p class="mt-1 text-slate-700">

                            <?= htmlspecialchars($property['manager']) ?>

                        </p>

                        <p class="mt-1 text-xs text-slate-500">

                            <?= htmlspecialchars($property['phone']) ?>

                        </p>

                    </div>


                    <div>

                        <p class="text-xs font-medium uppercase
                                  tracking-wide text-slate-400">

                            Added On

                        </p>

                        <p class="mt-1 text-slate-700">

                            <?= htmlspecialchars($property['created']) ?>

                        </p>

                    </div>

                </div>

            </div>

        </div>


        <!-- Recent Payments -->
        <div
            class="mt-6 overflow-hidden rounded-xl
                   border border-slate-200 bg-white shadow-sm">

            <div
                class="flex items-center justify-between
                       border-b border-slate-200 p-5">

                <div>

                    <h2 class="font-semibold text-slate-900">
                        Recent Payments
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        Recent rent payments associated with this property.
                    </p>

                </div>

                <a
                    href="payments.php"
                    class="text-sm font-medium
                           text-primary-600
                           hover:text-primary-800">

                    View All

                </a>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full text-left text-sm">

                    <thead
                        class="bg-slate-50 text-xs uppercase
                               text-slate-500">

                        <tr>

                            <th class="px-6 py-4">
                                Payment ID
                            </th>

                            <th class="px-6 py-4">
                                Tenant
                            </th>

                            <th class="px-6 py-4">
                                Unit
                            </th>

                            <th class="px-6 py-4">
                                Amount
                            </th>

                            <th class="px-6 py-4">
                                Date
                            </th>

                            <th class="px-6 py-4">
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">


                        <tr class="hover:bg-slate-50">

                            <td class="px-6 py-4 font-medium">
                                #PAY-10045
                            </td>

                            <td class="px-6 py-4">

                                <a
                                    href="tenant-details.php?id=TEN-00124"
                                    class="text-primary-600
                                           hover:text-primary-800">

                                    John Mwangi

                                </a>

                            </td>

                            <td class="px-6 py-4">
                                A-101
                            </td>

                            <td class="px-6 py-4 font-medium">
                                KSh 25,000
                            </td>

                            <td class="px-6 py-4 text-slate-600">
                                Sep 7, 2026
                            </td>

                            <td class="px-6 py-4">

                                <span
                                    class="rounded-full bg-emerald-50
                                           px-3 py-1 text-xs
                                           font-medium text-emerald-700">

                                    Paid

                                </span>

                            </td>

                        </tr>


                        <tr class="hover:bg-slate-50">

                            <td class="px-6 py-4 font-medium">
                                #PAY-10044
                            </td>

                            <td class="px-6 py-4">
                                Mary Wanjiku
                            </td>

                            <td class="px-6 py-4">
                                B-204
                            </td>

                            <td class="px-6 py-4 font-medium">
                                KSh 35,000
                            </td>

                            <td class="px-6 py-4 text-slate-600">
                                Sep 6, 2026
                            </td>

                            <td class="px-6 py-4">

                                <span
                                    class="rounded-full bg-emerald-50
                                           px-3 py-1 text-xs
                                           font-medium text-emerald-700">

                                    Paid

                                </span>

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</main>


<?php require_once "../includes/footer.php"; ?>