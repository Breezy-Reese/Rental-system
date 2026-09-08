<?php

$pageTitle = "Dashboard";

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

?>

<!-- Main Content -->

<main class="flex-1 lg:ml-0">

    <!-- Top Bar -->

    <header class="sticky top-0 z-30 flex h-20 items-center
                   justify-between border-b border-slate-200
                   bg-white px-4 sm:px-6 lg:px-8">

        <div class="flex items-center gap-4">

            <button
                id="mobileMenuButton"
                class="rounded-lg p-2 hover:bg-slate-100 lg:hidden">

                ☰

            </button>

            <div>

                <h2 class="text-xl font-bold text-slate-900">
                    Dashboard
                </h2>

                <p class="hidden text-sm text-slate-500 sm:block">
                    Welcome back! Here's what's happening today.
                </p>

            </div>

        </div>


        <div class="flex items-center gap-3">

            <button
                class="relative rounded-lg p-2 hover:bg-slate-100">

                🔔

                <span
                    class="absolute right-1 top-1 h-2 w-2 rounded-full
                           bg-red-500">
                </span>

            </button>


            <div class="hidden h-8 w-px bg-slate-200 sm:block"></div>


            <div class="flex items-center gap-3">

                <div class="flex h-9 w-9 items-center justify-center
                            rounded-full bg-indigo-100 text-sm
                            font-semibold text-indigo-700">

                    BM

                </div>

                <div class="hidden md:block">

                    <p class="text-sm font-medium">
                        Property Manager
                    </p>

                    <p class="text-xs text-slate-500">
                        Administrator
                    </p>

                </div>

            </div>

        </div>

    </header>


    <!-- Page -->

    <div class="p-4 sm:p-6 lg:p-8">


        <!-- Page heading -->

        <div class="mb-8 flex flex-col justify-between gap-4
                    sm:flex-row sm:items-center">

            <div>

                <h1 class="text-2xl font-bold text-slate-900">
                    Overview
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Here's an overview of your properties.
                </p>

            </div>


            <a href="properties.php"
               class="inline-flex items-center justify-center
                      rounded-lg bg-indigo-600 px-4 py-2.5
                      text-sm font-semibold text-white
                      shadow-sm hover:bg-indigo-700">

                + Add Property

            </a>

        </div>


        <!-- Statistics -->

        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">


            <!-- Properties -->

            <div class="rounded-xl border border-slate-200
                        bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-sm font-medium text-slate-500">
                            Total Properties
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            24
                        </p>

                    </div>

                    <div class="flex h-12 w-12 items-center
                                justify-center rounded-xl bg-indigo-50
                                text-2xl">

                        🏢

                    </div>

                </div>

                <p class="mt-4 text-sm text-emerald-600">
                    ↑ 8.2% <span class="text-slate-400">
                        from last month
                    </span>
                </p>

            </div>


            <!-- Units -->

            <div class="rounded-xl border border-slate-200
                        bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-sm font-medium text-slate-500">
                            Occupied Units
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            186
                        </p>

                    </div>

                    <div class="flex h-12 w-12 items-center
                                justify-center rounded-xl bg-emerald-50
                                text-2xl">

                        🚪

                    </div>

                </div>

                <p class="mt-4 text-sm text-emerald-600">
                    89.4% occupancy
                </p>

            </div>


            <!-- Rent -->

            <div class="rounded-xl border border-slate-200
                        bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-sm font-medium text-slate-500">
                            Rent Collected
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            KSh 842K
                        </p>

                    </div>

                    <div class="flex h-12 w-12 items-center
                                justify-center rounded-xl bg-blue-50
                                text-2xl">

                        💰

                    </div>

                </div>

                <p class="mt-4 text-sm text-emerald-600">
                    ↑ 12.5%
                    <span class="text-slate-400">
                        this month
                    </span>
                </p>

            </div>


            <!-- Outstanding -->

            <div class="rounded-xl border border-slate-200
                        bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-sm font-medium text-slate-500">
                            Outstanding Rent
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            KSh 126K
                        </p>

                    </div>

                    <div class="flex h-12 w-12 items-center
                                justify-center rounded-xl bg-amber-50
                                text-2xl">

                        ⚠️

                    </div>

                </div>

                <p class="mt-4 text-sm text-red-600">
                    18 tenants overdue
                </p>

            </div>

        </div>


        <!-- Middle section -->

        <div class="mt-8 grid gap-6 xl:grid-cols-3">


            <!-- Recent payments -->

            <div class="rounded-xl border border-slate-200
                        bg-white shadow-sm xl:col-span-2">

                <div class="flex items-center justify-between
                            border-b border-slate-200 p-5">

                    <div>

                        <h2 class="font-semibold text-slate-900">
                            Recent Payments
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Latest rent payments received
                        </p>

                    </div>

                    <a href="payments.php"
                       class="text-sm font-medium text-indigo-600
                              hover:text-indigo-700">

                        View all

                    </a>

                </div>


                <div class="overflow-x-auto">

                    <table class="w-full text-left text-sm">

                        <thead class="bg-slate-50 text-xs
                                      uppercase text-slate-500">

                            <tr>

                                <th class="px-5 py-3">
                                    Tenant
                                </th>

                                <th class="px-5 py-3">
                                    Property
                                </th>

                                <th class="px-5 py-3">
                                    Amount
                                </th>

                                <th class="px-5 py-3">
                                    Date
                                </th>

                                <th class="px-5 py-3">
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100">

                            <tr class="hover:bg-slate-50">

                                <td class="px-5 py-4 font-medium">
                                    John Mwangi
                                </td>

                                <td class="px-5 py-4 text-slate-500">
                                    Greenview Apartments
                                </td>

                                <td class="px-5 py-4 font-semibold">
                                    KSh 25,000
                                </td>

                                <td class="px-5 py-4 text-slate-500">
                                    Sep 7, 2026
                                </td>

                                <td class="px-5 py-4">

                                    <span class="rounded-full bg-emerald-50
                                                 px-2.5 py-1 text-xs
                                                 font-medium text-emerald-700">

                                        Paid

                                    </span>

                                </td>

                            </tr>


                            <tr class="hover:bg-slate-50">

                                <td class="px-5 py-4 font-medium">
                                    Mary Wanjiku
                                </td>

                                <td class="px-5 py-4 text-slate-500">
                                    Sunrise Estate
                                </td>

                                <td class="px-5 py-4 font-semibold">
                                    KSh 18,500
                                </td>

                                <td class="px-5 py-4 text-slate-500">
                                    Sep 6, 2026
                                </td>

                                <td class="px-5 py-4">

                                    <span class="rounded-full bg-emerald-50
                                                 px-2.5 py-1 text-xs
                                                 font-medium text-emerald-700">

                                        Paid

                                    </span>

                                </td>

                            </tr>


                            <tr class="hover:bg-slate-50">

                                <td class="px-5 py-4 font-medium">
                                    Peter Kamau
                                </td>

                                <td class="px-5 py-4 text-slate-500">
                                    Palm Heights
                                </td>

                                <td class="px-5 py-4 font-semibold">
                                    KSh 30,000
                                </td>

                                <td class="px-5 py-4 text-slate-500">
                                    Sep 5, 2026
                                </td>

                                <td class="px-5 py-4">

                                    <span class="rounded-full bg-amber-50
                                                 px-2.5 py-1 text-xs
                                                 font-medium text-amber-700">

                                        Pending

                                    </span>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>


            <!-- Maintenance -->

            <div class="rounded-xl border border-slate-200
                        bg-white shadow-sm">

                <div class="border-b border-slate-200 p-5">

                    <h2 class="font-semibold text-slate-900">
                        Maintenance Requests
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Recent issues reported
                    </p>

                </div>


                <div class="space-y-4 p-5">

                    <div class="flex gap-3">

                        <div class="flex h-10 w-10 shrink-0
                                    items-center justify-center
                                    rounded-lg bg-red-50">

                            🔧

                        </div>

                        <div class="min-w-0">

                            <p class="text-sm font-medium">
                                Broken water pipe
                            </p>

                            <p class="text-xs text-slate-500">
                                Greenview • Unit 204
                            </p>

                            <span class="mt-1 inline-block
                                         text-xs font-medium text-red-600">

                                Urgent

                            </span>

                        </div>

                    </div>


                    <div class="flex gap-3">

                        <div class="flex h-10 w-10 shrink-0
                                    items-center justify-center
                                    rounded-lg bg-amber-50">

                            💡

                        </div>

                        <div class="min-w-0">

                            <p class="text-sm font-medium">
                                Faulty electricity
                            </p>

                            <p class="text-xs text-slate-500">
                                Sunrise • Unit A12
                            </p>

                            <span class="mt-1 inline-block
                                         text-xs font-medium
                                         text-amber-600">

                                Pending

                            </span>

                        </div>

                    </div>


                    <div class="flex gap-3">

                        <div class="flex h-10 w-10 shrink-0
                                    items-center justify-center
                                    rounded-lg bg-blue-50">

                            🚿

                        </div>

                        <div class="min-w-0">

                            <p class="text-sm font-medium">
                                Leaking shower
                            </p>

                            <p class="text-xs text-slate-500">
                                Palm Heights • Unit 12
                            </p>

                            <span class="mt-1 inline-block
                                         text-xs font-medium
                                         text-blue-600">

                                Assigned

                            </span>

                        </div>

                    </div>

                </div>


                <div class="border-t border-slate-200 p-4">

                    <a href="maintenance.php"
                       class="block text-center text-sm font-medium
                              text-indigo-600 hover:text-indigo-700">

                        View maintenance requests →

                    </a>

                </div>

            </div>

        </div>

    </div>

</main>

<?php require_once "../includes/footer.php"; ?>