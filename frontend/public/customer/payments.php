<?php

$pageTitle = "My Payments";

require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../includes/api.php";

require_login();

if (current_role() !== "Customer") {
    header("Location: ../admin/dashboard.php");
    exit;
}

require_once __DIR__ . "/../../includes/data.php";

$user = current_user();

$customerName = $user["name"] ?? "Customer";

$successMessage = "";
$errorMessage = "";

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function customer_payment_escape($value): string
{
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

function customer_payment_date($date): string
{
    if (empty($date)) {
        return "-";
    }

    $timestamp = strtotime((string)$date);

    if ($timestamp === false) {
        return (string)$date;
    }

    return date("d M Y", $timestamp);
}

/**
 * Normalize Kenyan mobile numbers into 254XXXXXXXXX format.
 *
 * Accepted examples:
 * 0712345678
 * 0112345678
 * 254712345678
 * 254112345678
 * +254712345678
 * +254 712 345 678
 */
function customer_payment_normalize_phone(string $phone): ?string
{
    $number = preg_replace('/\D/', '', trim($phone));

    if (!is_string($number) || $number === "") {
        return null;
    }

    if (str_starts_with($number, "0")) {
        $number = "254" . substr($number, 1);
    } elseif (
        str_starts_with($number, "7") ||
        str_starts_with($number, "1")
    ) {
        $number = "254" . $number;
    }

    if (!preg_match('/^254[17]\d{8}$/', $number)) {
        return null;
    }

    return $number;
}

/**
 * Safely extract an ID from a plain ID or populated object.
 */
function customer_payment_extract_id($value): string
{
    if (is_array($value)) {
        return (string)(
            $value["_id"]
            ?? $value["id"]
            ?? $value["leaseId"]
            ?? ""
        );
    }

    if (is_string($value) || is_numeric($value)) {
        return trim((string)$value);
    }

    return "";
}

/*
|--------------------------------------------------------------------------
| Customer unit and lease information
|--------------------------------------------------------------------------
*/

$customerUnitId = "";
$customerUnitNumber = "";
$customerPropertyName = "";
$customerLeaseId = "";
$customerRent = 0;

/*
|--------------------------------------------------------------------------
| Tenant information
|--------------------------------------------------------------------------
*/

if (!empty($customerTenant) && is_array($customerTenant)) {

    $tenantUnit = $customerTenant["unitId"] ?? null;

    if (is_array($tenantUnit)) {

        $customerUnitId =
            $tenantUnit["_id"]
            ?? $tenantUnit["id"]
            ?? "";

        $customerUnitNumber =
            $tenantUnit["unitNumber"]
            ?? $tenantUnit["unit"]
            ?? "";

    } else {

        $customerUnitId =
            $customerTenant["unitId"]
            ?? $customerTenant["unit_id"]
            ?? "";

        $customerUnitNumber =
            $customerTenant["unitNumber"]
            ?? $customerTenant["unit"]
            ?? "";
    }

    $tenantProperty = $customerTenant["propertyId"] ?? null;

    if (is_array($tenantProperty)) {

        $customerPropertyName =
            $tenantProperty["name"]
            ?? $tenantProperty["propertyName"]
            ?? "";

    } else {

        $customerPropertyName =
            $customerTenant["property"]
            ?? $customerTenant["propertyName"]
            ?? "";
    }
}

/*
|--------------------------------------------------------------------------
| Current lease fallback
|--------------------------------------------------------------------------
*/

if (!empty($currentLease) && is_array($currentLease)) {

    $customerLeaseId = customer_payment_extract_id(
        $currentLease["_id"]
        ?? $currentLease["id"]
        ?? $currentLease["leaseId"]
        ?? ""
    );

    $leaseUnit = $currentLease["unitId"] ?? null;

    if (is_array($leaseUnit)) {

        if ($customerUnitId === "") {
            $customerUnitId =
                $leaseUnit["_id"]
                ?? $leaseUnit["id"]
                ?? "";
        }

        if ($customerUnitNumber === "") {
            $customerUnitNumber =
                $leaseUnit["unitNumber"]
                ?? $leaseUnit["unit"]
                ?? "";
        }

    } else {

        if ($customerUnitId === "") {
            $customerUnitId =
                $currentLease["unitId"]
                ?? $currentLease["unit_id"]
                ?? "";
        }

        if ($customerUnitNumber === "") {
            $customerUnitNumber =
                $currentLease["unitNumber"]
                ?? $currentLease["unit"]
                ?? "";
        }
    }

    if ($customerPropertyName === "") {

        $leaseProperty = $currentLease["propertyId"] ?? null;

        if (is_array($leaseProperty)) {

            $customerPropertyName =
                $leaseProperty["name"]
                ?? $leaseProperty["propertyName"]
                ?? "";

        } else {

            $customerPropertyName =
                $currentLease["property"]
                ?? $currentLease["propertyName"]
                ?? "";
        }
    }

    $customerRent = (float)(
        $currentLease["rent"]
        ?? $currentLease["monthlyRent"]
        ?? 0
    );
}

/*
|--------------------------------------------------------------------------
| Submit payment
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";

    if ($action === "submit_payment") {

        $amount = filter_var(
            $_POST["amount"] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        $paymentMethod = trim(
            (string)($_POST["paymentMethod"] ?? "")
        );

        $paymentDate = trim(
            (string)($_POST["paymentDate"] ?? "")
        );

        $unitId = trim(
            (string)($_POST["unitId"] ?? "")
        );

        $unitNumber = trim(
            (string)($_POST["unitNumber"] ?? "")
        );

        $mpesaNumber = trim(
            (string)($_POST["mpesaNumber"] ?? "")
        );

        $allowedMethods = [
            "M-Pesa",
            "Bank Transfer",
            "Cash",
            "Other"
        ];

        /*
        | Validate the payment details.
        */

        if ($unitId === "" && $unitNumber === "") {

            $errorMessage =
                "Your assigned unit could not be identified. Please contact the administrator.";

        } elseif (
            $amount === false ||
            $amount <= 0 ||
            !is_finite((float)$amount)
        ) {

            $errorMessage =
                "Enter a valid payment amount greater than zero.";

        } elseif (!in_array($paymentMethod, $allowedMethods, true)) {

            $errorMessage =
                "Please select a valid payment method.";

        } elseif (
            $paymentDate !== "" &&
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $paymentDate)
        ) {

            $errorMessage =
                "Please select a valid payment date.";

        } else {

            /*
            |----------------------------------------------------------
            | M-Pesa STK Push
            |----------------------------------------------------------
            |
            | Do not submit an M-Pesa payment to /customer/payments.
            | That endpoint creates a manual pending payment and would
            | result in duplicate records if STK Push were also called.
            */

            if ($paymentMethod === "M-Pesa") {

                $normalizedPhone =
                    customer_payment_normalize_phone($mpesaNumber);

                if ($mpesaNumber === "") {

                    $errorMessage =
                        "Please enter your M-Pesa phone number.";

                } elseif ($normalizedPhone === null) {

                    $errorMessage =
                        "Enter a valid Kenyan M-Pesa number, such as 0712345678 or +254712345678.";

                } elseif ($customerLeaseId === "") {

                    $errorMessage =
                        "Your active lease could not be identified. Please contact the administrator.";

                } else {

                    $stkPayload = [
                        "phoneNumber" => $normalizedPhone,
                        "amount" => (int)round((float)$amount),
                        "leaseId" => $customerLeaseId
                    ];

                    try {

                        $result = api_post(
                            "/mpesa/stk-push",
                            $stkPayload
                        );

                        if (
                            is_array($result) &&
                            !empty($result["success"])
                        ) {

                            $successMessage =
                                $result["message"]
                                ?? "M-Pesa payment request sent. Check your phone and follow the Safaricom prompt.";

                        } else {

                            $errorMessage =
                                is_array($result)
                                    ? (
                                        $result["message"]
                                        ?? "Unable to initiate M-Pesa payment. Please try again."
                                    )
                                    : "Unable to initiate M-Pesa payment. Please try again.";
                        }

                    } catch (Throwable $exception) {

                        error_log(
                            "PropertyPro M-Pesa request failed: " .
                            $exception->getMessage()
                        );

                        $errorMessage =
                            "Unable to contact the payment service. Please try again. If the problem continues, contact the administrator.";
                    }
                }

            } else {

                /*
                |------------------------------------------------------
                | Manual payment submission
                |------------------------------------------------------
                */

                $paymentId =
                    "PAY-" .
                    date("YmdHis") .
                    "-" .
                    random_int(100, 999);

                $payload = [
                    "paymentId" => $paymentId,
                    "amount" => (float)$amount,
                    "paymentMethod" => $paymentMethod,
                    "paymentDate" => $paymentDate !== ""
                        ? $paymentDate
                        : date("Y-m-d"),
                    "unitId" => $unitId,
                    "unitNumber" => $unitNumber
                ];

                if ($customerLeaseId !== "") {
                    $payload["leaseId"] = $customerLeaseId;
                }

                try {

                    $result = api_post(
                        "/customer/payments",
                        $payload
                    );

                    if (
                        is_array($result) &&
                        !empty($result["success"])
                    ) {

                        $successMessage =
                            $result["message"]
                            ?? "Payment submitted successfully.";

                    } else {

                        $errorMessage =
                            is_array($result)
                                ? (
                                    $result["message"]
                                    ?? "Unable to submit payment."
                                )
                                : "Unable to submit payment.";
                    }

                } catch (Throwable $exception) {

                    error_log(
                        "PropertyPro manual payment submission failed: " .
                        $exception->getMessage()
                    );

                    $errorMessage =
                        "Unable to submit your payment. Please try again.";
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Customer payment history
|--------------------------------------------------------------------------
*/

$customerPayments = isset($payments) && is_array($payments)
    ? $payments
    : [];

/*
|--------------------------------------------------------------------------
| Payment totals
|--------------------------------------------------------------------------
*/

$totalPaid = 0;
$totalPending = 0;

foreach ($customerPayments as $payment) {

    $paymentAmount = (float)(
        $payment["amount"] ?? 0
    );

    $paymentStatus = strtolower(
        (string)($payment["status"] ?? "")
    );

    if ($paymentStatus === "paid") {
        $totalPaid += $paymentAmount;
    }

    if ($paymentStatus === "pending") {
        $totalPending += $paymentAmount;
    }
}

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";

?>

<div class="lg:pl-64">

    <main class="min-h-screen p-4 sm:p-6 lg:p-8">

        <!-- Page header -->

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
                class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
            >
                + Submit Payment
            </button>

        </div>

        <!-- Feedback -->

        <?php if ($successMessage !== ""): ?>

            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                <?= customer_payment_escape($successMessage) ?>
            </div>

        <?php endif; ?>

        <?php if ($errorMessage !== ""): ?>

            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <?= customer_payment_escape($errorMessage) ?>
            </div>

        <?php endif; ?>

        <!-- Summary cards -->

        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Total Paid
                </p>

                <p class="mt-2 text-2xl font-bold text-slate-900">
                    KSh <?= number_format($totalPaid, 2) ?>
                </p>

            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Pending
                </p>

                <p class="mt-2 text-2xl font-bold text-amber-600">
                    KSh <?= number_format($totalPending, 2) ?>
                </p>

            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Payments
                </p>

                <p class="mt-2 text-2xl font-bold text-indigo-600">
                    <?= count($customerPayments) ?>
                </p>

            </div>

        </div>

        <!-- Payment history -->

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-5">

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

                                $paymentStatus = $payment["status"] ?? "Pending";

                                $statusLower = strtolower(
                                    (string)$paymentStatus
                                );

                                $statusClass = match ($statusLower) {

                                    "paid" =>
                                        "bg-emerald-100 text-emerald-700",

                                    "failed" =>
                                        "bg-red-100 text-red-700",

                                    "cancelled" =>
                                        "bg-slate-100 text-slate-700",

                                    default =>
                                        "bg-amber-100 text-amber-700",
                                };

                                $historyUnit = "";

                                if (
                                    isset($payment["unitId"]) &&
                                    is_array($payment["unitId"])
                                ) {
                                    $historyUnit =
                                        $payment["unitId"]["unitNumber"]
                                        ?? $payment["unitId"]["unit"]
                                        ?? "";
                                }

                                if ($historyUnit === "") {
                                    $historyUnit =
                                        $payment["unitNumber"]
                                        ?? $payment["unit"]
                                        ?? "-";
                                }

                                $historyPaymentId =
                                    $payment["paymentId"]
                                    ?? $payment["id"]
                                    ?? "-";

                                $historyMethod =
                                    $payment["paymentMethod"]
                                    ?? $payment["method"]
                                    ?? "-";

                                ?>

                                <tr class="hover:bg-slate-50">

                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">
                                        <?= customer_payment_escape($historyPaymentId) ?>
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                        <?= customer_payment_escape($historyUnit) ?>
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-slate-700">
                                        KSh <?= number_format(
                                            (float)($payment["amount"] ?? 0),
                                            2
                                        ) ?>
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                        <?= customer_payment_escape($historyMethod) ?>
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                        <?= customer_payment_escape(
                                            customer_payment_date(
                                                $payment["paymentDate"]
                                                ?? $payment["date"]
                                                ?? ""
                                            )
                                        ) ?>
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4">

                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold <?= $statusClass ?>">
                                            <?= customer_payment_escape($paymentStatus) ?>
                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

<!-- Payment modal -->

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
                    Submit your rent payment for processing.
                </p>

            </div>

            <button
                type="button"
                onclick="document.getElementById('paymentModal').classList.add('hidden')"
                aria-label="Close payment form"
                class="text-2xl text-slate-400 hover:text-slate-600"
            >
                &times;
            </button>

        </div>

        <form
            id="paymentForm"
            method="POST"
            action="payments.php"
            class="space-y-5 p-6"
        >

            <input
                type="hidden"
                name="action"
                value="submit_payment"
            >

            <!-- Assigned unit -->

            <div>

                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Your Unit
                </label>

                <?php if (
                    $customerUnitId !== "" ||
                    $customerUnitNumber !== ""
                ): ?>

                    <div class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3">

                        <div class="flex items-center justify-between">

                            <div>

                                <p class="text-xs font-medium uppercase tracking-wide text-indigo-500">
                                    Assigned Unit
                                </p>

                                <p class="mt-1 font-semibold text-slate-900">
                                    <?= customer_payment_escape(
                                        $customerUnitNumber !== ""
                                            ? $customerUnitNumber
                                            : $customerUnitId
                                    ) ?>
                                </p>

                                <?php if ($customerPropertyName !== ""): ?>

                                    <p class="mt-1 text-xs text-slate-500">
                                        <?= customer_payment_escape($customerPropertyName) ?>
                                    </p>

                                <?php endif; ?>

                            </div>

                            <div class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-indigo-600">
                                My Unit
                            </div>

                        </div>

                    </div>

                <?php else: ?>

                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        Your assigned unit could not be found. Please contact the administrator.
                    </div>

                <?php endif; ?>

                <input
                    type="hidden"
                    name="unitId"
                    value="<?= customer_payment_escape($customerUnitId) ?>"
                >

                <input
                    type="hidden"
                    name="unitNumber"
                    value="<?= customer_payment_escape($customerUnitNumber) ?>"
                >

            </div>

            <!-- Amount -->

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
                        step="1"
                        placeholder="25000"
                        value="<?= $customerRent > 0
                            ? customer_payment_escape((int)round($customerRent))
                            : "" ?>"
                        required
                        class="w-full rounded-lg border border-slate-300 py-3 pl-14 pr-4 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                    >

                </div>

                <?php if ($customerRent > 0): ?>

                    <p class="mt-1 text-xs text-slate-500">
                        Current monthly rent: KSh <?= number_format($customerRent, 2) ?>
                    </p>

                <?php endif; ?>

            </div>

            <!-- Payment method -->

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

                    <option value="">Select method</option>

                    <option value="M-Pesa">M-Pesa</option>

                    <option value="Bank Transfer">Bank Transfer</option>

                    <option value="Cash">Cash</option>

                    <option value="Other">Other</option>

                </select>

            </div>

            <!-- M-Pesa fields -->

            <div
                id="mpesaFields"
                class="hidden rounded-xl border border-emerald-200 bg-emerald-50/50 p-4"
            >

                <p class="text-sm font-semibold text-slate-900">
                    M-Pesa STK Push
                </p>

                <p class="mt-1 text-xs leading-5 text-slate-600">
                    Enter your M-Pesa phone number. If the payment service is configured correctly, a payment prompt will be requested on that phone.
                </p>

                <label
                    for="mpesaNumber"
                    class="mb-2 mt-4 block text-sm font-medium text-slate-700"
                >
                    M-Pesa Phone Number
                </label>

                <input
                    type="tel"
                    id="mpesaNumber"
                    name="mpesaNumber"
                    inputmode="tel"
                    autocomplete="tel"
                    placeholder="0712345678"
                    maxlength="20"
                    class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                >

                <p class="mt-1 text-xs text-slate-500">
                    Accepted formats: 0712345678 or +254712345678.
                </p>

            </div>

            <!-- Payment date -->

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
                    value="<?= date("Y-m-d") ?>"
                    required
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                >

            </div>

            <!-- Actions -->

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
                    id="submitPaymentButton"
                    <?= ($customerUnitId === "" && $customerUnitNumber === "") ? "disabled" : "" ?>
                    class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Submit Payment
                </button>

            </div>

        </form>

    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const paymentMethod = document.getElementById("paymentMethod");
    const mpesaFields = document.getElementById("mpesaFields");
    const mpesaNumber = document.getElementById("mpesaNumber");
    const paymentForm = document.getElementById("paymentForm");
    const submitButton = document.getElementById("submitPaymentButton");
    const modal = document.getElementById("paymentModal");

    function updatePaymentMethod() {

        const isMpesa = paymentMethod.value === "M-Pesa";

        mpesaFields.classList.toggle("hidden", !isMpesa);
        mpesaNumber.required = isMpesa;

        if (!isMpesa) {
            mpesaNumber.setCustomValidity("");
        }
    }

    paymentMethod.addEventListener("change", updatePaymentMethod);

    updatePaymentMethod();

    /*
     * Accept digits, spaces, hyphens and a leading plus.
     * Server-side normalization remains authoritative.
     */
    mpesaNumber.addEventListener("input", function () {

        let value = this.value.replace(/[^\d+\s-]/g, "");

        value = value.replace(/(?!^)\+/g, "");

        this.value = value;
        this.setCustomValidity("");
    });

    paymentForm.addEventListener("submit", function (event) {

        if (paymentMethod.value === "M-Pesa") {

            const digits = mpesaNumber.value.replace(/\D/g, "");

            let normalized = digits;

            if (normalized.startsWith("0")) {
                normalized = "254" + normalized.substring(1);
            } else if (
                normalized.startsWith("7") ||
                normalized.startsWith("1")
            ) {
                normalized = "254" + normalized;
            }

            if (!/^254[17]\d{8}$/.test(normalized)) {

                event.preventDefault();

                mpesaNumber.setCustomValidity(
                    "Enter a valid Kenyan number, such as 0712345678 or +254712345678."
                );

                mpesaNumber.reportValidity();

                return;
            }
        }

        /*
         * Prevent accidental double submissions.
         * Re-enable the button when the browser returns to this page.
         */
        submitButton.disabled = true;
        submitButton.textContent = "Submitting...";
    });

    window.addEventListener("pageshow", function () {
        submitButton.disabled = false;
        submitButton.textContent = "Submit Payment";
    });

    modal.addEventListener("click", function (event) {

        if (event.target === modal) {
            modal.classList.add("hidden");
        }
    });

});
</script>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>