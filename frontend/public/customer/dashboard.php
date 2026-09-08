<?php

require_once __DIR__ . "/../../includes/auth.php";
require_login();

if (current_role() !== 'Customer') {
    header("Location: ../admin/dashboard.php");
    exit;
}

require_once __DIR__ . "/../../includes/data.php";

$pageTitle = "Customer Dashboard";

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";

$user = current_user();

/*
|--------------------------------------------------------------------------
| Demo customer information
|--------------------------------------------------------------------------
*/

$customerName = $user['name'] ?? 'Customer';

$currentLease = $leases[0] ?? [];
$currentPayment = $payments[0] ?? [];

$monthlyRent = (float) ($currentPayment['amount'] ?? 0);

$nextPayment = $currentPayment['status'] ?? 'Pending';

$maintenanceForCustomer = array_filter(
    $maintenanceRequests,
    function ($request) use ($customerName) {
        return ($request['tenant'] ?? '') === $customerName;
    }
);
?>

<div class="lg:pl-64">

    <!-- Header -->
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
                    <?= htmlspecialchars($customerName) ?>
                </p>

                <p class="text-xs text-slate-500">
                    Customer
                </p>

            </div>

            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-600 font-semibold text-white">
                <?= htmlspecialchars(strtoupper(substr($customerName, 0, 1))) ?>
            </div>

        </div>

    </header>

    <!-- Main -->
    <main class="p-4 sm:p-6 lg:p-8">

        <!-- Welcome -->
        <div class="mb-8">

            <p class="text-sm font-medium text-indigo-600">
                Customer Portal
            </p>

            <h1 class="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">
                Welcome, <?= htmlspecialchars($customerName) ?>
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Manage your rental, payments and maintenance requests.
            </p>

        </div>

        <!-- Customer Stats -->
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

            <!-- Property -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">

                <p class="text-sm text-slate-500">
                    My Property
                </p>

                <p class="mt-2 text-lg font-bold text-slate-900">
                    <?= htmlspecialchars($currentLease['property'] ?? 'Greenview Apartments') ?>
                </p>

                <a
                    href="leases.php"
                    class="mt-4 inline-block text-sm font-medium text-indigo-600">
                    View lease →
                </a>

            </div>

            <!-- Unit -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">

                <p class="text-sm text-slate-500">
                    My Unit
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-900">
                    <?= htmlspecialchars($currentLease['unit'] ?? 'A-101') ?>
                </p>

                <p class="mt-4 text-xs text-green-600">
                    Currently occupied
                </p>

            </div>

            <!-- Rent -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">

                <p class="text-sm text-slate-500">
                    Monthly Rent
                </p>

                <p class="mt-2 text-2xl font-bold text-slate-900">
                    KES <?= number_format($monthlyRent) ?>
                </p>

                <p class="mt-4 text-xs text-slate-500">
                    Current rental amount
                </p>

            </div>

            <!-- Payment -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">

                <p class="text-sm text-slate-500">
                    Payment Status
                </p>

                <p class="mt-2 text-xl font-bold <?= $nextPayment === 'Paid'
                    ? 'text-green-600'
                    : 'text-orange-600' ?>">
                    <?= htmlspecialchars($nextPayment) ?>
                </p>

                <a
                    href="payments.php"
                    class="mt-4 inline-block text-sm font-medium text-indigo-600">
                    View payments →
                </a>

            </div>

        </div>

        <!-- Main Customer Content -->
        <div class="mt-8 grid gap-6 lg:grid-cols-3">

            <!-- Current Lease -->
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 lg:col-span-2">

                <div class="flex items-center justify-between">

                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">
                            My Current Lease
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Your current rental information
                        </p>
                    </div>

                    <a
                        href="leases.php"
                        class="text-sm font-medium text-indigo-600">
                        Details →
                    </a>

                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">

                    <div class="rounded-lg bg-slate-50 p-4">

                        <p class="text-xs text-slate-500">
                            Property
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= htmlspecialchars($currentLease['property'] ?? 'Greenview Apartments') ?>
                        </p>

                    </div>

                    <div class="rounded-lg bg-slate-50 p-4">

                        <p class="text-xs text-slate-500">
                            Unit
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= htmlspecialchars($currentLease['unit'] ?? 'A-101') ?>
                        </p>

                    </div>

                    <div class="rounded-lg bg-slate-50 p-4">

                        <p class="text-xs text-slate-500">
                            Lease Start
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= htmlspecialchars($currentLease['start_date'] ?? '01 Jan 2026') ?>
                        </p>

                    </div>

                    <div class="rounded-lg bg-slate-50 p-4">

                        <p class="text-xs text-slate-500">
                            Lease Status
                        </p>

                        <p class="mt-1 font-semibold text-green-600">
                            Active
                        </p>

                    </div>

                </div>

            </div>

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
                        <span class="text-sm font-medium">Find Property</span>
                    </a>

                    <a
                        href="payments.php"
                        class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                        <span class="text-xl">💳</span>
                        <span class="text-sm font-medium">Make Payment</span>
                    </a>

                    <a
                        href="maintenance.php"
                        class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                        <span class="text-xl">🔧</span>
                        <span class="text-sm font-medium">Report Maintenance</span>
                    </a>

                    <a
                        href="profile.php"
                        class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                        <span class="text-xl">👤</span>
                        <span class="text-sm font-medium">My Profile</span>
                    </a>

                </div>

            </div>

        </div>

        <!-- Payments & Maintenance -->
        <div class="mt-8 grid gap-6 lg:grid-cols-2">

            <!-- Recent Payments -->
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">

                <div class="flex items-center justify-between border-b border-slate-200 p-5">

                    <div>
                        <h2 class="font-semibold text-slate-900">
                            My Recent Payments
                        </h2>

                        <p class="text-xs text-slate-500">
                            Your payment history
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
                                    <?= htmlspecialchars($payment['date'] ?? 'Recent payment') ?>
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
                            My Maintenance
                        </h2>

                        <p class="text-xs text-slate-500">
                            Your maintenance requests
                        </p>

                    </div>

                    <a
                        href="maintenance.php"
                        class="text-sm font-medium text-indigo-600">
                        View all
                    </a>

                </div>

                <div class="p-5">

                    <?php if (count($maintenanceForCustomer) > 0): ?>

                        <?php foreach ($maintenanceForCustomer as $request): ?>

                            <div class="border-b border-slate-100 py-3 last:border-0">

                                <p class="text-sm font-medium text-slate-800">
                                    <?= htmlspecialchars($request['title'] ?? 'Maintenance request') ?>
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    <?= htmlspecialchars($request['status'] ?? 'Pending') ?>
                                </p>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="py-8 text-center">

                            <div class="text-3xl">
                                🔧
                            </div>

                            <p class="mt-3 text-sm font-medium text-slate-700">
                                No maintenance requests
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                Everything looks good.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </main>

</div>

<?php
require_once __DIR__ . "/../../includes/footer.php";
?>