<?php

require_once "../includes/auth.php";
require_login();

require_once "../includes/data.php";

$pageTitle = "Payments";

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$tenantFilter = $_GET['tenant'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$visiblePayments = $payments;

if ($tenantFilter !== '') {
    $visiblePayments = array_filter(
        $visiblePayments,
        fn($payment) => $payment['tenant_id'] === $tenantFilter
    );
}

if ($statusFilter !== '') {
    $visiblePayments = array_filter(
        $visiblePayments,
        fn($payment) => $payment['status'] === $statusFilter
    );
}

/*
|--------------------------------------------------------------------------
| Payment Statistics
|--------------------------------------------------------------------------
*/

$collected = 842000;
$outstanding = 126000;

$paidCount = count(
    array_filter(
        $payments,
        fn($payment) => $payment['status'] === 'Paid'
    )
);

$pendingCount = count(
    array_filter(
        $payments,
        fn($payment) => $payment['status'] === 'Pending'
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
                    Payments
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Track rent payments and outstanding balances.
                </p>
            </div>

            <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-lg
                       bg-primary-600 px-4 py-2.5 text-sm font-semibold
                       text-white shadow-sm transition
                       hover:bg-primary-700">

                <span class="text-lg">+</span>
                Record Payment

            </button>

        </div>

        <!-- Statistics -->
        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            <!-- Collected -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm text-slate-500">
                            Collected This Month
                        </p>

                        <p class="mt-2 text-2xl font-bold text-emerald-600">
                            <?= money($collected) ?>
                        </p>
                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-xl">
                        💰
                    </div>

                </div>

            </div>

            <!-- Outstanding -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm text-slate-500">
                            Outstanding
                        </p>

                        <p class="mt-2 text-2xl font-bold text-red-600">
                            <?= money($outstanding) ?>
                        </p>
                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-50 text-xl">
                        ⚠️
                    </div>

                </div>

            </div>

            <!-- Paid -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm text-slate-500">
                            Paid
                        </p>

                        <p class="mt-2 text-2xl font-bold text-slate-900">
                            <?= $paidCount ?>
                        </p>
                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-50 text-xl">
                        ✓
                    </div>

                </div>

            </div>

            <!-- Pending -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm text-slate-500">
                            Pending
                        </p>

                        <p class="mt-2 text-2xl font-bold text-amber-600">
                            <?= $pendingCount ?>
                        </p>
                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-xl">
                        ⏳
                    </div>

                </div>

            </div>

        </div>

        <!-- Filters -->
        <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">

            <form
                method="GET"
                class="grid grid-cols-1 gap-4 md:grid-cols-3">

                <!-- Search -->
                <div class="relative">

                    <span
                        class="pointer-events-none absolute left-3 top-1/2
                               -translate-y-1/2 text-slate-400">

                        🔍

                    </span>

                    <input
                        type="text"
                        placeholder="Search payment..."
                        class="w-full rounded-lg border border-slate-200
                               bg-slate-50 py-2.5 pl-10 pr-4 text-sm
                               outline-none focus:border-primary-500
                               focus:ring-2 focus:ring-primary-100">

                </div>

                <!-- Tenant -->
                <select
                    name="tenant"
                    onchange="this.form.submit()"
                    class="rounded-lg border border-slate-200
                           bg-slate-50 px-4 py-2.5 text-sm
                           text-slate-700 outline-none
                           focus:border-primary-500
                           focus:ring-2 focus:ring-primary-100">

                    <option value="">
                        All Tenants
                    </option>

                    <?php foreach ($tenants as $tenant): ?>

                        <option
                            value="<?= e($tenant['id']) ?>"
                            <?= $tenantFilter === $tenant['id'] ? 'selected' : '' ?>>

                            <?= e($tenant['name']) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

                <!-- Status -->
                <select
                    name="status"
                    onchange="this.form.submit()"
                    class="rounded-lg border border-slate-200
                           bg-slate-50 px-4 py-2.5 text-sm
                           text-slate-700 outline-none
                           focus:border-primary-500
                           focus:ring-2 focus:ring-primary-100">

                    <option value="">
                        All Statuses
                    </option>

                    <option
                        value="Paid"
                        <?= $statusFilter === 'Paid' ? 'selected' : '' ?>>

                        Paid

                    </option>

                    <option
                        value="Pending"
                        <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>

                        Pending

                    </option>

                </select>

            </form>

        </div>

        <!-- Payments Table -->
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-50">

                        <tr>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Payment
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Tenant
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Property / Unit
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Amount
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Date
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

                        <?php if (empty($visiblePayments)): ?>

                            <tr>

                                <td
                                    colspan="7"
                                    class="px-6 py-12 text-center">

                                    <div class="text-4xl">
                                        💳
                                    </div>

                                    <p class="mt-3 font-semibold text-slate-700">
                                        No payments found
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Try changing your filters.
                                    </p>

                                </td>

                            </tr>

                        <?php endif; ?>


                        <?php foreach ($visiblePayments as $payment): ?>

                            <tr class="transition hover:bg-slate-50">

                                <!-- Payment ID -->
                                <td class="whitespace-nowrap px-6 py-4">

                                    <span class="font-semibold text-slate-800">
                                        <?= e($payment['id']) ?>
                                    </span>

                                </td>

                                <!-- Tenant -->
                                <td class="px-6 py-4">

                                    <a
                                        href="tenant-details.php?id=<?= urlencode($payment['tenant_id']) ?>"
                                        class="font-semibold text-primary-600 hover:text-primary-800 hover:underline">

                                        <?= e($payment['tenant']) ?>

                                    </a>

                                </td>

                                <!-- Property / Unit -->
                                <td class="px-6 py-4">

                                    <?php
                                    $tenant = $tenants[$payment['tenant_id']] ?? null;
                                    ?>

                                    <?php if ($tenant): ?>

                                        <a
                                            href="property-details.php?id=<?= urlencode($tenant['property_id']) ?>"
                                            class="font-medium text-slate-800 hover:text-primary-600">

                                            <?= e($payment['property']) ?>

                                        </a>

                                    <?php else: ?>

                                        <span class="font-medium text-slate-800">
                                            <?= e($payment['property']) ?>
                                        </span>

                                    <?php endif; ?>

                                    <p class="mt-1 text-xs text-slate-400">
                                        Unit <?= e($payment['unit']) ?>
                                    </p>

                                </td>

                                <!-- Amount -->
                                <td class="whitespace-nowrap px-6 py-4">

                                    <span class="font-semibold text-slate-900">
                                        <?= money($payment['amount']) ?>
                                    </span>

                                </td>

                                <!-- Date -->
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">

                                    <?= e($payment['date']) ?>

                                </td>

                                <!-- Status -->
                                <td class="whitespace-nowrap px-6 py-4">

                                    <?php if ($payment['status'] === 'Paid'): ?>

                                        <span
                                            class="rounded-full bg-emerald-100 px-3 py-1
                                                   text-xs font-semibold text-emerald-700">

                                            Paid

                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="rounded-full bg-amber-100 px-3 py-1
                                                   text-xs font-semibold text-amber-700">

                                            Pending

                                        </span>

                                    <?php endif; ?>

                                </td>

                                <!-- Action -->
                                <td class="whitespace-nowrap px-6 py-4 text-right">

                                    <a
                                        href="tenant-details.php?id=<?= urlencode($payment['tenant_id']) ?>"
                                        class="text-sm font-semibold text-primary-600 hover:text-primary-800">

                                        View Tenant

                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <!-- Footer -->
            <div class="border-t border-slate-200 px-6 py-4">

                <p class="text-sm text-slate-500">

                    Showing

                    <span class="font-semibold text-slate-700">
                        <?= count($visiblePayments) ?>
                    </span>

                    payment record(s)

                </p>

            </div>

        </div>

    </div>

</main>

<?php require_once "../includes/footer.php"; ?>