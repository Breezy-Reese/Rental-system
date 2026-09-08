<?php

require_once __DIR__ . "/../../includes/admin.php";
require_admin();

require_once __DIR__ . "/../../includes/data.php";

$pageTitle = "Admin Dashboard";

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";

$user = current_user();

/*
|--------------------------------------------------------------------------
| Dashboard calculations
|--------------------------------------------------------------------------
*/

$totalProperties = count($properties);
$totalUnits = count($units);
$totalTenants = count($tenants);

$occupiedUnits = 0;
$vacantUnits = 0;

foreach ($units as $unit) {
    if (($unit['status'] ?? '') === 'Occupied') {
        $occupiedUnits++;
    } else {
        $vacantUnits++;
    }
}

$totalPaid = 0;
$totalPending = 0;

foreach ($payments as $payment) {
    $amount = (float) ($payment['amount'] ?? 0);

    if (($payment['status'] ?? '') === 'Paid') {
        $totalPaid += $amount;
    } else {
        $totalPending += $amount;
    }
}

$maintenanceCount = count($maintenanceRequests);
?>

<div class="lg:pl-64">

    <!-- Mobile Header -->
    <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 lg:px-8">

        <button
            id="openSidebar"
            type="button"
            class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden">
            ☰
        </button>

        <div class="ml-auto flex items-center gap-4">

            <div class="hidden text-right sm:block">
                <p class="text-sm font-semibold text-slate-800">
                    <?= htmlspecialchars($user['name'] ?? 'Administrator') ?>
                </p>

                <p class="text-xs text-slate-500">
                    Administrator
                </p>
            </div>

            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-600 font-semibold text-white">
                <?= htmlspecialchars(substr($user['name'] ?? 'A', 0, 1)) ?>
            </div>

        </div>

    </header>

    <!-- Main -->
    <main class="p-4 sm:p-6 lg:p-8">

        <!-- Heading -->
        <div class="mb-8">

            <p class="text-sm font-medium text-indigo-600">
                Administration
            </p>

            <h1 class="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">
                Admin Dashboard
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Overview of your property rental operations.
            </p>

        </div>

        <!-- Statistics -->
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

            <!-- Properties -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm text-slate-500">
                            Properties
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            <?= $totalProperties ?>
                        </p>
                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-2xl">
                        🏢
                    </div>

                </div>

                <a
                    href="properties.php"
                    class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-700">
                    Manage properties →
                </a>

            </div>

            <!-- Units -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm text-slate-500">
                            Units
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            <?= $totalUnits ?>
                        </p>
                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-2xl">
                        🚪
                    </div>

                </div>

                <div class="mt-4 flex gap-4 text-xs">
                    <span class="text-green-600">
                        <?= $occupiedUnits ?> occupied
                    </span>

                    <span class="text-orange-600">
                        <?= $vacantUnits ?> vacant
                    </span>
                </div>

            </div>

            <!-- Tenants -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm text-slate-500">
                            Tenants
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            <?= $totalTenants ?>
                        </p>
                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 text-2xl">
                        👥
                    </div>

                </div>

                <a
                    href="tenants.php"
                    class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-700">
                    View tenants →
                </a>

            </div>

            <!-- Payments -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm text-slate-500">
                            Rent Collected
                        </p>

                        <p class="mt-2 text-2xl font-bold text-slate-900">
                            KES <?= number_format($totalPaid) ?>
                        </p>
                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-50 text-2xl">
                        💰
                    </div>

                </div>

                <p class="mt-4 text-xs text-orange-600">
                    KES <?= number_format($totalPending) ?> pending
                </p>

            </div>

        </div>

        <!-- Middle Section -->
        <div class="mt-8 grid gap-6 lg:grid-cols-3">

            <!-- Quick Actions -->
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">

                <h2 class="text-lg font-semibold text-slate-900">
                    Quick Actions
                </h2>

                <div class="mt-5 space-y-3">

                    <a
                        href="properties.php"
                        class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                        <span class="text-xl">🏢</span>
                        <span class="text-sm font-medium">Manage Properties</span>
                    </a>

                    <a
                        href="tenants.php"
                        class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                        <span class="text-xl">👥</span>
                        <span class="text-sm font-medium">Manage Tenants</span>
                    </a>

                    <a
                        href="payments.php"
                        class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                        <span class="text-xl">💳</span>
                        <span class="text-sm font-medium">View Payments</span>
                    </a>

                    <a
                        href="maintenance.php"
                        class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                        <span class="text-xl">🔧</span>
                        <span class="text-sm font-medium">Maintenance Requests</span>
                    </a>

                </div>

            </div>

            <!-- Occupancy -->
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 lg:col-span-2">

                <div class="flex items-center justify-between">

                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">
                            Occupancy Overview
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Current unit occupancy
                        </p>
                    </div>

                    <a
                        href="units.php"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                        View units →
                    </a>

                </div>

                <div class="mt-6">

                    <?php
                    $occupancyPercentage = $totalUnits > 0
                        ? round(($occupiedUnits / $totalUnits) * 100)
                        : 0;
                    ?>

                    <div class="mb-2 flex justify-between text-sm">

                        <span class="text-slate-600">
                            Occupancy
                        </span>

                        <span class="font-semibold text-slate-900">
                            <?= $occupancyPercentage ?>%
                        </span>

                    </div>

                    <div class="h-3 overflow-hidden rounded-full bg-slate-100">

                        <div
                            class="h-full rounded-full bg-indigo-600"
                            style="width: <?= $occupancyPercentage ?>%">
                        </div>

                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-4">

                        <div class="rounded-lg bg-green-50 p-4">

                            <p class="text-xs text-green-600">
                                Occupied
                            </p>

                            <p class="mt-1 text-2xl font-bold text-green-700">
                                <?= $occupiedUnits ?>
                            </p>

                        </div>

                        <div class="rounded-lg bg-orange-50 p-4">

                            <p class="text-xs text-orange-600">
                                Vacant
                            </p>

                            <p class="mt-1 text-2xl font-bold text-orange-700">
                                <?= $vacantUnits ?>
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- Bottom Section -->
        <div class="mt-8 grid gap-6 lg:grid-cols-2">

            <!-- Recent Payments -->
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">

                <div class="flex items-center justify-between border-b border-slate-200 p-5">

                    <div>
                        <h2 class="font-semibold text-slate-900">
                            Recent Payments
                        </h2>

                        <p class="text-xs text-slate-500">
                            Latest rental payments
                        </p>
                    </div>

                    <a
                        href="payments.php"
                        class="text-sm font-medium text-indigo-600">
                        View all
                    </a>

                </div>

                <div class="divide-y divide-slate-100">

                    <?php foreach (array_slice($payments, 0, 4) as $payment): ?>

                        <div class="flex items-center justify-between p-4">

                            <div>

                                <p class="text-sm font-medium text-slate-800">
                                    <?= htmlspecialchars($payment['tenant'] ?? 'Tenant') ?>
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    <?= htmlspecialchars($payment['method'] ?? 'Payment') ?>
                                </p>

                            </div>

                            <div class="text-right">

                                <p class="text-sm font-semibold text-slate-900">
                                    KES <?= number_format((float)($payment['amount'] ?? 0)) ?>
                                </p>

                                <span class="text-xs <?= ($payment['status'] ?? '') === 'Paid'
                                    ? 'text-green-600'
                                    : 'text-orange-600' ?>">
                                    <?= htmlspecialchars($payment['status'] ?? 'Pending') ?>
                                </span>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

            <!-- Maintenance -->
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">

                <div class="flex items-center justify-between border-b border-slate-200 p-5">

                    <div>
                        <h2 class="font-semibold text-slate-900">
                            Maintenance
                        </h2>

                        <p class="text-xs text-slate-500">
                            Current maintenance requests
                        </p>
                    </div>

                    <a
                        href="maintenance.php"
                        class="text-sm font-medium text-indigo-600">
                        View all
                    </a>

                </div>

                <div class="p-5">

                    <div class="flex items-center gap-4">

                        <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-orange-50 text-2xl">
                            🔧
                        </div>

                        <div>

                            <p class="text-2xl font-bold text-slate-900">
                                <?= $maintenanceCount ?>
                            </p>

                            <p class="text-sm text-slate-500">
                                Active requests
                            </p>

                        </div>

                    </div>

                    <div class="mt-5">

                        <?php foreach (array_slice($maintenanceRequests, 0, 3) as $request): ?>

                            <div class="border-t border-slate-100 py-3">

                                <p class="text-sm font-medium text-slate-800">
                                    <?= htmlspecialchars($request['title'] ?? 'Maintenance request') ?>
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    <?= htmlspecialchars($request['property'] ?? '') ?>
                                </p>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

            </div>

        </div>

    </main>

</div>

<?php
require_once __DIR__ . "/../../includes/footer.php";
?>