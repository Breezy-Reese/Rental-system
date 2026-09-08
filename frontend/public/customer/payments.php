<?php

$pageTitle = "My Payments";

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../../includes/auth.php";

require_login();

/*
|--------------------------------------------------------------------------
| Customer-only access
|--------------------------------------------------------------------------
*/

if (current_role() !== 'Customer') {
    header("Location: ../admin/dashboard.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Load application data
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../../includes/data.php";

/*
|--------------------------------------------------------------------------
| Current customer
|--------------------------------------------------------------------------
*/

$user = current_user();

$customerName = $user['name'] ?? '';

/*
|--------------------------------------------------------------------------
| Get customer's payments only
|--------------------------------------------------------------------------
*/

$customerPayments = [];

foreach ($payments as $payment) {

    if (
        isset($payment['tenant']) &&
        strcasecmp($payment['tenant'], $customerName) === 0
    ) {
        $customerPayments[] = $payment;
    }
}

/*
|--------------------------------------------------------------------------
| Calculate totals
|--------------------------------------------------------------------------
*/

$totalPaid = 0;
$totalPending = 0;

foreach ($customerPayments as $payment) {

    $amount = (float) ($payment['amount'] ?? 0);

    $status = strtolower($payment['status'] ?? '');

    if ($status === 'paid') {
        $totalPaid += $amount;
    }

    if ($status === 'pending') {
        $totalPending += $amount;
    }
}


/*
|--------------------------------------------------------------------------
| Page layout
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../../includes/header.php";

require_once __DIR__ . "/../../includes/sidebar.php";

?>

<main class="flex-1 lg:ml-64">

    <?php require_once __DIR__ . "/../../includes/navbar.php"; ?>


    <div class="p-4 sm:p-6 lg:p-8">

        <!-- Page Header -->
        <div class="mb-8">

            <h1 class="text-2xl font-bold text-slate-900">
                My Payments
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                View your rent payments and payment history.
            </p>

        </div>


        <!-- Payment Summary -->
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

            <!-- Total Paid -->
            <div class="rounded-xl border bg-white p-5">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-sm text-slate-500">
                            Total Paid
                        </p>

                        <p class="mt-2 text-2xl font-bold text-slate-900">
                            KSh <?= number_format($totalPaid) ?>
                        </p>

                    </div>

                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-xl">
                        ✓
                    </div>

                </div>

            </div>


            <!-- Pending -->
            <div class="rounded-xl border bg-white p-5">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-sm text-slate-500">
                            Pending
                        </p>

                        <p class="mt-2 text-2xl font-bold text-slate-900">
                            KSh <?= number_format($totalPending) ?>
                        </p>

                    </div>

                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-xl">
                        ⏳
                    </div>

                </div>

            </div>


            <!-- Number of Payments -->
            <div class="rounded-xl border bg-white p-5">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-sm text-slate-500">
                            Payments
                        </p>

                        <p class="mt-2 text-2xl font-bold text-slate-900">
                            <?= count($customerPayments) ?>
                        </p>

                    </div>

                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-xl">
                        💳
                    </div>

                </div>

            </div>

        </div>


        <!-- Payment History -->
        <div class="overflow-hidden rounded-xl border bg-white">

            <div class="border-b px-6 py-5">

                <h2 class="text-lg font-semibold text-slate-900">
                    Payment History
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Your previous rent payments.
                </p>

            </div>


            <?php if (!empty($customerPayments)): ?>

                <div class="overflow-x-auto">

                    <table class="w-full text-left text-sm">

                        <thead class="bg-slate-50 text-xs uppercase text-slate-500">

                            <tr>

                                <th class="px-6 py-4">
                                    Payment
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


                        <tbody class="divide-y">

                            <?php foreach ($customerPayments as $payment): ?>

                                <?php
                                $status = strtolower($payment['status'] ?? 'pending');

                                $statusClass = match ($status) {
                                    'paid' => 'bg-emerald-50 text-emerald-700',
                                    'pending' => 'bg-amber-50 text-amber-700',
                                    'failed' => 'bg-red-50 text-red-700',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                                ?>

                                <tr class="hover:bg-slate-50">

                                    <!-- Payment ID -->
                                    <td class="px-6 py-4 font-medium text-slate-900">
                                        <?= htmlspecialchars($payment['id'] ?? 'N/A') ?>
                                    </td>


                                    <!-- Date -->
                                    <td class="px-6 py-4 text-slate-600">
                                        <?= htmlspecialchars($payment['date'] ?? 'N/A') ?>
                                    </td>


                                    <!-- Amount -->
                                    <td class="px-6 py-4 font-semibold text-slate-900">

                                        KSh
                                        <?= number_format((float) ($payment['amount'] ?? 0)) ?>

                                    </td>


                                    <!-- Method -->
                                    <td class="px-6 py-4 text-slate-600">
                                        <?= htmlspecialchars($payment['method'] ?? 'N/A') ?>
                                    </td>


                                    <!-- Status -->
                                    <td class="px-6 py-4">

                                        <span
                                            class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= $statusClass ?>">

                                            <?= htmlspecialchars(
                                                ucfirst($payment['status'] ?? 'Pending')
                                            ) ?>

                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <!-- No Payments -->
                <div class="p-10 text-center">

                    <div
                        class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-3xl">
                        💳
                    </div>

                    <h3 class="mt-4 text-lg font-semibold text-slate-900">
                        No Payments Found
                    </h3>

                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                        You do not have any payment records yet.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>

</main>


<?php require_once __DIR__ . "/../../includes/footer.php"; ?>