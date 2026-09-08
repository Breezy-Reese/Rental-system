<?php

$pageTitle = "My Lease";

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
| Load data
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
| Find customer's lease
|--------------------------------------------------------------------------
*/

$customerLease = null;

foreach ($leases as $lease) {

    if (
        isset($lease['tenant']) &&
        strcasecmp($lease['tenant'], $customerName) === 0
    ) {
        $customerLease = $lease;
        break;
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
                My Lease
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                View your current rental agreement and lease details.
            </p>

        </div>


        <?php if ($customerLease): ?>

            <!-- Lease Overview -->
            <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                <!-- Lease ID -->
                <div class="rounded-xl border bg-white p-5">

                    <p class="text-sm text-slate-500">
                        Lease ID
                    </p>

                    <p class="mt-2 text-lg font-bold text-slate-900">
                        <?= htmlspecialchars($customerLease['id'] ?? 'N/A') ?>
                    </p>

                </div>


                <!-- Tenant -->
                <div class="rounded-xl border bg-white p-5">

                    <p class="text-sm text-slate-500">
                        Tenant
                    </p>

                    <p class="mt-2 text-lg font-bold text-slate-900">
                        <?= htmlspecialchars($customerLease['tenant'] ?? $customerName) ?>
                    </p>

                </div>


                <!-- Rent -->
                <div class="rounded-xl border bg-white p-5">

                    <p class="text-sm text-slate-500">
                        Monthly Rent
                    </p>

                    <p class="mt-2 text-lg font-bold text-slate-900">
                        KSh <?= number_format((float) ($customerLease['rent'] ?? 0)) ?>
                    </p>

                </div>


                <!-- Status -->
                <div class="rounded-xl border bg-white p-5">

                    <p class="text-sm text-slate-500">
                        Status
                    </p>

                    <div class="mt-2">

                        <span
                            class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700">

                            <?= htmlspecialchars($customerLease['status'] ?? 'Active') ?>

                        </span>

                    </div>

                </div>

            </div>


            <!-- Lease Details -->
            <div class="overflow-hidden rounded-xl border bg-white">

                <div class="border-b px-6 py-5">

                    <h2 class="text-lg font-semibold text-slate-900">
                        Lease Details
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Information about your current rental agreement.
                    </p>

                </div>


                <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-3">

                    <!-- Property -->
                    <div>

                        <p class="text-sm text-slate-500">
                            Property
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= htmlspecialchars($customerLease['property'] ?? 'Greenview Apartments') ?>
                        </p>

                    </div>


                    <!-- Unit -->
                    <div>

                        <p class="text-sm text-slate-500">
                            Unit
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= htmlspecialchars($customerLease['unit'] ?? 'A-101') ?>
                        </p>

                    </div>


                    <!-- Monthly Rent -->
                    <div>

                        <p class="text-sm text-slate-500">
                            Monthly Rent
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            KSh <?= number_format((float) ($customerLease['rent'] ?? 0)) ?>
                        </p>

                    </div>


                    <!-- Start Date -->
                    <div>

                        <p class="text-sm text-slate-500">
                            Lease Start
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= htmlspecialchars($customerLease['start_date'] ?? 'Jan 1, 2026') ?>
                        </p>

                    </div>


                    <!-- End Date -->
                    <div>

                        <p class="text-sm text-slate-500">
                            Lease End
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            <?= htmlspecialchars($customerLease['end_date'] ?? 'Dec 31, 2026') ?>
                        </p>

                    </div>


                    <!-- Lease Status -->
                    <div>

                        <p class="text-sm text-slate-500">
                            Lease Status
                        </p>

                        <p class="mt-1">

                            <span
                                class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">

                                <?= htmlspecialchars($customerLease['status'] ?? 'Active') ?>

                            </span>

                        </p>

                    </div>

                </div>

            </div>


        <?php else: ?>

            <!-- No Lease -->
            <div class="rounded-xl border bg-white p-10 text-center">

                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-3xl">
                    📄
                </div>

                <h2 class="mt-4 text-lg font-semibold text-slate-900">
                    No Active Lease
                </h2>

                <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                    You currently do not have an active lease associated
                    with your account.
                </p>

            </div>

        <?php endif; ?>

    </div>

</main>


<?php require_once __DIR__ . "/../../includes/footer.php"; ?>