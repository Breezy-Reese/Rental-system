<?php

/**
 * ============================================================
 * PropertyPro - Customer Settings
 * ============================================================
 */

$pageTitle = "Settings";

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

$name   = $user['name'] ?? 'Customer';
$email  = $user['email'] ?? '';
$role   = $user['role'] ?? 'Customer';
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

    <div class="p-4 sm:p-6 lg:p-8">

        <!-- Page Header -->
        <div class="mb-8">

            <h1 class="text-2xl font-bold text-slate-900">
                Settings
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Manage your account settings and preferences.
            </p>

        </div>

        <div class="grid gap-6 lg:grid-cols-3">

            <!-- Profile Summary -->
            <div class="rounded-xl border bg-white p-6">

                <div class="flex flex-col items-center text-center">

                    <!-- Avatar -->
                    <div
                        class="flex h-24 w-24 items-center justify-center rounded-full bg-indigo-600 text-2xl font-bold text-white"
                    >
                        <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                    </div>

                    <!-- Name -->
                    <h2 class="mt-4 text-xl font-bold text-slate-900">
                        <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                    </h2>

                    <!-- Email -->
                    <p class="mt-1 text-sm text-slate-500">
                        <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <!-- Role -->
                    <span
                        class="mt-4 rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700"
                    >
                        <?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>
                    </span>

                </div>

            </div>

            <!-- Settings -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Account Settings -->
                <div class="overflow-hidden rounded-xl border bg-white">

                    <div class="border-b px-6 py-5">

                        <h2 class="text-lg font-semibold text-slate-900">
                            Account Settings
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            View your account information.
                        </p>

                    </div>

                    <div class="space-y-6 p-6">

                        <!-- Full Name -->
                        <div>

                            <label
                                class="block text-sm font-medium text-slate-700"
                            >
                                Full Name
                            </label>

                            <div
                                class="mt-2 rounded-lg border bg-slate-50 px-4 py-3 text-sm text-slate-900"
                            >
                                <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                            </div>

                        </div>

                        <!-- Email -->
                        <div>

                            <label
                                class="block text-sm font-medium text-slate-700"
                            >
                                Email Address
                            </label>

                            <div
                                class="mt-2 rounded-lg border bg-slate-50 px-4 py-3 text-sm text-slate-900"
                            >
                                <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>
                            </div>

                        </div>

                        <!-- Account Type -->
                        <div>

                            <label
                                class="block text-sm font-medium text-slate-700"
                            >
                                Account Type
                            </label>

                            <div
                                class="mt-2 rounded-lg border bg-slate-50 px-4 py-3 text-sm text-slate-900"
                            >
                                Customer
                            </div>

                        </div>

                        <!-- Account ID -->
                        <div>

                            <label
                                class="block text-sm font-medium text-slate-700"
                            >
                                Account ID
                            </label>

                            <div
                                class="mt-2 break-all rounded-lg border bg-slate-50 px-4 py-3 text-sm text-slate-900"
                            >
                                <?= htmlspecialchars($userId, ENT_QUOTES, 'UTF-8') ?>
                            </div>

                        </div>

                    </div>

                </div>

                <!-- Security -->
                <div class="overflow-hidden rounded-xl border bg-white">

                    <div class="border-b px-6 py-5">

                        <h2 class="text-lg font-semibold text-slate-900">
                            Security
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Manage your account security.
                        </p>

                    </div>

                    <div class="p-6">

                        <div
                            class="flex flex-col gap-4 rounded-lg border bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between"
                        >

                            <div>

                                <h3 class="font-medium text-slate-900">
                                    Password
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    Keep your account secure with a strong password.
                                </p>

                            </div>

                            <a
                                href="profile.php"
                                class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700"
                            >
                                Account Profile
                            </a>

                        </div>

                    </div>

                </div>

                <!-- Rental Settings -->
                <div class="overflow-hidden rounded-xl border bg-white">

                    <div class="border-b px-6 py-5">

                        <h2 class="text-lg font-semibold text-slate-900">
                            Rental Account
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Quickly access your rental information.
                        </p>

                    </div>

                    <div class="grid gap-4 p-6 sm:grid-cols-3">

                        <!-- Lease -->
                        <a
                            href="leases.php"
                            class="rounded-lg border p-4 transition hover:border-indigo-300 hover:bg-indigo-50"
                        >

                            <div class="text-2xl">
                                📄
                            </div>

                            <h3 class="mt-2 font-semibold text-slate-900">
                                My Lease
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                View your lease.
                            </p>

                        </a>

                        <!-- Payments -->
                        <a
                            href="payments.php"
                            class="rounded-lg border p-4 transition hover:border-indigo-300 hover:bg-indigo-50"
                        >

                            <div class="text-2xl">
                                💳
                            </div>

                            <h3 class="mt-2 font-semibold text-slate-900">
                                My Payments
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                View payments.
                            </p>

                        </a>

                        <!-- Maintenance -->
                        <a
                            href="maintenance.php"
                            class="rounded-lg border p-4 transition hover:border-indigo-300 hover:bg-indigo-50"
                        >

                            <div class="text-2xl">
                                🔧
                            </div>

                            <h3 class="mt-2 font-semibold text-slate-900">
                                Maintenance
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                View requests.
                            </p>

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</main>

<?php
require_once __DIR__ . "/../../includes/footer.php";
?>