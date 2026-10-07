<?php

require_once "../../includes/admin.php";
require_admin();

require_once "../../includes/api.php";

$pageTitle = "Payments";

$statusFilter = trim($_GET['status'] ?? '');
$tenantFilter = trim($_GET['tenant'] ?? '');

/*
|--------------------------------------------------------------------------
| Local display helpers
|--------------------------------------------------------------------------
| We intentionally do not load data.php here because it can trigger
| additional API requests. These helpers replace only what this page needs.
|--------------------------------------------------------------------------
*/

if (!function_exists('payments_escape')) {
    function payments_escape($value): string
    {
        return htmlspecialchars(
            (string) ($value ?? ''),
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

if (!function_exists('payments_money')) {
    function payments_money($amount): string
    {
        return 'KES ' . number_format(
            (float) ($amount ?? 0),
            2
        );
    }
}

/*
|--------------------------------------------------------------------------
| Load payments
|--------------------------------------------------------------------------
*/

$paymentsResponse = api_get('/payments');

$payments = [];

if (
    !empty($paymentsResponse['success']) &&
    isset($paymentsResponse['data']) &&
    is_array($paymentsResponse['data'])
) {
    $payments = $paymentsResponse['data'];
}

/*
|--------------------------------------------------------------------------
| Filter payments
|--------------------------------------------------------------------------
*/

$filteredPayments = array_filter(
    $payments,
    function (array $payment) use ($statusFilter, $tenantFilter): bool {

        /*
         * Status filter
         */
        if (
            $statusFilter !== '' &&
            ($payment['status'] ?? '') !== $statusFilter
        ) {
            return false;
        }

        /*
         * Tenant filter
         */
        if ($tenantFilter !== '') {

            $tenant = $payment['tenantId'] ?? [];

            $tenantMongoId = '';

            if (is_array($tenant)) {
                $tenantMongoId =
                    $tenant['_id']
                    ?? $tenant['tenantId']
                    ?? '';
            }

            $customerId = $payment['customerId'] ?? '';
            $submittedBy = $payment['submittedBy'] ?? '';

            if (
                $tenantMongoId !== $tenantFilter &&
                $customerId !== $tenantFilter &&
                $submittedBy !== $tenantFilter
            ) {
                return false;
            }
        }

        return true;
    }
);

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
?>

<main class="min-h-screen bg-slate-50 lg:ml-64">

    <header class="border-b border-slate-200 bg-white">

        <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">

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


        <?php if (
            empty($payments) &&
            empty($paymentsResponse['success'])
        ): ?>

            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                Unable to load payment records.
            </div>

        <?php endif; ?>


        <!-- Filters -->

        <div class="mb-6 flex flex-wrap gap-2">

            <a
                href="payments.php"
                class="rounded-lg px-4 py-2 text-sm font-medium
                <?= $statusFilter === ''
                    ? 'bg-indigo-600 text-white'
                    : 'border border-slate-200 bg-white text-slate-600' ?>">
                All
            </a>

            <a
                href="payments.php?status=Paid"
                class="rounded-lg px-4 py-2 text-sm font-medium
                <?= $statusFilter === 'Paid'
                    ? 'bg-green-600 text-white'
                    : 'border border-slate-200 bg-white text-slate-600' ?>">
                Paid
            </a>

            <a
                href="payments.php?status=Pending"
                class="rounded-lg px-4 py-2 text-sm font-medium
                <?= $statusFilter === 'Pending'
                    ? 'bg-amber-500 text-white'
                    : 'border border-slate-200 bg-white text-slate-600' ?>">
                Pending
            </a>

        </div>


        <!-- Payments Table -->

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="overflow-x-auto">

                <table class="w-full text-left text-sm">

                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">

                        <tr>

                            <th class="px-6 py-4">
                                Reference
                            </th>

                            <th class="px-6 py-4">
                                Tenant
                            </th>

                            <th class="px-6 py-4">
                                Lease
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

                            <th class="px-6 py-4">
                                Date
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        <?php if (empty($filteredPayments)): ?>

                            <tr>

                                <td
                                    colspan="7"
                                    class="px-6 py-10 text-center text-sm text-slate-500">
                                    No payment records found.
                                </td>

                            </tr>

                        <?php endif; ?>


                        <?php foreach ($filteredPayments as $payment): ?>

                            <?php

                            /*
                             * Tenant
                             */
                            $tenant = $payment['tenantId'] ?? [];

                            $tenantName = is_array($tenant)
                                ? ($tenant['name'] ?? 'N/A')
                                : 'N/A';

                            $tenantEmail = is_array($tenant)
                                ? ($tenant['email'] ?? '')
                                : '';

                            /*
                             * Lease
                             */
                            $lease = $payment['leaseId'] ?? [];

                            $leaseReference = is_array($lease)
                                ? ($lease['leaseId'] ?? 'N/A')
                                : 'N/A';

                            /*
                             * Payment reference
                             */
                            $paymentReference =
                                $payment['paymentId']
                                ?? $payment['_id']
                                ?? $payment['id']
                                ?? '-';

                            /*
                             * Amount
                             */
                            $amount = (float) (
                                $payment['amount'] ?? 0
                            );

                            /*
                             * Payment method
                             */
                            $paymentMethod =
                                $payment['paymentMethod']
                                ?? '-';

                            /*
                             * Status
                             */
                            $status =
                                $payment['status']
                                ?? 'Pending';

                            /*
                             * Date
                             */
                            $paymentDate =
                                $payment['paymentDate']
                                ?? $payment['createdAt']
                                ?? '';

                            $formattedDate = '-';

                            if ($paymentDate !== '') {

                                $timestamp = strtotime(
                                    (string) $paymentDate
                                );

                                if ($timestamp !== false) {

                                    $formattedDate = date(
                                        'd M Y',
                                        $timestamp
                                    );

                                } else {

                                    $formattedDate =
                                        (string) $paymentDate;
                                }
                            }

                            /*
                             * Status styling
                             */
                            $statusClass = match ($status) {

                                'Paid',
                                'Completed'
                                    => 'bg-green-100 text-green-700',

                                'Failed',
                                'Cancelled',
                                'Rejected'
                                    => 'bg-red-100 text-red-700',

                                default
                                    => 'bg-amber-100 text-amber-700',
                            };

                            ?>


                            <tr class="hover:bg-slate-50">


                                <!-- Reference -->

                                <td class="px-6 py-4 font-medium text-slate-900">

                                    <?= payments_escape(
                                        $paymentReference
                                    ) ?>

                                </td>


                                <!-- Tenant -->

                                <td class="px-6 py-4 text-slate-600">

                                    <div class="font-medium text-slate-900">
                                        <?= payments_escape(
                                            $tenantName
                                        ) ?>
                                    </div>

                                    <?php if ($tenantEmail !== ''): ?>

                                        <div class="mt-1 text-xs text-slate-400">
                                            <?= payments_escape(
                                                $tenantEmail
                                            ) ?>
                                        </div>

                                    <?php endif; ?>

                                </td>


                                <!-- Lease -->

                                <td class="px-6 py-4 text-slate-600">

                                    <?= payments_escape(
                                        $leaseReference
                                    ) ?>

                                </td>


                                <!-- Amount -->

                                <td class="px-6 py-4 font-semibold text-slate-900">

                                    <?= payments_money($amount) ?>

                                </td>


                                <!-- Method -->

                                <td class="px-6 py-4 text-slate-600">

                                    <?= payments_escape(
                                        $paymentMethod
                                    ) ?>

                                </td>


                                <!-- Status -->

                                <td class="px-6 py-4">

                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-medium <?= $statusClass ?>">

                                        <?= payments_escape(
                                            $status
                                        ) ?>

                                    </span>

                                </td>


                                <!-- Date -->

                                <td class="px-6 py-4 text-slate-500">

                                    <?= payments_escape(
                                        $formattedDate
                                    ) ?>

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