<?php

$pageTitle = "My Payments";

require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../includes/api.php";

require_login();

if (current_role() !== 'Customer') {
    header("Location: ../admin/dashboard.php");
    exit;
}

require_once __DIR__ . "/../../includes/data.php";

$user = current_user();

$customerName = $user['name'] ?? 'Customer';

$successMessage = '';
$errorMessage = '';

/*
|--------------------------------------------------------------------------
| Submit Customer Payment
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'submit_payment') {

        $paymentId = trim($_POST['paymentId'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);
        $paymentMethod = trim($_POST['paymentMethod'] ?? '');
        $reference = trim($_POST['reference'] ?? '');
        $paymentDate = trim($_POST['paymentDate'] ?? '');

        if ($paymentId === '') {

            $errorMessage = 'Please enter a payment ID.';

        } elseif ($amount <= 0) {

            $errorMessage = 'Payment amount must be greater than zero.';

        } elseif ($paymentMethod === '') {

            $errorMessage = 'Please select a payment method.';

        } else {

            $payload = [
                'paymentId' => $paymentId,
                'amount' => $amount,
                'paymentMethod' => $paymentMethod,
                'reference' => $reference,
                'paymentDate' => $paymentDate !== ''
                    ? $paymentDate
                    : date('Y-m-d'),
            ];

            /*
             * IMPORTANT:
             * This is the customer endpoint.
             *
             * DO NOT use /payments here.
             */
            $result = api_post(
                '/customer/payments',
                $payload
            );

            if (
                !empty($result['success'])
            ) {

                $successMessage =
                    $result['message']
                    ?? 'Payment submitted successfully.';

            } else {

                $errorMessage =
                    $result['message']
                    ?? 'Unable to submit payment.';
            }

        }
    }
}

/*
|--------------------------------------------------------------------------
| Reload customer data after submission
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../../includes/data.php";

$customerPayments = $payments ?? [];

/*
|--------------------------------------------------------------------------
| Totals
|--------------------------------------------------------------------------
*/

$totalPaid = 0;
$totalPending = 0;

foreach ($customerPayments as $payment) {

    $amount = (float) ($payment['amount'] ?? 0);

    $status = strtolower(
        $payment['status'] ?? ''
    );

    if ($status === 'paid') {
        $totalPaid += $amount;
    }

    if ($status === 'pending') {
        $totalPending += $amount;
    }
}

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";

?>

