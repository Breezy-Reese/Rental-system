<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Mobile Overlay -->
<div
    id="sidebarOverlay"
    class="fixed inset-0 z-40 hidden bg-black/50 lg:hidden">
</div>


<!-- Sidebar -->
<aside
    id="sidebar"
    class="fixed inset-y-0 left-0 z-50 w-64
           -translate-x-full bg-slate-950 text-white
           transition-transform duration-300 ease-in-out">

    <div class="flex h-full flex-col">

        <!-- Logo / Header -->
        <div class="flex h-20 items-center justify-between
                    border-b border-slate-800 px-6">

            <div class="flex items-center gap-3">

                <div class="flex h-10 w-10 items-center justify-center
                            rounded-xl bg-primary-600">

                    <svg
                        class="h-6 w-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 12l9-9 9 9M5 10v10h14V10M9 20v-6h6v6"/>
                    </svg>

                </div>

                <div>
                    <h1 class="text-lg font-bold">
                        PropertyPro
                    </h1>

                    <p class="text-xs text-slate-400">
                        Rental Management
                    </p>
                </div>

            </div>


            <!-- Close Sidebar Button -->
            <button
                id="closeSidebar"
                type="button"
                aria-label="Close sidebar">

                <svg
                    class="h-6 w-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24">

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"/>

                </svg>

            </button>

        </div>


        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-4 py-6">

            <p class="mb-3 px-3 text-xs font-semibold uppercase
                      tracking-wider text-slate-500">
                Overview
            </p>


            <!-- Dashboard -->
            <a
                href="dashboard.php"
                class="<?= $currentPage === 'dashboard.php'
                    ? 'bg-primary-600 text-white'
                    : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>
                    mb-1 flex items-center gap-3 rounded-lg px-3 py-3 text-sm">

                <span>📊</span>
                <span>Dashboard</span>

            </a>


            <!-- Properties -->
            <a
                href="properties.php"
                class="<?= $currentPage === 'properties.php'
                    ? 'bg-primary-600 text-white'
                    : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>
                    mb-1 flex items-center gap-3 rounded-lg px-3 py-3 text-sm">

                <span>🏢</span>
                <span>Properties</span>

            </a>


            <!-- Units -->
            <a
                href="units.php"
                class="<?= $currentPage === 'units.php'
                    ? 'bg-primary-600 text-white'
                    : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>
                    mb-1 flex items-center gap-3 rounded-lg px-3 py-3 text-sm">

                <span>🚪</span>
                <span>Units</span>

            </a>


            <!-- Tenants -->
            <a
                href="tenants.php"
                class="<?= $currentPage === 'tenants.php'
                    ? 'bg-primary-600 text-white'
                    : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>
                    mb-1 flex items-center gap-3 rounded-lg px-3 py-3 text-sm">

                <span>👥</span>
                <span>Tenants</span>

            </a>


            <!-- Finance -->
            <p class="mb-3 mt-8 px-3 text-xs font-semibold uppercase
                      tracking-wider text-slate-500">
                Finance
            </p>


            <!-- Payments -->
            <a
                href="payments.php"
                class="<?= $currentPage === 'payments.php'
                    ? 'bg-primary-600 text-white'
                    : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>
                    mb-1 flex items-center gap-3 rounded-lg px-3 py-3 text-sm">

                <span>💰</span>
                <span>Payments</span>

            </a>


            <!-- Leases -->
            <a
                href="leases.php"
                class="<?= $currentPage === 'leases.php'
                    ? 'bg-primary-600 text-white'
                    : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>
                    mb-1 flex items-center gap-3 rounded-lg px-3 py-3 text-sm">

                <span>📄</span>
                <span>Leases</span>

            </a>


            <!-- Expenses -->
            <a
                href="expenses.php"
                class="<?= $currentPage === 'expenses.php'
                    ? 'bg-primary-600 text-white'
                    : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>
                    mb-1 flex items-center gap-3 rounded-lg px-3 py-3 text-sm">

                <span>💳</span>
                <span>Expenses</span>

            </a>


            <!-- Operations -->
            <p class="mb-3 mt-8 px-3 text-xs font-semibold uppercase
                      tracking-wider text-slate-500">
                Operations
            </p>


            <!-- Maintenance -->
            <a
                href="maintenance.php"
                class="<?= $currentPage === 'maintenance.php'
                    ? 'bg-primary-600 text-white'
                    : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>
                    mb-1 flex items-center gap-3 rounded-lg px-3 py-3 text-sm">

                <span>🔧</span>
                <span>Maintenance</span>

            </a>


            <!-- Reports -->
            <a
                href="reports.php"
                class="<?= $currentPage === 'reports.php'
                    ? 'bg-primary-600 text-white'
                    : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>
                    mb-1 flex items-center gap-3 rounded-lg px-3 py-3 text-sm">

                <span>📈</span>
                <span>Reports</span>

            </a>

        </nav>


        <!-- User -->
        <div class="border-t border-slate-800 p-4">

            <div class="flex items-center gap-3">

                <div
                    class="flex h-10 w-10 items-center justify-center
                           rounded-full bg-primary-600 font-semibold">

                    BM

                </div>

                <div class="min-w-0 flex-1">

                    <p class="truncate text-sm font-medium">
                        Property Manager
                    </p>

                    <p class="truncate text-xs text-slate-400">
                        Administrator
                    </p>

                </div>

            </div>


            <!-- Logout -->
            <a
                href="logout.php"
                class="mt-4 flex items-center gap-3 rounded-lg
                       px-3 py-3 text-sm text-slate-300
                       transition hover:bg-red-500/10 hover:text-red-400">

                <span>🚪</span>

                <span>
                    Logout
                </span>

            </a>

        </div>

    </div>

</aside>