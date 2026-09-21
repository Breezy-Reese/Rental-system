<?php

require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../includes/api.php";

require_login();

if (current_role() !== 'Customer') {
    header("Location: ../admin/dashboard.php");
    exit;
}

$pageTitle = "Customer Dashboard";

require_once __DIR__ . "/../../includes/data.php";
require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";

$user = current_user();

$customerName = $user['name'] ?? 'Customer';

/*
|--------------------------------------------------------------------------
| Customer dashboard data
|--------------------------------------------------------------------------
|
| data.php gets these from:
| GET /api/customer/dashboard
| GET /api/customer/payments
| GET /api/customer/lease
| GET /api/customer/maintenance
|--------------------------------------------------------------------------
*/

$currentLease = $currentLease ?? [];

if (empty($currentLease) && !empty($leases)) {
    $currentLease = $leases[0];
}

$currentPayment = [];

if (!empty($payments)) {
    $currentPayment = $payments[0];
}

$monthlyRent = (float) (
    $currentLease['rent']
    ?? $currentLease['monthlyRent']
    ?? $currentPayment['amount']
    ?? 0
);

$nextPayment = $currentPayment['status'] ?? 'Pending';

$maintenanceForCustomer = $maintenanceRequests ?? [];

$totalMaintenance = count($maintenanceForCustomer);

$pendingMaintenance = 0;

foreach ($maintenanceForCustomer as $request) {
    $status = strtolower($request['status'] ?? '');

    if (
        $status === 'pending' ||
        $status === 'assigned' ||
        $status === 'in progress'
    ) {
        $pendingMaintenance++;
    }
}

?>

<div class="lg:pl-64">

    <!-- Header -->
    <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 lg:px-8">

        <button
            id="openSidebar"
            type="button"
            class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden"
        >
            ☰
        </button>

        <div class="ml-auto flex items-center gap-4">

            <div class="hidden text-right sm:block">

                <p class="text-sm font-semibold text-slate-800">
                    <?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?>
                </p>

                <p class="text-xs text-slate-500">
                    Customer
                </p>

            </div>

            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-600 font-semibold text-white">
                <?= htmlspecialchars(
                    strtoupper(substr($customerName, 0, 1)),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
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
                Welcome, <?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?>
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Manage your rental, payments and maintenance requests.
            </p>

        </div>

        <!-- Stats -->
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">

            <!-- Property -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">

                <p class="text-sm text-slate-500">
                    My Property
                </p>

                <p class="mt-2 text-lg font-bold text-slate-900">
                    <?= htmlspecialchars(
                        $currentLease['property']
                        ?? $currentLease['propertyId']['name']
                        ?? 'No property assigned',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

                <a
                    href="leases.php"
                    class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-700"
                >
                    View lease →
                </a>

            </div>

            <!-- Unit -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">

                <p class="text-sm text-slate-500">
                    My Unit
                </p>

                <p class="mt-2 text-lg font-bold text-slate-900">
                    <?= htmlspecialchars(
                        $currentLease['unit']
                        ?? $currentLease['unitId']['unitNumber']
                        ?? 'Not assigned',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

                <a
                    href="leases.php"
                    class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-700"
                >
                    View details →
                </a>

            </div>

            <!-- Monthly Rent -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">

                <p class="text-sm text-slate-500">
                    Monthly Rent
                </p>

                <p class="mt-2 text-2xl font-bold text-slate-900">
                    KSh <?= number_format($monthlyRent) ?>
                </p>

                <p class="mt-2 text-xs text-slate-500">
                    Current lease amount
                </p>

            </div>

            <!-- Payment Status -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">

                <p class="text-sm text-slate-500">
                    Latest Payment
                </p>

                <p class="mt-2 text-lg font-bold text-slate-900">
                    <?= htmlspecialchars(
                        $nextPayment,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

                <a
                    href="payments.php"
                    class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-700"
                >
                    View payments →
                </a>

            </div>

        </div>

        <!-- Quick Actions -->
        <div class="mt-8 grid gap-5 md:grid-cols-3">

            <a
                href="payments.php"
                class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-md"
            >

                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-xl">
                    💳
                </div>

                <h2 class="font-semibold text-slate-900">
                    My Payments
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Submit and track your rent payments.
                </p>

            </a>

            <a
                href="maintenance.php"
                class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-md"
            >

                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-xl">
                    🔧
                </div>

                <h2 class="font-semibold text-slate-900">
                    Maintenance
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Report and track maintenance issues.
                </p>

                <p class="mt-3 text-xs text-slate-500">
                    <?= $pendingMaintenance ?> active request(s)
                </p>

            </a>

            <a
                href="profile.php"
                class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-md"
            >

                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-xl">
                    👤
                </div>

                <h2 class="font-semibold text-slate-900">
                    My Profile
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Manage your personal information.
                </p>

            </a>

        </div>

    </main>

</div>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>