<main class="flex-1 lg:ml-64">

    <?php require_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div class="p-4 sm:p-6 lg:p-8">

        <!-- Header -->
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h1 class="text-2xl font-bold text-slate-900">
                    My Payments
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    View and submit your rent payments.
                </p>

            </div>

            <button
                type="button"
                onclick="document.getElementById('paymentModal').classList.remove('hidden')"
                class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"
            >
                + Submit Payment
            </button>

        </div>

        <?php if ($successMessage): ?>

            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
            </div>

        <?php endif; ?>

        <?php if ($errorMessage): ?>

            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
            </div>

        <?php endif; ?>

        <!-- Summary -->
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

            <div class="rounded-xl border bg-white p-5">

                <p class="text-sm text-slate-500">
                    Total Paid
                </p>

                <p class="mt-2 text-2xl font-bold text-slate-900">
                    KSh <?= number_format($totalPaid) ?>
                </p>

            </div>

            <div class="rounded-xl border bg-white p-5">

                <p class="text-sm text-slate-500">
                    Pending
                </p>

                <p class="mt-2 text-2xl font-bold text-amber-600">
                    KSh <?= number_format($totalPending) ?>
                </p>

            </div>

            <div class="rounded-xl border bg-white p-5">

                <p class="text-sm text-slate-500">
                    Payments
                </p>

                <p class="mt-2 text-2xl font-bold text-indigo-600">
                    <?= count($customerPayments) ?>
                </p>

            </div>

        </div>

        <!-- Payment History -->
        <div class="overflow-hidden rounded-xl border bg-white">

            <div class="border-b px-6 py-5">

                <h2 class="text-lg font-semibold text-slate-900">
                    Payment History
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Your submitted rent payments.
                </p>

            </div>

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-50">

                        <tr>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Payment ID
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Amount
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Method
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Date
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Status
                            </th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">

                        <?php if (empty($customerPayments)): ?>

                            <tr>

                                <td
                                    colspan="5"
                                    class="px-6 py-12 text-center text-sm text-slate-500"
                                >
                                    No payments found.
                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($customerPayments as $payment): ?>

                                <?php
                                $status = $payment['status'] ?? 'Pending';

                                $statusClass = match ($status) {
                                    'Paid' => 'bg-emerald-100 text-emerald-700',
                                    'Failed' => 'bg-red-100 text-red-700',
                                    'Cancelled' => 'bg-slate-100 text-slate-700',
                                    default => 'bg-amber-100 text-amber-700',
                                };
                                ?>

                                <tr class="hover:bg-slate-50">

                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">
                                        <?= htmlspecialchars(
                                            $payment['paymentId']
                                            ?? $payment['id']
                                            ?? '-',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                        KSh <?= number_format(
                                            (float) ($payment['amount'] ?? 0)
                                        ) ?>
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                        <?= htmlspecialchars(
                                            $payment['paymentMethod']
                                            ?? $payment['method']
                                            ?? '-',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                        <?= htmlspecialchars(
                                            $payment['paymentDate']
                                            ?? $payment['date']
                                            ?? '-',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4">

                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold <?= $statusClass ?>">
                                            <?= htmlspecialchars(
                                                $status,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</main>

<!-- Payment Modal -->
<div
    id="paymentModal"
    class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/50 px-4 py-10"
>

    <div class="mx-auto max-w-lg rounded-2xl bg-white shadow-xl">

        <div class="flex items-center justify-between border-b px-6 py-5">

            <div>

                <h2 class="text-lg font-semibold text-slate-900">
                    Submit Payment
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Submit your rent payment for verification.
                </p>

            </div>

            <button
                type="button"
                onclick="document.getElementById('paymentModal').classList.add('hidden')"
                class="text-2xl text-slate-400 hover:text-slate-600"
            >
                &times;
            </button>

        </div>

        <form method="POST" action="payments.php" class="space-y-5 p-6">

            <input
                type="hidden"
                name="action"
                value="submit_payment"
            >

            <div>

                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Payment ID
                </label>

                <input
                    type="text"
                    name="paymentId"
                    placeholder="PAY-2026-001"
                    required
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                >

            </div>

            <div>

                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Amount
                </label>

                <input
                    type="number"
                    name="amount"
                    min="1"
                    step="0.01"
                    placeholder="25000"
                    required
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                >

            </div>

            <div>

                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Payment Method
                </label>

                <select
                    name="paymentMethod"
                    required
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                >

                    <option value="">
                        Select method
                    </option>

                    <option value="M-Pesa">
                        M-Pesa
                    </option>

                    <option value="Bank Transfer">
                        Bank Transfer
                    </option>

                    <option value="Cash">
                        Cash
                    </option>

                    <option value="Other">
                        Other
                    </option>

                </select>

            </div>

            <div>

                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Reference
                </label>

                <input
                    type="text"
                    name="reference"
                    placeholder="M-Pesa transaction code"
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                >

            </div>

            <div>

                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Payment Date
                </label>

                <input
                    type="date"
                    name="paymentDate"
                    value="<?= date('Y-m-d') ?>"
                    required
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                >

            </div>

            <div class="flex justify-end gap-3 border-t pt-5">

                <button
                    type="button"
                    onclick="document.getElementById('paymentModal').classList.add('hidden')"
                    class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                >
                    Submit Payment
                </button>

            </div>

        </form>

    </div>

</div>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>