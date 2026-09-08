<?php

require_once "../../includes/admin.php";
require_admin();

require_once "../../includes/data.php";

$pageTitle = "Payments";

$statusFilter = $_GET['status'] ?? '';
$tenantFilter = $_GET['tenant'] ?? '';

$filteredPayments = $payments;

if ($statusFilter !== '') {
    $filteredPayments = array_filter(
        $filteredPayments,
        fn($payment) => ($payment['status'] ?? '') === $statusFilter
    );
}

if ($tenantFilter !== '') {
    $filteredPayments = array_filter(
        $filteredPayments,
        fn($payment) => ($payment['tenant_id'] ?? '') === $tenantFilter
    );
}

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
?>

<main class="min-h-screen bg-slate-50 lg:ml-64">

    <header class="border-b border-slate-200 bg-white">

        <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">

            <button
                id="mobileMenuButton"
                type="button"
                class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden">
                ☰
            </button>

            <div>
                <h1 class="text-lg font-semibold text-slate-900">
                    Payments
                </h1>

                <p class="hidden text-xs text-slate-500 sm:block">
                    Manage rent payments
                </p>
            </div>

            <a
                href="#"
                class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                + Record Payment
            </a>

        </div>

    </header>


    <div class="p-4 sm:p-6 lg:p-8">

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-slate-900">
                Payment Records
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Track rent collection and payment status.
            </p>
        </div>


        <div class="mb-6 flex flex-wrap gap-2">

            <a
                href="payments.php"
                class="rounded-lg px-4 py-2 text-sm font-medium <?= $statusFilter === '' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600 border border-slate-200' ?>">
                All
            </a>

            <a
                href="payments.php?status=Paid"
                class="rounded-lg px-4 py-2 text-sm font-medium <?= $statusFilter === 'Paid' ? 'bg-green-600 text-white' : 'bg-white text-slate-600 border border-slate-200' ?>">
                Paid
            </a>

            <a
                href="payments.php?status=Pending"
                class="rounded-lg px-4 py-2 text-sm font-medium <?= $statusFilter === 'Pending' ? 'bg-amber-500 text-white' : 'bg-white text-slate-600 border border-slate-200' ?>">
                Pending
            </a>

        </div>


        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="overflow-x-auto">

                <table class="w-full text-left text-sm">

                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">

                        <tr>
                            <th class="px-6 py-4">Reference</th>
                            <th class="px-6 py-4">Tenant</th>
                            <th class="px-6 py-4">Property</th>
                            <th class="px-6 py-4">Amount</th>
                            <th class="px-6 py-4">Method</th>
                            <th class="px-6 py-4">Status</th>
                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        <?php foreach ($filteredPayments as $payment): ?>

                            <tr class="hover:bg-slate-50">

                                <td class="px-6 py-4 font-medium text-slate-900">
                                    <?= e($payment['id']) ?>
                                </td>

                                <td class="px-6 py-4 text-slate-600">
                                    <?= e($payment['tenant']) ?>
                                </td>

                                <td class="px-6 py-4 text-slate-600">
                                    <?= e($payment['property']) ?>
                                </td>

                                <td class="px-6 py-4 font-semibold text-slate-900">
                                    <?= money($payment['amount']) ?>
                                </td>

                                <td class="px-6 py-4 text-slate-600">
                                    <?= e($payment['method'] ?? '-') ?>
                                </td>

                                <td class="px-6 py-4">

                                    <?php if (($payment['status'] ?? '') === 'Paid'): ?>

                                        <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                                            Paid
                                        </span>

                                    <?php else: ?>

                                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-700">
                                            Pending
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</main>

<?php require_once "../../includes/footer.php"; ?>