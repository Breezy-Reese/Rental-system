<?php

require_once "../../includes/admin.php";
require_admin();

require_once "../../includes/data.php";

$pageTitle = "Lease Details";

/*
|--------------------------------------------------------------------------
| Get lease ID
|--------------------------------------------------------------------------
*/

$leaseId = $_GET["id"] ?? "";

if ($leaseId === "") {
    header("Location: leases.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Find lease from loaded leases
|--------------------------------------------------------------------------
*/

$selectedLease = null;

foreach ($leases as $lease) {
    $databaseId = $lease["_id"] ?? "";
    $displayId = $lease["leaseId"] ?? $lease["id"] ?? "";

    if (
        (string) $databaseId === (string) $leaseId ||
        (string) $displayId === (string) $leaseId
    ) {
        $selectedLease = $lease;
        break;
    }
}

/*
|--------------------------------------------------------------------------
| Values
|--------------------------------------------------------------------------
*/

$tenant = $selectedLease["tenant"] ?? "Not available";
$property = $selectedLease["property"] ?? "Not available";
$unit = $selectedLease["unit"] ?? "Not available";

$rent = (float) (
    $selectedLease["rent"]
    ?? $selectedLease["monthlyRent"]
    ?? 0
);

$status = $selectedLease["status"] ?? "Active";

$startDate =
    $selectedLease["startDate"]
    ?? $selectedLease["start_date"]
    ?? "";

$endDate =
    $selectedLease["endDate"]
    ?? $selectedLease["end_date"]
    ?? "";

$leaseDisplayId =
    $selectedLease["leaseId"]
    ?? $selectedLease["id"]
    ?? $selectedLease["_id"]
    ?? "N/A";

/*
|--------------------------------------------------------------------------
| Date formatter
|--------------------------------------------------------------------------
*/

function formatLeaseDate($date)
{
    if (!$date) {
        return "Not available";
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return htmlspecialchars(
            $date,
            ENT_QUOTES,
            "UTF-8"
        );
    }

    return date("d M Y", $timestamp);
}

/*
|--------------------------------------------------------------------------
| Status styling
|--------------------------------------------------------------------------
*/

$statusLower = strtolower($status);

if ($statusLower === "active") {
    $statusClass =
        "bg-emerald-50 text-emerald-700 ring-emerald-600/20";
} elseif ($statusLower === "expired") {
    $statusClass =
        "bg-red-50 text-red-700 ring-red-600/20";
} elseif ($statusLower === "terminated") {
    $statusClass =
        "bg-red-50 text-red-700 ring-red-600/20";
} else {
    $statusClass =
        "bg-amber-50 text-amber-700 ring-amber-600/20";
}

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
?>

<main class="min-h-screen bg-slate-50 lg:ml-64">

    <header class="border-b border-slate-200 bg-white">

        <div class="flex min-h-16 items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">

            <div class="flex items-center gap-3">

                <button
                    id="mobileMenuButton"
                    type="button"
                    aria-label="Open navigation menu"
                    class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden"
                >
                    ☰
                </button>

                <div>

                    <h1 class="text-lg font-semibold text-slate-900">
                        Lease Details
                    </h1>

                    <p class="hidden text-xs text-slate-500 sm:block">
                        View rental agreement information
                    </p>

                </div>

            </div>

            <a
                href="leases.php"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                ← Back to Leases
            </a>

        </div>

    </header>

    <div class="p-4 sm:p-6 lg:p-8">

        <?php if (!$selectedLease): ?>

            <!-- Not Found -->

            <div class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">

                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-50 text-2xl">
                    !
                </div>

                <h2 class="mt-5 text-xl font-bold text-slate-900">
                    Lease Not Found
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    The requested lease could not be found.
                </p>

                <a
                    href="leases.php"
                    class="mt-6 inline-flex rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                >
                    Return to Leases
                </a>

            </div>

        <?php else: ?>

            <!-- Page Heading -->

            <div class="mb-6">

                <p class="text-sm font-semibold text-indigo-600">
                    Lease Agreement
                </p>

                <h2 class="mt-1 text-2xl font-bold text-slate-900">
                    <?= e($leaseDisplayId) ?>
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Complete information for this rental agreement.
                </p>

            </div>

            <!-- Summary Cards -->

            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

                <!-- Tenant -->

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                    <p class="text-sm text-slate-500">
                        Tenant
                    </p>

                    <p class="mt-2 text-lg font-bold text-slate-900">
                        <?= e($tenant) ?>
                    </p>

                </div>

                <!-- Property -->

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                    <p class="text-sm text-slate-500">
                        Property
                    </p>

                    <p class="mt-2 text-lg font-bold text-slate-900">
                        <?= e($property) ?>
                    </p>

                </div>

                <!-- Rent -->

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                    <p class="text-sm text-slate-500">
                        Monthly Rent
                    </p>

                    <p class="mt-2 text-lg font-bold text-slate-900">
                        KSh <?= number_format($rent, 2) ?>
                    </p>

                </div>

                <!-- Status -->

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                    <p class="text-sm text-slate-500">
                        Status
                    </p>

                    <div class="mt-3">

                        <span
                            class="inline-flex rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset <?= $statusClass ?>"
                        >
                            <?= e($status) ?>
                        </span>

                    </div>

                </div>

            </div>

            <!-- Agreement Information -->

            <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">

                    <h3 class="text-lg font-semibold text-slate-900">
                        Rental Agreement Information
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Details of the selected lease.
                    </p>

                </div>

                <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-3">

                    <div>
                        <p class="text-sm text-slate-500">
                            Lease ID
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= e($leaseDisplayId) ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">
                            Tenant
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= e($tenant) ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">
                            Property
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= e($property) ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">
                            Unit
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= e($unit) ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">
                            Monthly Rent
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            KSh <?= number_format($rent, 2) ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">
                            Deposit
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            KSh <?= number_format(
                                (float) ($selectedLease["deposit"] ?? 0),
                                2
                            ) ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">
                            Lease Start
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= formatLeaseDate($startDate) ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">
                            Lease End
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= formatLeaseDate($endDate) ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">
                            Status
                        </p>

                        <div class="mt-2">

                            <span
                                class="inline-flex rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset <?= $statusClass ?>"
                            >
                                <?= e($status) ?>
                            </span>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Actions -->

            <div class="mt-6 flex flex-wrap gap-3">

                <a
                    href="leases.php"
                    class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                >
                    ← Back to All Leases
                </a>

            </div>

        <?php endif; ?>

    </div>

</main>

<?php require_once "../../includes/footer.php"; ?>