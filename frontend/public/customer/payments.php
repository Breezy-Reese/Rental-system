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
| Customer Unit Information
|--------------------------------------------------------------------------
|
| Try to obtain the customer's assigned unit from the tenant/lease data.
| The API may return unitId either as an object or as a plain ID.
|
*/

$customerUnitId = '';
$customerUnitNumber = '';
$customerPropertyName = '';
$customerRent = 0;

/*
 * Check tenant unit.
 */
if (!empty($customerTenant)) {

    if (
        isset($customerTenant['unitId']) &&
        is_array($customerTenant['unitId'])
    ) {

        $customerUnitId =
            $customerTenant['unitId']['_id']
            ?? $customerTenant['unitId']['id']
            ?? '';

        $customerUnitNumber =
            $customerTenant['unitId']['unitNumber']
            ?? $customerTenant['unitId']['unit']
            ?? '';

    } else {

        $customerUnitId =
            $customerTenant['unitId']
            ?? $customerTenant['unit_id']
            ?? '';

        $customerUnitNumber =
            $customerTenant['unitNumber']
            ?? $customerTenant['unit']
            ?? '';
    }

    /*
     * Property from tenant.
     */
    if (
        isset($customerTenant['propertyId']) &&
        is_array($customerTenant['propertyId'])
    ) {

        $customerPropertyName =
            $customerTenant['propertyId']['name']
            ?? '';

    } else {

        $customerPropertyName =
            $customerTenant['property']
            ?? $customerTenant['propertyName']
            ?? '';
    }
}

/*
|--------------------------------------------------------------------------
| Check current lease for unit information
|--------------------------------------------------------------------------
*/

if (!empty($currentLease)) {

    /*
     * Unit.
     */
    if (
        isset($currentLease['unitId']) &&
        is_array($currentLease['unitId'])
    ) {

        if ($customerUnitId === '') {
            $customerUnitId =
                $currentLease['unitId']['_id']
                ?? $currentLease['unitId']['id']
                ?? '';
        }

        if ($customerUnitNumber === '') {
            $customerUnitNumber =
                $currentLease['unitId']['unitNumber']
                ?? $currentLease['unitId']['unit']
                ?? '';
        }

    } else {

        if ($customerUnitId === '') {
            $customerUnitId =
                $currentLease['unitId']
                ?? $currentLease['unit_id']
                ?? '';
        }

        if ($customerUnitNumber === '') {
            $customerUnitNumber =
                $currentLease['unitNumber']
                ?? $currentLease['unit']
                ?? '';
        }
    }

    /*
     * Property.
     */
    if ($customerPropertyName === '') {

        if (
            isset($currentLease['propertyId']) &&
            is_array($currentLease['propertyId'])
        ) {

            $customerPropertyName =
                $currentLease['propertyId']['name']
                ?? '';

        } else {

            $customerPropertyName =
                $currentLease['property']
                ?? $currentLease['propertyName']
                ?? '';
        }
    }

    /*
     * Rent.
     */
    $customerRent =
        (float) (
            $currentLease['rent']
            ?? $currentLease['monthlyRent']
            ?? 0
        );
}

