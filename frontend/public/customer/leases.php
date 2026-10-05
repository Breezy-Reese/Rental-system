<?php

$pageTitle = "My Lease";

require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../includes/data.php";

require_login();

if (current_role() !== "Customer") {
    header("Location: ../admin/dashboard.php");
    exit;
}

$user = current_user();

$customerName = $user["name"] ?? "Customer";

$customerLease = is_array($currentLease ?? null)
    ? $currentLease
    : [];

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function lease_escape($value): string
{
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

function format_lease_date($date): string
{
    if (empty($date)) {
        return "Not available";
    }

    $timestamp = strtotime((string)$date);

    if ($timestamp === false) {
        return lease_escape($date);
    }

    return date("d M Y", $timestamp);
}

/*
|--------------------------------------------------------------------------
| Property
|--------------------------------------------------------------------------
*/

$propertyName = "";

if (
    isset($customerLease["propertyId"]) &&
    is_array($customerLease["propertyId"])
) {
    $propertyName =
        $customerLease["propertyId"]["name"]
        ?? $customerLease["propertyId"]["propertyName"]
        ?? "";
}

if ($propertyName === "") {
    $propertyName =
        $customerLease["property"]
        ?? $customerLease["propertyName"]
        ?? "Not available";
}

/*
|--------------------------------------------------------------------------
| Unit
|--------------------------------------------------------------------------
*/

$unitNumber = "";

if (
    isset($customerLease["unitId"]) &&
    is_array($customerLease["unitId"])
) {
    $unitNumber =
        $customerLease["unitId"]["unitNumber"]
        ?? $customerLease["unitId"]["unit"]
        ?? $customerLease["unitId"]["unitId"]
        ?? "";
}

if ($unitNumber === "") {
    $unitNumber =
        $customerLease["unit"]
        ?? $customerLease["unitNumber"]
        ?? "Not available";
}

/*
|--------------------------------------------------------------------------
| Tenant
|--------------------------------------------------------------------------
*/

$tenantName = "";

if (
    isset($customerLease["tenantId"]) &&
    is_array($customerLease["tenantId"])
) {
    $tenantName =
        $customerLease["tenantId"]["name"]
        ?? "";
}

if ($tenantName === "") {
    $tenantName =
        $customerLease["tenant"]
        ?? $customerName;
}

/*
|--------------------------------------------------------------------------
| Lease values
|--------------------------------------------------------------------------
*/

$leaseId =
    $customerLease["leaseId"]
    ?? $customerLease["id"]
    ?? $customerLease["_id"]
    ?? "N/A";

$monthlyRent = (float)(
    $customerLease["rent"]
    ?? $customerLease["monthlyRent"]
    ?? 0
);

$leaseStatus =
    $customerLease["status"]
    ?? "Active";

$startDate =
    $customerLease["startDate"]
    ?? $customerLease["start_date"]
    ?? "";

$endDate =
    $customerLease["endDate"]
    ?? $customerLease["end_date"]
    ?? "";

$status = strtolower((string)$leaseStatus);

$statusClass = match ($status) {
    "active" =>
        "bg-emerald-50 text-emerald-700 ring-emerald-600/20",

    "expired",
    "terminated" =>
        "bg-red-50 text-red-700 ring-red-600/20",

    "pending" =>
        "bg-amber-50 text-amber-700 ring-amber-600/20",

    default =>
        "bg-slate-100 text-slate-700 ring-slate-600/20",
};

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

        <!-- Page Header -->
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <p class="text-sm font-semibold text-indigo-600">
                    Customer Portal
                </p>

                <h1 class="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">
                    My Lease
                </h1>

                <p class="mt-2 text-sm text-slate-500">
                    View your current rental agreement and lease details.
                </p>
            </div>

            <a
                href="dashboard.php"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            >
                ← Back to Dashboard
            </a>

        </div>

        <?php if (!empty($customerLease)): ?>

            <!-- Summary -->
            <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Lease ID</p>

                    <p class="mt-2 break-all text-lg font-bold text-slate-900">
                        <?= lease_escape($leaseId) ?>
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Tenant</p>

                    <p class="mt-2 text-lg font-bold text-slate-900">
                        <?= lease_escape($tenantName) ?>
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Monthly Rent</p>

                    <p class="mt-2 text-lg font-bold text-slate-900">
                        KSh <?= number_format($monthlyRent, 2) ?>
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Status</p>

                    <div class="mt-3">
                        <span
                            class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset <?= $statusClass ?>"
                        >
                            <?= lease_escape($leaseStatus) ?>
                        </span>
                    </div>
                </div>

            </div>

            <!-- Lease Details -->
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">

                    <h2 class="text-lg font-semibold text-slate-900">
                        Lease Details
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Information about your current rental agreement.
                    </p>

                </div>

                <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-3">

                    <div>
                        <p class="text-sm text-slate-500">Property</p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= lease_escape($propertyName) ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Unit</p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= lease_escape($unitNumber) ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Monthly Rent</p>

                        <p class="mt-1 font-semibold text-slate-900">
                            KSh <?= number_format($monthlyRent, 2) ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Lease Start</p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= format_lease_date($startDate) ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Lease End</p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= format_lease_date($endDate) ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Lease Status</p>

                        <div class="mt-2">
                            <span
                                class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset <?= $statusClass ?>"
                            >
                                <?= lease_escape($leaseStatus) ?>
                            </span>
                        </div>
                    </div>

                </div>

            </section>

            <!-- Notice -->
            <section class="mt-6 rounded-xl border border-indigo-100 bg-indigo-50 p-5">

                <h2 class="font-semibold text-indigo-900">
                    Rental Agreement
                </h2>

                <p class="mt-2 text-sm leading-6 text-indigo-800">
                    This page displays the current lease information connected
                    to your PropertyPro account. Contact the property
                    administrator if any lease information is incorrect.
                </p>

            </section>

        <?php else: ?>

            <!-- No Lease -->
            <section class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">

                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-3xl">
                    📄
                </div>

                <h2 class="mt-5 text-xl font-bold text-slate-900">
                    No Active Lease Found
                </h2>

                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                    There is currently no lease connected to your customer
                    account. Please contact the property administrator for
                    assistance.
                </p>

                <a
                    href="dashboard.php"
                    class="mt-6 inline-flex rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700"
                >
                    Return to Dashboard
                </a>

            </section>

        <?php endif; ?>

    </main>

</div>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>