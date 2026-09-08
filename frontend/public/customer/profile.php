<?php

$pageTitle = "My Profile";

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
| Current customer
|--------------------------------------------------------------------------
*/

$user = current_user();

$name = $user['name'] ?? 'Customer';
$email = $user['email'] ?? '';
$role = $user['role'] ?? 'Customer';
$userId = $user['id'] ?? 'N/A';

/*
|--------------------------------------------------------------------------
| User initials
|--------------------------------------------------------------------------
*/

$nameParts = preg_split('/\s+/', trim($name));

$initials = '';

foreach ($nameParts as $part) {
    if ($part !== '') {
        $initials .= strtoupper(substr($part, 0, 1));
    }
}

$initials = substr($initials, 0, 2);

if ($initials === '') {
    $initials = 'CU';
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
                My Profile
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                View and manage your account information.
            </p>

        </div>


        <div class="grid gap-6 lg:grid-cols-3">

            <!-- Profile Card -->
            <div class="rounded-xl border bg-white p-6">

                <div class="flex flex-col items-center text-center">

                    <!-- Avatar -->
                    <div
                        class="flex h-24 w-24 items-center justify-center rounded-full bg-indigo-600 text-2xl font-bold text-white">

                        <?= htmlspecialchars($initials) ?>

                    </div>


                    <h2 class="mt-4 text-xl font-bold text-slate-900">
                        <?= htmlspecialchars($name) ?>
                    </h2>


                    <p class="mt-1 text-sm text-slate-500">
                        <?= htmlspecialchars($email) ?>
                    </p>


                    <span
                        class="mt-4 rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700">

                        <?= htmlspecialchars($role) ?>

                    </span>

                </div>

            </div>


            <!-- Account Information -->
            <div class="lg:col-span-2">

                <div class="overflow-hidden rounded-xl border bg-white">

                    <div class="border-b px-6 py-5">

                        <h2 class="text-lg font-semibold text-slate-900">
                            Account Information
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Your account details.
                        </p>

                    </div>


                    <div class="grid gap-6 p-6 sm:grid-cols-2">

                        <!-- Full Name -->
                        <div>

                            <label class="text-sm font-medium text-slate-500">
                                Full Name
                            </label>

                            <div
                                class="mt-2 rounded-lg border bg-slate-50 px-4 py-3 text-sm text-slate-900">

                                <?= htmlspecialchars($name) ?>

                            </div>

                        </div>


                        <!-- Email -->
                        <div>

                            <label class="text-sm font-medium text-slate-500">
                                Email Address
                            </label>

                            <div
                                class="mt-2 rounded-lg border bg-slate-50 px-4 py-3 text-sm text-slate-900">

                                <?= htmlspecialchars($email) ?>

                            </div>

                        </div>


                        <!-- Account ID -->
                        <div>

                            <label class="text-sm font-medium text-slate-500">
                                Account ID
                            </label>

                            <div
                                class="mt-2 rounded-lg border bg-slate-50 px-4 py-3 text-sm text-slate-900">

                                <?= htmlspecialchars($userId) ?>

                            </div>

                        </div>


                        <!-- Account Type -->
                        <div>

                            <label class="text-sm font-medium text-slate-500">
                                Account Type
                            </label>

                            <div
                                class="mt-2 rounded-lg border bg-slate-50 px-4 py-3 text-sm text-slate-900">

                                Customer

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Rental Account -->
                <div class="mt-6 overflow-hidden rounded-xl border bg-white">

                    <div class="border-b px-6 py-5">

                        <h2 class="text-lg font-semibold text-slate-900">
                            Rental Account
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Access your rental-related information.
                        </p>

                    </div>


                    <div class="grid gap-4 p-6 sm:grid-cols-3">

                        <!-- Lease -->
                        <a
                            href="leases.php"
                            class="rounded-lg border p-4 transition hover:border-indigo-300 hover:bg-indigo-50">

                            <div class="text-2xl">
                                📄
                            </div>

                            <h3 class="mt-2 font-semibold text-slate-900">
                                My Lease
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                View your lease details.
                            </p>

                        </a>


                        <!-- Payments -->
                        <a
                            href="payments.php"
                            class="rounded-lg border p-4 transition hover:border-indigo-300 hover:bg-indigo-50">

                            <div class="text-2xl">
                                💳
                            </div>

                            <h3 class="mt-2 font-semibold text-slate-900">
                                My Payments
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                View your payment history.
                            </p>

                        </a>


                        <!-- Maintenance -->
                        <a
                            href="maintenance.php"
                            class="rounded-lg border p-4 transition hover:border-indigo-300 hover:bg-indigo-50">

                            <div class="text-2xl">
                                🔧
                            </div>

                            <h3 class="mt-2 font-semibold text-slate-900">
                                Maintenance
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                View your maintenance requests.
                            </p>

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</main>


<?php require_once __DIR__ . "/../../includes/footer.php"; ?>