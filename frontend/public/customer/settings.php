```php
<?php

$pageTitle = "Settings";

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

$user = current_user();

$name = $user['name'] ?? 'Customer';
$email = $user['email'] ?? '';

/*
|--------------------------------------------------------------------------
| Default settings
|--------------------------------------------------------------------------
|
| For the current demo application, these are stored in the session.
| Later, they can be moved to a database table.
|
*/
if (!isset($_SESSION['settings'])) {
    $_SESSION['settings'] = [
        'email_notifications' => true,
        'payment_reminders' => true,
        'maintenance_updates' => true,
        'marketing_notifications' => false,
        'language' => 'English',
        'timezone' => 'Africa/Nairobi',
    ];
}

$settings = $_SESSION['settings'];

$successMessage = '';
$errorMessage = '';

/*
|--------------------------------------------------------------------------
| Save settings
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $emailNotifications = isset($_POST['email_notifications']);
    $paymentReminders = isset($_POST['payment_reminders']);
    $maintenanceUpdates = isset($_POST['maintenance_updates']);
    $marketingNotifications = isset($_POST['marketing_notifications']);

    $language = $_POST['language'] ?? 'English';
    $timezone = $_POST['timezone'] ?? 'Africa/Nairobi';

    $allowedLanguages = [
        'English',
        'Swahili'
    ];

    $allowedTimezones = [
        'Africa/Nairobi',
        'Africa/Kampala',
        'Africa/Dar_es_Salaam',
        'UTC'
    ];

    if (!in_array($language, $allowedLanguages, true)) {
        $language = 'English';
    }

    if (!in_array($timezone, $allowedTimezones, true)) {
        $timezone = 'Africa/Nairobi';
    }

    $_SESSION['settings'] = [
        'email_notifications' => $emailNotifications,
        'payment_reminders' => $paymentReminders,
        'maintenance_updates' => $maintenanceUpdates,
        'marketing_notifications' => $marketingNotifications,
        'language' => $language,
        'timezone' => $timezone,
    ];

    $settings = $_SESSION['settings'];

    $successMessage = 'Your settings have been saved successfully.';
}

/*
|--------------------------------------------------------------------------
| Escape output
|--------------------------------------------------------------------------
*/
function settings_e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
?>

<main class="flex-1 lg:ml-64">

    <?php require_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div class="p-4 sm:p-6 lg:p-8">

        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <h1 class="text-2xl font-bold text-slate-900">
                        Settings
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Manage your account preferences and notifications.
                    </p>
                </div>

                <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">

                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 font-semibold text-indigo-700">
                        <?= settings_e(
                            strtoupper(substr($name, 0, 1))
                        ) ?>
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-slate-900">
                            <?= settings_e($name) ?>
                        </p>

                        <p class="text-xs text-slate-500">
                            <?= settings_e($email) ?>
                        </p>
                    </div>

                </div>

            </div>
        </div>

        <!-- Success Message -->
        <?php if ($successMessage): ?>

            <div class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">

                <div class="text-lg">
                    ✓
                </div>

                <div>
                    <p class="font-semibold">
                        Settings updated
                    </p>

                    <p class="mt-1 text-sm">
                        <?= settings_e($successMessage) ?>
                    </p>
                </div>

            </div>

        <?php endif; ?>

        <!-- Settings Form -->
        <form method="POST" action="settings.php">

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

                <!-- Main Settings -->
                <div class="space-y-6 xl:col-span-2">

                    <!-- Notifications -->
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                        <div class="border-b border-slate-200 px-6 py-5">

                            <h2 class="text-lg font-semibold text-slate-900">
                                Notifications
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Choose which notifications you would like to receive.
                            </p>

                        </div>

                        <div class="divide-y divide-slate-100">

                            <!-- Email Notifications -->
                            <label class="flex cursor-pointer items-center justify-between gap-4 px-6 py-5">

                                <div>
                                    <p class="font-medium text-slate-900">
                                        Email notifications
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Receive important account notifications by email.
                                    </p>
                                </div>

                                <input
                                    type="checkbox"
                                    name="email_notifications"
                                    value="1"
                                    class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    <?= !empty($settings['email_notifications']) ? 'checked' : '' ?>
                                >

                            </label>

                            <!-- Payment Reminders -->
                            <label class="flex cursor-pointer items-center justify-between gap-4 px-6 py-5">

                                <div>
                                    <p class="font-medium text-slate-900">
                                        Payment reminders
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Get reminders about upcoming or outstanding rent payments.
                                    </p>
                                </div>

                                <input
                                    type="checkbox"
                                    name="payment_reminders"
                                    value="1"
                                    class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    <?= !empty($settings['payment_reminders']) ? 'checked' : '' ?>
                                >

                            </label>

                            <!-- Maintenance Updates -->
                            <label class="flex cursor-pointer items-center justify-between gap-4 px-6 py-5">

                                <div>
                                    <p class="font-medium text-slate-900">
                                        Maintenance updates
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Receive updates when your maintenance request changes status.
                                    </p>
                                </div>

                                <input
                                    type="checkbox"
                                    name="maintenance_updates"
                                    value="1"
                                    class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    <?= !empty($settings['maintenance_updates']) ? 'checked' : '' ?>
                                >

                            </label>

                            <!-- Marketing -->
                            <label class="flex cursor-pointer items-center justify-between gap-4 px-6 py-5">

                                <div>
                                    <p class="font-medium text-slate-900">
                                        General notifications
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Receive optional PropertyPro announcements and updates.
                                    </p>
                                </div>

                                <input
                                    type="checkbox"
                                    name="marketing_notifications"
                                    value="1"
                                    class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    <?= !empty($settings['marketing_notifications']) ? 'checked' : '' ?>
                                >

                            </label>

                        </div>

                    </section>

                    <!-- Regional Settings -->
                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                        <div class="border-b border-slate-200 px-6 py-5">

                            <h2 class="text-lg font-semibold text-slate-900">
                                Regional Preferences
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Choose your preferred language and timezone.
                            </p>

                        </div>

                        <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">

                            <!-- Language -->
                            <div>

                                <label
                                    for="language"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Language
                                </label>

                                <select
                                    id="language"
                                    name="language"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                >
                                    <option
                                        value="English"
                                        <?= $settings['language'] === 'English' ? 'selected' : '' ?>
                                    >
                                        English
                                    </option>

                                    <option
                                        value="Swahili"
                                        <?= $settings['language'] === 'Swahili' ? 'selected' : '' ?>
                                    >
                                        Swahili
                                    </option>
                                </select>

                            </div>

                            <!-- Timezone -->
                            <div>

                                <label
                                    for="timezone"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Timezone
                                </label>

                                <select
                                    id="timezone"
                                    name="timezone"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                >

                                    <option
                                        value="Africa/Nairobi"
                                        <?= $settings['timezone'] === 'Africa/Nairobi' ? 'selected' : '' ?>
                                    >
                                        East Africa Time (Nairobi)
                                    </option>

                                    <option
                                        value="Africa/Kampala"
                                        <?= $settings['timezone'] === 'Africa/Kampala' ? 'selected' : '' ?>
                                    >
                                        East Africa Time (Kampala)
                                    </option>

                                    <option
                                        value="Africa/Dar_es_Salaam"
                                        <?= $settings['timezone'] === 'Africa/Dar_es_Salaam' ? 'selected' : '' ?>
                                    >
                                        East Africa Time (Dar es Salaam)
                                    </option>

                                    <option
                                        value="UTC"
                                        <?= $settings['timezone'] === 'UTC' ? 'selected' : '' ?>
                                    >
                                        UTC
                                    </option>

                                </select>

                            </div>

                        </div>

                    </section>

                    <!-- Save -->
                    <div class="flex justify-end">

                        <button
                            type="submit"
                            class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Save Changes
                        </button>

                    </div>

                </div>

                <!-- Right Column -->
                <div class="space-y-6">

                    <!-- Account -->
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                        <h2 class="text-lg font-semibold text-slate-900">
                            Account
                        </h2>

                        <div class="mt-5 space-y-4">

                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                    Name
                                </p>

                                <p class="mt-1 text-sm font-medium text-slate-800">
                                    <?= settings_e($name) ?>
                                </p>
                            </div>

                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                    Email
                                </p>

                                <p class="mt-1 break-all text-sm font-medium text-slate-800">
                                    <?= settings_e($email) ?>
                                </p>
                            </div>

                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                    Account Type
                                </p>

                                <p class="mt-1 text-sm font-medium text-slate-800">
                                    Customer
                                </p>
                            </div>

                        </div>

                        <a
                            href="profile.php"
                            class="mt-6 block rounded-xl border border-slate-200 px-4 py-3 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            View My Profile
                        </a>

                    </section>

                    <!-- Rental Links -->
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                        <h2 class="text-lg font-semibold text-slate-900">
                            My Rental
                        </h2>

                        <div class="mt-4 space-y-2">

                            <a
                                href="leases.php"
                                class="flex items-center justify-between rounded-xl px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-700"
                            >
                                <span>My Lease</span>
                                <span>→</span>
                            </a>

                            <a
                                href="payments.php"
                                class="flex items-center justify-between rounded-xl px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-700"
                            >
                                <span>My Payments</span>
                                <span>→</span>
                            </a>

                            <a
                                href="maintenance.php"
                                class="flex items-center justify-between rounded-xl px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-700"
                            >
                                <span>Maintenance Requests</span>
                                <span>→</span>
                            </a>

                        </div>

                    </section>

                    <!-- Security -->
                    <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6">

                        <div class="flex gap-3">

                            <div class="text-xl">
                                🔒
                            </div>

                            <div>
                                <h2 class="font-semibold text-amber-900">
                                    Account Security
                                </h2>

                                <p class="mt-2 text-sm leading-6 text-amber-800">
                                    Keep your account information private and use a strong password.
                                </p>

                                <a
                                    href="profile.php"
                                    class="mt-4 inline-block text-sm font-semibold text-amber-900 hover:underline"
                                >
                                    Manage account →
                                </a>
                            </div>

                        </div>

                    </section>

                </div>

            </div>

        </form>

    </div>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>