/*
|--------------------------------------------------------------------------
| Submit Customer Payment
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'submit_payment') {

        /*
         * Payment ID is now generated automatically.
         */
        $paymentId =
            'PAY-' .
            date('YmdHis') .
            '-' .
            random_int(100, 999);

        $amount =
            (float) (
                $_POST['amount']
                ?? 0
            );

        $paymentMethod =
            trim(
                $_POST['paymentMethod']
                ?? ''
            );

        $reference =
            trim(
                $_POST['reference']
                ?? ''
            );

        $paymentDate =
            trim(
                $_POST['paymentDate']
                ?? ''
            );

        $unitId =
            trim(
                $_POST['unitId']
                ?? ''
            );

        $unitNumber =
            trim(
                $_POST['unitNumber']
                ?? ''
            );

        $mpesaNumber =
            trim(
                $_POST['mpesaNumber']
                ?? ''
            );

        /*
         * Validation.
         */

        if ($unitId === '' && $unitNumber === '') {

            $errorMessage =
                'Your assigned unit could not be identified. Please contact the administrator.';

        } elseif ($amount <= 0) {

            $errorMessage =
                'Payment amount must be greater than zero.';

        } elseif ($paymentMethod === '') {

            $errorMessage =
                'Please select a payment method.';

        } elseif (
            $paymentMethod === 'M-Pesa' &&
            $mpesaNumber === ''
        ) {

            $errorMessage =
                'Please enter your M-Pesa number.';

        } elseif (
            $paymentMethod === 'M-Pesa' &&
            !preg_match('/^(?:254|\+254|0)?7\d{8}$/', $mpesaNumber)
        ) {

            $errorMessage =
                'Please enter a valid Kenyan M-Pesa number.';

        } elseif (
            $paymentMethod === 'M-Pesa' &&
            $reference === ''
        ) {

            $errorMessage =
                'Please enter the M-Pesa transaction code.';

        } else {

            /*
             * Normalize M-Pesa number.
             */
            if ($paymentMethod === 'M-Pesa') {

                $mpesaNumber =
                    preg_replace(
                        '/\s+/',
                        '',
                        $mpesaNumber
                    );

                if (str_starts_with($mpesaNumber, '+254')) {

                    $mpesaNumber =
                        '254' .
                        substr(
                            $mpesaNumber,
                            4
                        );

                } elseif (
                    str_starts_with($mpesaNumber, '07')
                ) {

                    $mpesaNumber =
                        '254' .
                        substr(
                            $mpesaNumber,
                            1
                        );

                }
            }

            /*
             * Payment payload.
             */
            $payload = [

                /*
                 * Automatically generated.
                 */
                'paymentId' =>
                    $paymentId,

                /*
                 * Payment information.
                 */
                'amount' =>
                    $amount,

                'paymentMethod' =>
                    $paymentMethod,

                'reference' =>
                    $reference,

                'paymentDate' =>
                    $paymentDate !== ''
                        ? $paymentDate
                        : date('Y-m-d'),

                /*
                 * Customer unit.
                 */
                'unitId' =>
                    $unitId,

                'unitNumber' =>
                    $unitNumber,

                /*
                 * M-Pesa phone number.
                 */
                'mpesaNumber' =>
                    $paymentMethod === 'M-Pesa'
                        ? $mpesaNumber
                        : '',

            ];

            /*
             * Customer payment endpoint.
             */
            $result =
                api_post(
                    '/customer/payments',
                    $payload
                );

            if (!empty($result['success'])) {

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

$customerPayments =
    $payments ?? [];

/*
|--------------------------------------------------------------------------
| Totals
|--------------------------------------------------------------------------
*/

$totalPaid = 0;
$totalPending = 0;

foreach ($customerPayments as $payment) {

    $paymentAmount =
        (float) (
            $payment['amount']
            ?? 0
        );

    $status =
        strtolower(
            $payment['status']
            ?? ''
        );

    if ($status === 'paid') {

        $totalPaid +=
            $paymentAmount;
    }

    if ($status === 'pending') {

        $totalPending +=
            $paymentAmount;
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

            <?= htmlspecialchars(
                $successMessage,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </div>

    <?php endif; ?>

    <?php if ($errorMessage): ?>

        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">

            <?= htmlspecialchars(
                $errorMessage,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

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
                            Unit
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
                                colspan="6"
                                class="px-6 py-12 text-center text-sm text-slate-500"
                            >
                                No payments found.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($customerPayments as $payment): ?>

                            <?php

                            $status =
                                $payment['status']
                                ?? 'Pending';

                            $statusClass =
                                match ($status) {

                                    'Paid' =>
                                        'bg-emerald-100 text-emerald-700',

                                    'Failed' =>
                                        'bg-red-100 text-red-700',

                                    'Cancelled' =>
                                        'bg-slate-100 text-slate-700',

                                    default =>
                                        'bg-amber-100 text-amber-700',
                                };

                            $historyUnit = '';

                            if (
                                isset($payment['unitId']) &&
                                is_array($payment['unitId'])
                            ) {

                                $historyUnit =
                                    $payment['unitId']['unitNumber']
                                    ?? '';
                            }

                            if ($historyUnit === '') {

                                $historyUnit =
                                    $payment['unitNumber']
                                    ?? $payment['unit']
                                    ?? '-';
                            }

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

                                    <?= htmlspecialchars(
                                        $historyUnit,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">

                                    KSh <?= number_format(
                                        (float) (
                                            $payment['amount']
                                            ?? 0
                                        )
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

<!-- ============================================================
     Payment Modal
============================================================ -->

<div
    id="paymentModal"
    class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/50 px-4 py-10"
>

```
<div class="mx-auto max-w-lg rounded-2xl bg-white shadow-xl">

    <!-- Modal Header -->

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


    <!-- Payment Form -->

    <form
        method="POST"
        action="payments.php"
        class="space-y-5 p-6"
        id="paymentForm"
    >

        <input
            type="hidden"
            name="action"
            value="submit_payment"
        >

        <!-- Automatically generated Payment ID -->

        <input
            type="hidden"
            name="paymentId"
            value=""
        >


        <!-- =================================================
             Customer Unit
        ================================================== -->

        <div>

            <label class="mb-2 block text-sm font-medium text-slate-700">
                Your Unit
            </label>

            <?php if ($customerUnitId !== '' || $customerUnitNumber !== ''): ?>

                <div class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-xs font-medium uppercase tracking-wide text-indigo-500">
                                Assigned Unit
                            </p>

                            <p class="mt-1 font-semibold text-slate-900">

                                <?= htmlspecialchars(
                                    $customerUnitNumber !== ''
                                        ? $customerUnitNumber
                                        : $customerUnitId,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </p>

                            <?php if ($customerPropertyName !== ''): ?>

                                <p class="mt-1 text-xs text-slate-500">

                                    <?= htmlspecialchars(
                                        $customerPropertyName,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </p>

                            <?php endif; ?>

                        </div>

                        <div class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-indigo-600">
                            My Unit
                        </div>

                    </div>

                </div>

                <input
                    type="hidden"
                    name="unitId"
                    value="<?= htmlspecialchars(
                        $customerUnitId,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <input
                    type="hidden"
                    name="unitNumber"
                    value="<?= htmlspecialchars(
                        $customerUnitNumber,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

            <?php else: ?>

                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">

                    Your assigned unit could not be found.
                    Please contact the administrator before submitting a payment.

                </div>

                <input
                    type="hidden"
                    name="unitId"
                    value=""
                >

                <input
                    type="hidden"
                    name="unitNumber"
                    value=""
                >

            <?php endif; ?>

        </div>


        <!-- =================================================
             Amount
        ================================================== -->

        <div>

            <label
                for="amount"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Amount
            </label>

            <div class="relative">

                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-medium text-slate-500">
                    KSh
                </span>

                <input
                    type="number"
                    id="amount"
                    name="amount"
                    min="1"
                    step="0.01"
                    placeholder="25000"
                    value="<?= $customerRent > 0
                        ? htmlspecialchars(
                            (string) $customerRent,
                            ENT_QUOTES,
                            'UTF-8'
                        )
                        : '' ?>"
                    required
                    class="w-full rounded-lg border border-slate-300 py-3 pl-14 pr-4 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                >

            </div>

            <?php if ($customerRent > 0): ?>

                <p class="mt-1 text-xs text-slate-500">
                    Current monthly rent: KSh
                    <?= number_format($customerRent) ?>
                </p>

            <?php endif; ?>

        </div>


        <!-- =================================================
             Payment Method
        ================================================== -->

        <div>

            <label
                for="paymentMethod"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Payment Method
            </label>

            <select
                id="paymentMethod"
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


        <!-- =================================================
             M-Pesa Details
        ================================================== -->

        <div
            id="mpesaFields"
            class="hidden space-y-5 rounded-xl border border-emerald-200 bg-emerald-50/50 p-4"
        >

            <div>

                <p class="text-sm font-semibold text-slate-900">
                    M-Pesa Payment Details
                </p>

                <p class="mt-1 text-xs text-slate-500">
                    Enter the phone number used to make this M-Pesa payment.
                </p>

            </div>


            <!-- M-Pesa Number -->

            <div>

                <label
                    for="mpesaNumber"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    M-Pesa Number
                </label>

                <input
                    type="tel"
                    id="mpesaNumber"
                    name="mpesaNumber"
                    inputmode="numeric"
                    autocomplete="tel"
                    placeholder="0712345678"
                    maxlength="13"
                    class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                >

                <p class="mt-1 text-xs text-slate-500">
                    Example: 0712345678
                </p>

            </div>


            <!-- Transaction Code -->

            <div>

                <label
                    for="reference"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    M-Pesa Transaction Code
                </label>

                <input
                    type="text"
                    id="reference"
                    name="reference"
                    placeholder="Enter M-Pesa transaction code"
                    class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm uppercase focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                >

            </div>

        </div>


        <!-- =================================================
             Reference for non-M-Pesa payments
        ================================================== -->

        <div
            id="otherReferenceField"
            class="hidden"
        >

            <label
                for="otherReference"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Reference
            </label>

            <input
                type="text"
                id="otherReference"
                placeholder="Payment reference"
                class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
            >

        </div>


        <!-- =================================================
             Payment Date
        ================================================== -->

        <div>

            <label
                for="paymentDate"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Payment Date
            </label>

            <input
                type="date"
                id="paymentDate"
                name="paymentDate"
                value="<?= date('Y-m-d') ?>"
                required
                class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
            >

        </div>


        <!-- =================================================
             Buttons
        ================================================== -->

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
```

</div>

<!-- ============================================================
     Payment Form JavaScript
============================================================ -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    const paymentMethod =
        document.getElementById('paymentMethod');

    const mpesaFields =
        document.getElementById('mpesaFields');

    const mpesaNumber =
        document.getElementById('mpesaNumber');

    const reference =
        document.getElementById('reference');

    const otherReferenceField =
        document.getElementById('otherReferenceField');

    const otherReference =
        document.getElementById('otherReference');


    function updatePaymentMethod() {

        const method =
            paymentMethod.value;


        /*
         * M-Pesa selected.
         */

        if (method === 'M-Pesa') {

            mpesaFields.classList.remove('hidden');

            otherReferenceField.classList.add('hidden');

            mpesaNumber.required = true;

            reference.required = true;

            otherReference.required = false;

        }


        /*
         * Other payment methods.
         */

        else {

            mpesaFields.classList.add('hidden');

            mpesaNumber.required = false;

            reference.required = false;

            if (method !== '') {

                otherReferenceField.classList.remove('hidden');

            } else {

                otherReferenceField.classList.add('hidden');

            }

            otherReference.required = false;
        }
    }


    paymentMethod.addEventListener(
        'change',
        updatePaymentMethod
    );


    /*
     * Initial state.
     */

    updatePaymentMethod();


    /*
     * Prevent letters in the M-Pesa number.
     */

    mpesaNumber.addEventListener(
        'input',
        function () {

            this.value =
                this.value.replace(
                    /[^0-9+]/g,
                    ''
                );
        }
    );


    /*
     * Uppercase transaction code.
     */

    reference.addEventListener(
        'input',
        function () {

            this.value =
                this.value.toUpperCase();
        }
    );


    /*
     * Close modal when clicking outside it.
     */

    const modal =
        document.getElementById('paymentModal');

    modal.addEventListener(
        'click',
        function (event) {

            if (event.target === modal) {

                modal.classList.add('hidden');

            }

        }
    );

});

</script>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
