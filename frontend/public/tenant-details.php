<?php

$pageTitle = "Tenant Details";

/*
|--------------------------------------------------------------------------
| Tenant Selection
|--------------------------------------------------------------------------
| The tenants.php page sends:
| tenant-details.php?id=TEN-00124
| or
| tenant-details.php?id=TEN-00125
|
| For now, we use static data.
| Later this will come from MongoDB.
|--------------------------------------------------------------------------
*/

$tenantId = $_GET['id'] ?? 'TEN-00124';

$tenants = [

    'TEN-00124' => [
        'id' => 'TEN-00124',
        'name' => 'John Mwangi',
        'initials' => 'JM',
        'email' => 'john@example.com',
        'phone' => '+254 712 345 678',
        'property' => 'Greenview Apartments',
        'property_id' => 'PROP-001',
        'unit' => 'A-101',
        'type' => '2 Bedroom',
        'rent' => 'KSh 25,000',
        'status' => 'Active',
        'lease_id' => 'LS-00124',
        'lease_start' => 'January 1, 2026',
        'lease_end' => 'December 31, 2026',
        'balance' => 'KSh 0',
        'total_paid' => 'KSh 225,000',
        'joined' => 'January 1, 2026',
    ],

    'TEN-00125' => [
        'id' => 'TEN-00125',
        'name' => 'Mary Wanjiku',
        'initials' => 'MW',
        'email' => 'mary@example.com',
        'phone' => '+254 723 456 789',
        'property' => 'Sunrise Estate',
        'property_id' => 'PROP-002',
        'unit' => 'B-204',
        'type' => '3 Bedroom',
        'rent' => 'KSh 35,000',
        'status' => 'Active',
        'lease_id' => 'LS-00125',
        'lease_start' => 'March 1, 2026',
        'lease_end' => 'February 28, 2027',
        'balance' => 'KSh 0',
        'total_paid' => 'KSh 210,000',
        'joined' => 'March 1, 2026',
    ],

];


/*
|--------------------------------------------------------------------------
| Check Tenant
|--------------------------------------------------------------------------
*/

if (!isset($tenants[$tenantId])) {
    $tenantId = 'TEN-00124';
}

$tenant = $tenants[$tenantId];

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

?>

