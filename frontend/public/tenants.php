<?php

$pageTitle = "Tenants";

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

?>

<main class="flex-1 lg:ml-64">

    <?php require_once "../includes/navbar.php"; ?>

    <div class="p-4 sm:p-6 lg:p-8">

        <!-- Page Header -->
        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">

            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Tenants
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Manage your tenants and rental information.
                </p>
            </div>

            <button
                type="button"
                class="rounded-lg bg-primary-600 px-4 py-2.5
                       text-sm font-semibold text-white
                       transition hover:bg-primary-700">
                + Add Tenant
            </button>

        </div>


        <!-- Tenant Statistics -->
        <div class="mb-6 grid gap-4 sm:grid-cols-3">

            <!-- Total -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Total Tenants
                </p>

                <p class="mt-2 text-2xl font-bold text-slate-900">
                    186
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Registered tenants
                </p>

            </div>


            <!-- Active -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Active Tenants
                </p>

                <p class="mt-2 text-2xl font-bold text-emerald-600">
                    174
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Currently renting
                </p>

            </div>


            <!-- Overdue -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Overdue
                </p>

                <p class="mt-2 text-2xl font-bold text-red-600">
                    18
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Require attention
                </p>

            </div>

        </div>


        <!-- Tenants Table -->
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <!-- Search -->
            <div class="border-b border-slate-200 p-5">

                <div class="relative max-w-md">

                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24">

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>

                        </svg>
                    </span>

                    <input
                        type="text"
                        placeholder="Search tenants..."
                        class="w-full rounded-lg border border-slate-300
                               py-2.5 pl-10 pr-4 text-sm
                               outline-none transition
                               focus:border-primary-500
                               focus:ring-2 focus:ring-primary-100">

                </div>

            </div>


            <!-- Responsive Table -->
            <div class="overflow-x-auto">

                <table class="w-full min-w-[900px] text-left text-sm">

                    <!-- Table Header -->
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">

                        <tr>

                            <th class="px-6 py-4 font-semibold">
                                Tenant
                            </th>

                            <th class="px-6 py-4 font-semibold">
                                Phone
                            </th>

                            <th class="px-6 py-4 font-semibold">
                                Property
                            </th>

                            <th class="px-6 py-4 font-semibold">
                                Unit
                            </th>

                            <th class="px-6 py-4 font-semibold">
                                Rent
                            </th>

                            <th class="px-6 py-4 font-semibold">
                                Status
                            </th>

                            <th class="px-6 py-4 text-right font-semibold">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <!-- Table Body -->
                    <tbody class="divide-y divide-slate-100">


                        <!-- John Mwangi -->
                        <tr class="transition hover:bg-slate-50">

                            <!-- Tenant -->
                            <td class="px-6 py-4">

                                <a
                                    href="tenant-details.php?id=TEN-00124"
                                    class="group flex items-center gap-3">

                                    <div
                                        class="flex h-10 w-10 shrink-0
                                               items-center justify-center
                                               rounded-full bg-primary-100
                                               font-semibold text-primary-700">

                                        JM

                                    </div>

                                    <div>

                                        <p
                                            class="font-medium text-slate-900
                                                   group-hover:text-primary-600">

                                            John Mwangi

                                        </p>

                                        <p class="text-xs text-slate-500">
                                            john@example.com
                                        </p>

                                    </div>

                                </a>

                            </td>


                            <!-- Phone -->
                            <td class="px-6 py-4 text-slate-600">

                                +254 712 345 678

                            </td>


                            <!-- Property -->
                            <td class="px-6 py-4">

                                <a
                                    href="property-details.php?id=PROP-001"
                                    class="text-slate-700 hover:text-primary-600">

                                    Greenview Apartments

                                </a>

                            </td>


                            <!-- Unit -->
                            <td class="px-6 py-4 font-medium text-slate-700">

                                A-101

                            </td>


                            <!-- Rent -->
                            <td class="px-6 py-4 font-medium text-slate-900">

                                KSh 25,000

                            </td>


                            <!-- Status -->
                            <td class="px-6 py-4">

                                <span
                                    class="inline-flex rounded-full
                                           bg-emerald-50 px-3 py-1
                                           text-xs font-medium
                                           text-emerald-700">

                                    Active

                                </span>

                            </td>


                            <!-- Action -->
                            <td class="px-6 py-4 text-right">

                                <a
                                    href="tenant-details.php?id=TEN-00124"
                                    class="inline-flex items-center gap-1
                                           font-medium text-primary-600
                                           hover:text-primary-800">

                                    View

                                    <svg
                                        class="h-4 w-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24">

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="m9 18 6-6-6-6"/>

                                    </svg>

                                </a>

                            </td>

                        </tr>


                        <!-- Mary Wanjiku -->
                        <tr class="transition hover:bg-slate-50">

                            <!-- Tenant -->
                            <td class="px-6 py-4">

                                <a
                                    href="tenant-details.php?id=TEN-00125"
                                    class="group flex items-center gap-3">

                                    <div
                                        class="flex h-10 w-10 shrink-0
                                               items-center justify-center
                                               rounded-full bg-pink-100
                                               font-semibold text-pink-700">

                                        MW

                                    </div>

                                    <div>

                                        <p
                                            class="font-medium text-slate-900
                                                   group-hover:text-primary-600">

                                            Mary Wanjiku

                                        </p>

                                        <p class="text-xs text-slate-500">
                                            mary@example.com
                                        </p>

                                    </div>

                                </a>

                            </td>


                            <!-- Phone -->
                            <td class="px-6 py-4 text-slate-600">

                                +254 723 456 789

                            </td>


                            <!-- Property -->
                            <td class="px-6 py-4">

                                <a
                                    href="property-details.php?id=PROP-002"
                                    class="text-slate-700 hover:text-primary-600">

                                    Sunrise Estate

                                </a>

                            </td>


                            <!-- Unit -->
                            <td class="px-6 py-4 font-medium text-slate-700">

                                B-204

                            </td>


                            <!-- Rent -->
                            <td class="px-6 py-4 font-medium text-slate-900">

                                KSh 35,000

                            </td>


                            <!-- Status -->
                            <td class="px-6 py-4">

                                <span
                                    class="inline-flex rounded-full
                                           bg-emerald-50 px-3 py-1
                                           text-xs font-medium
                                           text-emerald-700">

                                    Active

                                </span>

                            </td>


                            <!-- Action -->
                            <td class="px-6 py-4 text-right">

                                <a
                                    href="tenant-details.php?id=TEN-00125"
                                    class="inline-flex items-center gap-1
                                           font-medium text-primary-600
                                           hover:text-primary-800">

                                    View

                                    <svg
                                        class="h-4 w-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24">

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="m9 18 6-6-6-6"/>

                                    </svg>

                                </a>

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>


            <!-- Table Footer -->
            <div
                class="flex flex-col gap-3 border-t border-slate-200
                       px-6 py-4 text-sm text-slate-500
                       sm:flex-row sm:items-center sm:justify-between">

                <p>
                    Showing
                    <span class="font-medium text-slate-700">2</span>
                    of
                    <span class="font-medium text-slate-700">186</span>
                    tenants
                </p>

                <div class="flex items-center gap-2">

                    <button
                        type="button"
                        disabled
                        class="rounded-lg border border-slate-200
                               px-3 py-2 text-sm text-slate-400
                               cursor-not-allowed">

                        Previous

                    </button>

                    <button
                        type="button"
                        class="rounded-lg bg-primary-600
                               px-3 py-2 text-sm font-medium
                               text-white">

                        1

                    </button>

                    <button
                        type="button"
                        class="rounded-lg border border-slate-200
                               px-3 py-2 text-sm
                               hover:bg-slate-50">

                        Next

                    </button>

                </div>

            </div>

        </div>

    </div>

</main>


<?php require_once "../includes/footer.php"; ?>