<main class="flex-1 lg:ml-64">

    <?php require_once "../includes/navbar.php"; ?>

    <div class="p-4 sm:p-6 lg:p-8">

        <!-- Back Navigation -->
        <div class="mb-6">

            <a
                href="tenants.php"
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

                Back to Tenants

            </a>

        </div>


        <!-- Page Header -->
        <div class="mb-6 flex flex-col justify-between gap-4
                    sm:flex-row sm:items-center">

            <div>

                <p class="text-sm text-slate-500">
                    Tenant ID: <?= htmlspecialchars($tenant['id']) ?>
                </p>

                <h1 class="mt-1 text-2xl font-bold text-slate-900">
                    Tenant Details
                </h1>

            </div>


            <div class="flex flex-wrap gap-3">

                <a
                    href="payments.php"
                    class="rounded-lg border border-slate-300
                           bg-white px-4 py-2.5 text-sm
                           font-semibold text-slate-700
                           hover:bg-slate-50">

                    Payment History

                </a>

                <button
                    type="button"
                    class="rounded-lg bg-primary-600
                           px-4 py-2.5 text-sm font-semibold
                           text-white hover:bg-primary-700">

                    Edit Tenant

                </button>

            </div>

        </div>


        <!-- Tenant Profile -->
        <div class="mb-6 rounded-xl border border-slate-200
                    bg-white p-6 shadow-sm">

            <div class="flex flex-col gap-5 sm:flex-row
                        sm:items-center sm:justify-between">

                <div class="flex items-center gap-4">

                    <div
                        class="flex h-16 w-16 shrink-0
                               items-center justify-center
                               rounded-full bg-primary-100
                               text-xl font-bold text-primary-700">

                        <?= htmlspecialchars($tenant['initials']) ?>

                    </div>

                    <div>

                        <h2 class="text-xl font-bold text-slate-900">
                            <?= htmlspecialchars($tenant['name']) ?>
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            <?= htmlspecialchars($tenant['email']) ?>
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            <?= htmlspecialchars($tenant['phone']) ?>
                        </p>

                    </div>

                </div>


                <span
                    class="inline-flex w-fit rounded-full
                           bg-emerald-50 px-3 py-1.5
                           text-xs font-semibold
                           text-emerald-700">

                    <?= htmlspecialchars($tenant['status']) ?>

                </span>

            </div>

        </div>


        <!-- Information Cards -->
        <div class="mb-6 grid gap-6 lg:grid-cols-2">


            <!-- Personal Information -->
            <div class="rounded-xl border border-slate-200
                        bg-white shadow-sm">

                <div class="border-b border-slate-200 p-5">

                    <h2 class="font-semibold text-slate-900">
                        Personal Information
                    </h2>

                </div>

                <div class="p-5">

                    <div class="grid gap-5 sm:grid-cols-2">

                        <div>

                            <p class="text-xs font-medium uppercase
                                      tracking-wide text-slate-400">

                                Full Name

                            </p>

                            <p class="mt-1 font-medium text-slate-900">
                                <?= htmlspecialchars($tenant['name']) ?>
                            </p>

                        </div>


                        <div>

                            <p class="text-xs font-medium uppercase
                                      tracking-wide text-slate-400">

                                Email

                            </p>

                            <p class="mt-1 text-slate-700">
                                <?= htmlspecialchars($tenant['email']) ?>
                            </p>

                        </div>


                        <div>

                            <p class="text-xs font-medium uppercase
                                      tracking-wide text-slate-400">

                                Phone

                            </p>

                            <p class="mt-1 text-slate-700">
                                <?= htmlspecialchars($tenant['phone']) ?>
                            </p>

                        </div>


                        <div>

                            <p class="text-xs font-medium uppercase
                                      tracking-wide text-slate-400">

                                Tenant Since

                            </p>

                            <p class="mt-1 text-slate-700">
                                <?= htmlspecialchars($tenant['joined']) ?>
                            </p>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Current Lease -->
            <div class="rounded-xl border border-slate-200
                        bg-white shadow-sm">

                <div class="flex items-center justify-between
                            border-b border-slate-200 p-5">

                    <h2 class="font-semibold text-slate-900">
                        Current Lease
                    </h2>

                    <span
                        class="rounded-full bg-emerald-50
                               px-3 py-1 text-xs font-medium
                               text-emerald-700">

                        Active

                    </span>

                </div>


                <div class="p-5">

                    <div class="grid gap-5 sm:grid-cols-2">

                        <div>

                            <p class="text-xs font-medium uppercase
                                      tracking-wide text-slate-400">

                                Property

                            </p>

                            <a
                                href="property-details.php?id=<?= urlencode($tenant['property_id']) ?>"
                                class="mt-1 block font-medium
                                       text-primary-600
                                       hover:text-primary-800">

                                <?= htmlspecialchars($tenant['property']) ?>

                            </a>

                        </div>


                        <div>

                            <p class="text-xs font-medium uppercase
                                      tracking-wide text-slate-400">

                                Unit

                            </p>

                            <p class="mt-1 font-medium text-slate-900">
                                <?= htmlspecialchars($tenant['unit']) ?>
                            </p>

                        </div>


                        <div>

                            <p class="text-xs font-medium uppercase
                                      tracking-wide text-slate-400">

                                Unit Type

                            </p>

                            <p class="mt-1 text-slate-700">
                                <?= htmlspecialchars($tenant['type']) ?>
                            </p>

                        </div>


                        <div>

                            <p class="text-xs font-medium uppercase
                                      tracking-wide text-slate-400">

                                Monthly Rent

                            </p>

                            <p class="mt-1 font-semibold text-slate-900">
                                <?= htmlspecialchars($tenant['rent']) ?>
                            </p>

                        </div>


                        <div>

                            <p class="text-xs font-medium uppercase
                                      tracking-wide text-slate-400">

                                Lease Start

                            </p>

                            <p class="mt-1 text-slate-700">
                                <?= htmlspecialchars($tenant['lease_start']) ?>
                            </p>

                        </div>


                        <div>

                            <p class="text-xs font-medium uppercase
                                      tracking-wide text-slate-400">

                                Lease End

                            </p>

                            <p class="mt-1 text-slate-700">
                                <?= htmlspecialchars($tenant['lease_end']) ?>
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- Financial Summary -->
        <div class="mb-6 grid gap-4 sm:grid-cols-3">


            <div class="rounded-xl border border-slate-200
                        bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Monthly Rent
                </p>

                <p class="mt-2 text-2xl font-bold text-slate-900">
                    <?= htmlspecialchars($tenant['rent']) ?>
                </p>

            </div>


            <div class="rounded-xl border border-slate-200
                        bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Outstanding Balance
                </p>

                <p class="mt-2 text-2xl font-bold text-emerald-600">
                    <?= htmlspecialchars($tenant['balance']) ?>
                </p>

            </div>


            <div class="rounded-xl border border-slate-200
                        bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Total Paid
                </p>

                <p class="mt-2 text-2xl font-bold text-primary-600">
                    <?= htmlspecialchars($tenant['total_paid']) ?>
                </p>

            </div>

        </div>


        <!-- Recent Payments -->
        <div class="mb-6 overflow-hidden rounded-xl
                    border border-slate-200 bg-white shadow-sm">

            <div class="flex items-center justify-between
                        border-b border-slate-200 p-5">

                <div>

                    <h2 class="font-semibold text-slate-900">
                        Recent Payments
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        Latest rent payments made by this tenant.
                    </p>

                </div>

                <a
                    href="payments.php"
                    class="text-sm font-medium text-primary-600
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
                                Date
                            </th>

                            <th class="px-6 py-4">
                                Amount
                            </th>

                            <th class="px-6 py-4">
                                Method
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

                            <td class="px-6 py-4 text-slate-600">
                                September 7, 2026
                            </td>

                            <td class="px-6 py-4 font-medium">
                                <?= htmlspecialchars($tenant['rent']) ?>
                            </td>

                            <td class="px-6 py-4">
                                M-Pesa
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
                                #PAY-10020
                            </td>

                            <td class="px-6 py-4 text-slate-600">
                                August 5, 2026
                            </td>

                            <td class="px-6 py-4 font-medium">
                                <?= htmlspecialchars($tenant['rent']) ?>
                            </td>

                            <td class="px-6 py-4">
                                M-Pesa
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
                                #PAY-10001
                            </td>

                            <td class="px-6 py-4 text-slate-600">
                                July 5, 2026
                            </td>

                            <td class="px-6 py-4 font-medium">
                                <?= htmlspecialchars($tenant['rent']) ?>
                            </td>

                            <td class="px-6 py-4">
                                Bank Transfer
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


        <!-- Maintenance Requests -->
        <div class="overflow-hidden rounded-xl
                    border border-slate-200 bg-white shadow-sm">

            <div class="flex items-center justify-between
                        border-b border-slate-200 p-5">

                <div>

                    <h2 class="font-semibold text-slate-900">
                        Maintenance Requests
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        Maintenance requests associated with this tenant.
                    </p>

                </div>

                <a
                    href="maintenance.php"
                    class="text-sm font-medium text-primary-600
                           hover:text-primary-800">

                    View All

                </a>

            </div>


            <div class="divide-y divide-slate-100">


                <div class="flex flex-col gap-3 p-5 sm:flex-row
                            sm:items-center sm:justify-between">

                    <div>

                        <p class="font-medium text-slate-900">
                            Broken water pipe
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Unit <?= htmlspecialchars($tenant['unit']) ?>
                            · September 7, 2026
                        </p>

                    </div>

                    <span
                        class="w-fit rounded-full bg-red-50
                               px-3 py-1 text-xs font-medium
                               text-red-700">

                        Urgent

                    </span>

                </div>


                <div class="flex flex-col gap-3 p-5 sm:flex-row
                            sm:items-center sm:justify-between">

                    <div>

                        <p class="font-medium text-slate-900">
                            Routine inspection
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Unit <?= htmlspecialchars($tenant['unit']) ?>
                            · August 20, 2026
                        </p>

                    </div>

                    <span
                        class="w-fit rounded-full bg-emerald-50
                               px-3 py-1 text-xs font-medium
                               text-emerald-700">

                        Completed

                    </span>

                </div>

            </div>

        </div>

    </div>

</main>


<?php require_once "../includes/footer.php"; ?>