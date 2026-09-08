```php
<?php

/*
|--------------------------------------------------------------------------
| PropertyPro - Shared Sidebar
|--------------------------------------------------------------------------
| Used by:
| - Administrator
| - Customer
|--------------------------------------------------------------------------
*/

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Load authentication and notification helpers
|--------------------------------------------------------------------------
*/

$authFile = __DIR__ . "/auth.php";

if (file_exists($authFile)) {
    require_once $authFile;
}

$notificationsFile = __DIR__ . "/notifications.php";

if (file_exists($notificationsFile)) {
    require_once $notificationsFile;
}

/*
|--------------------------------------------------------------------------
| Current page
|--------------------------------------------------------------------------
*/

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

$scriptPath = str_replace(
    '\\',
    '/',
    $_SERVER['SCRIPT_NAME'] ?? ''
);

/*
|--------------------------------------------------------------------------
| Determine current area
|--------------------------------------------------------------------------
*/

$isAdminArea = str_contains($scriptPath, '/admin/');
$isCustomerArea = str_contains($scriptPath, '/customer/');

/*
|--------------------------------------------------------------------------
| Current user
|--------------------------------------------------------------------------
*/

$user = function_exists('current_user')
    ? current_user()
    : [];

$userName = $user['name'] ?? 'User';
$userEmail = $user['email'] ?? '';
$userRole = $user['role'] ?? '';

/*
|--------------------------------------------------------------------------
| Relative paths
|--------------------------------------------------------------------------
*/

if ($isAdminArea || $isCustomerArea) {
    $rootUrl = '../';
} else {
    $rootUrl = '';
}

/*
|--------------------------------------------------------------------------
| User initials
|--------------------------------------------------------------------------
*/

$nameParts = preg_split(
    '/\s+/',
    trim($userName)
);

$initials = '';

foreach ($nameParts as $part) {
    if ($part !== '') {
        $initials .= strtoupper(substr($part, 0, 1));
    }
}

$initials = substr($initials, 0, 2);

if ($initials === '') {
    $initials = 'US';
}

/*
|--------------------------------------------------------------------------
| Active navigation helper
|--------------------------------------------------------------------------
*/

if (!function_exists('nav_active')) {
    function nav_active(
        string $page,
        string $currentPage
    ): string {
        if ($page === $currentPage) {
            return 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/20';
        }

        return 'text-slate-300 hover:bg-slate-800 hover:text-white';
    }
}

/*
|--------------------------------------------------------------------------
| Customer notification count
|--------------------------------------------------------------------------
*/

$unreadNotificationCount = 0;

if (
    $isCustomerArea &&
    !empty($_SESSION['notifications']) &&
    is_array($_SESSION['notifications'])
) {
    foreach ($_SESSION['notifications'] as $notification) {

        if (
            is_array($notification) &&
            empty($notification['read'])
        ) {
            $unreadNotificationCount++;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Admin notification count
|--------------------------------------------------------------------------
*/

$adminUnreadNotificationCount = 0;

if (
    $isAdminArea &&
    function_exists('get_admin_unread_count')
) {
    $adminUnreadNotificationCount = get_admin_unread_count();
}

?>

<!-- ================================================================
     MOBILE OVERLAY
================================================================ -->

<div
    id="sidebarOverlay"
    class="fixed inset-0 z-40 hidden bg-black/50 lg:hidden"
></div>


<!-- ================================================================
     SIDEBAR
================================================================ -->

<aside
    id="sidebar"
    class="fixed inset-y-0 left-0 z-50 flex w-64
           -translate-x-full flex-col
           bg-slate-950 text-white
           transition-transform duration-300
           lg:translate-x-0"
>

    <!-- ============================================================
         LOGO
    ============================================================= -->

    <div
        class="flex h-16 shrink-0 items-center justify-between
               border-b border-slate-800 px-5"
    >

        <a
            href="<?= $isAdminArea || $isCustomerArea
                ? 'dashboard.php'
                : 'index.php' ?>"
            class="flex min-w-0 items-center gap-3"
        >

            <!-- Logo -->

            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center
                       rounded-lg bg-indigo-600 font-bold text-white"
            >
                P
            </div>

            <!-- Brand -->

            <div class="min-w-0">

                <div class="truncate font-bold text-white">
                    PropertyPro
                </div>

                <div class="truncate text-xs text-slate-400">

                    <?= $isAdminArea
                        ? 'Administration'
                        : ($isCustomerArea
                            ? 'Customer Portal'
                            : 'Property Management') ?>

                </div>

            </div>

        </a>


        <!-- Mobile Close Button -->

        <button
            id="closeSidebar"
            type="button"
            aria-label="Close sidebar"
            class="ml-2 shrink-0 text-2xl leading-none
                   text-slate-400 transition
                   hover:text-white lg:hidden"
        >
            &times;
        </button>

    </div>


    <!-- ============================================================
         USER INFORMATION
    ============================================================= -->

    <div
        class="shrink-0 border-b border-slate-800 px-4 py-4"
    >

        <div class="flex min-w-0 items-center gap-3">

            <!-- Avatar -->

            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center
                       rounded-full bg-indigo-600 font-semibold text-white"
            >
                <?= htmlspecialchars(
                    $initials,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>


            <!-- User Details -->

            <div class="min-w-0 flex-1">

                <p
                    class="truncate text-sm font-semibold text-white"
                    title="<?= htmlspecialchars(
                        $userName,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
                    <?= htmlspecialchars(
                        $userName,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>


                <p
                    class="truncate text-xs text-slate-400"
                    title="<?= htmlspecialchars(
                        $userRole,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
                    <?= htmlspecialchars(
                        $userRole,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

            </div>

        </div>

    </div>


    <!-- ============================================================
         NAVIGATION
    ============================================================= -->

    <nav
        class="min-h-0 flex-1 overflow-y-auto px-3 py-5"
    >

        <?php if ($isAdminArea): ?>

            <!-- ====================================================
                 ADMIN NAVIGATION
            ===================================================== -->

            <p
                class="mb-2 px-3 text-xs font-semibold
                       uppercase tracking-wider text-slate-500"
            >
                Administration
            </p>


            <div class="space-y-1">

                <!-- Dashboard -->

                <a
                    href="dashboard.php"
                    class="flex items-center gap-3 rounded-lg
                           px-3 py-3 text-sm font-medium transition
                           <?= nav_active(
                               'dashboard.php',
                               $currentPage
                           ) ?>"
                >
                    <span class="w-6 shrink-0 text-center">
                        📊
                    </span>

                    <span>
                        Dashboard
                    </span>
                </a>


                <!-- Notifications -->

                <a
                    href="notifications.php"
                    class="flex items-center gap-3 rounded-lg
                           px-3 py-3 text-sm font-medium transition
                           <?= nav_active(
                               'notifications.php',
                               $currentPage
                           ) ?>"
                >

                    <span class="w-6 shrink-0 text-center">
                        🔔
                    </span>

                    <span class="min-w-0 flex-1 truncate">
                        Notifications
                    </span>


                    <?php if ($adminUnreadNotificationCount > 0): ?>

                        <span
                            class="flex h-5 min-w-5 shrink-0
                                   items-center justify-center
                                   rounded-full bg-red-500 px-1.5
                                   text-[11px] font-bold text-white"
                        >
                            <?= $adminUnreadNotificationCount > 99
                                ? '99+'
                                : $adminUnreadNotificationCount ?>
                        </span>

                    <?php endif; ?>

                </a>


                <!-- Properties -->

                <a
                    href="properties.php"
                    class="flex items-center gap-3 rounded-lg
                           px-3 py-3 text-sm font-medium transition
                           <?= nav_active(
                               'properties.php',
                               $currentPage
                           ) ?>"
                >
                    <span class="w-6 shrink-0 text-center">
                        🏢
                    </span>

                    <span>
                        Properties
                    </span>
                </a>


                <!-- Units -->

                <a
                    href="units.php"
                    class="flex items-center gap-3 rounded-lg
                           px-3 py-3 text-sm font-medium transition
                           <?= nav_active(
                               'units.php',
                               $currentPage
                           ) ?>"
                >
                    <span class="w-6 shrink-0 text-center">
                        🚪
                    </span>

                    <span>
                        Units
                    </span>
                </a>


                <!-- Tenants -->

                <a
                    href="tenants.php"
                    class="flex items-center gap-3 rounded-lg
                           px-3 py-3 text-sm font-medium transition
                           <?= nav_active(
                               'tenants.php',
                               $currentPage
                           ) ?>"
                >
                    <span class="w-6 shrink-0 text-center">
                        👥
                    </span>

                    <span>
                        Tenants
                    </span>
                </a>


                <!-- Payments -->

                <a
                    href="payments.php"
                    class="flex items-center gap-3 rounded-lg
                           px-3 py-3 text-sm font-medium transition
                           <?= nav_active(
                               'payments.php',
                               $currentPage
                           ) ?>"
                >
                    <span class="w-6 shrink-0 text-center">
                        💳
                    </span>

                    <span>
                        Payments
                    </span>
                </a>


                <!-- Leases -->

                <a
                    href="leases.php"
                    class="flex items-center gap-3 rounded-lg
                           px-3 py-3 text-sm font-medium transition
                           <?= nav_active(
                               'leases.php',
                               $currentPage
                           ) ?>"
                >
                    <span class="w-6 shrink-0 text-center">
                        📄
                    </span>

                    <span>
                        Leases
                    </span>
                </a>


                <!-- Expenses -->

                <a
                    href="expenses.php"
                    class="flex items-center gap-3 rounded-lg
                           px-3 py-3 text-sm font-medium transition
                           <?= nav_active(
                               'expenses.php',
                               $currentPage
                           ) ?>"
                >
                    <span class="w-6 shrink-0 text-center">
                        💰
                    </span>

                    <span>
                        Expenses
                    </span>
                </a>


                <!-- Maintenance -->

                <a
                    href="maintenance.php"
                    class="flex items-center gap-3 rounded-lg
                           px-3 py-3 text-sm font-medium transition
                           <?= nav_active(
                               'maintenance.php',
                               $currentPage
                           ) ?>"
                >
                    <span class="w-6 shrink-0 text-center">
                        🔧
                    </span>

                    <span>
                        Maintenance
                    </span>
                </a>


                <!-- Reports -->

                <a
                    href="reports.php"
                    class="flex items-center gap-3 rounded-lg
                           px-3 py-3 text-sm font-medium transition
                           <?= nav_active(
                               'reports.php',
                               $currentPage
                           ) ?>"
                >
                    <span class="w-6 shrink-0 text-center">
                        📈
                    </span>

                    <span>
                        Reports
                    </span>
                </a>

            </div>


        <?php elseif ($isCustomerArea): ?>

            <!-- ====================================================
                 CUSTOMER NAVIGATION
            ===================================================== -->

            <p
                class="mb-2 px-3 text-xs font-semibold
                       uppercase tracking-wider text-slate-500"
            >
                My Rental
            </p>


            <div class="space-y-1">

                <!-- Dashboard -->

                <a
                    href="dashboard.php"
                    class="flex items-center gap-3 rounded-lg
                           px-3 py-3 text-sm font-medium transition
                           <?= nav_active(
                               'dashboard.php',
                               $currentPage
                           ) ?>"
                >
                    <span class="w-6 shrink-0 text-center">
                        📊
                    </span>

                    <span>
                        Dashboard
                    </span>
                </a>


                <!-- My Lease -->

                <a
                    href="leases.php"
                    class="flex items-center gap-3 rounded-lg
                           px-3 py-3 text-sm font-medium transition
                           <?= nav_active(
                               'leases.php',
                               $currentPage
                           ) ?>"
                >
                    <span class="w-6 shrink-0 text-center">
                        📄
                    </span>

                    <span>
                        My Lease
                    </span>
                </a>


                <!-- My Payments -->

                <a
                    href="payments.php"
                    class="flex items-center gap-3 rounded-lg
                           px-3 py-3 text-sm font-medium transition
                           <?= nav_active(
                               'payments.php',
                               $currentPage
                           ) ?>"
                >
                    <span class="w-6 shrink-0 text-center">
                        💳
                    </span>

                    <span>
                        My Payments
                    </span>
                </a>


                <!-- Maintenance -->

                <a
                    href="maintenance.php"
                    class="flex items-center gap-3 rounded-lg
                           px-3 py-3 text-sm font-medium transition
                           <?= nav_active(
                               'maintenance.php',
                               $currentPage
                           ) ?>"
                >
                    <span class="w-6 shrink-0 text-center">
                        🔧
                    </span>

                    <span class="min-w-0 flex-1 truncate">
                        Maintenance Requests
                    </span>
                </a>


                <!-- Notifications -->

                <a
                    href="notifications.php"
                    class="flex items-center gap-3 rounded-lg
                           px-3 py-3 text-sm font-medium transition
                           <?= nav_active(
                               'notifications.php',
                               $currentPage
                           ) ?>"
                >

                    <span class="w-6 shrink-0 text-center">
                        🔔
                    </span>

                    <span class="min-w-0 flex-1 truncate">
                        Notifications
                    </span>


                    <?php if ($unreadNotificationCount > 0): ?>

                        <span
                            class="flex h-5 min-w-5 shrink-0
                                   items-center justify-center
                                   rounded-full bg-red-500 px-1.5
                                   text-[11px] font-bold text-white"
                        >
                            <?= $unreadNotificationCount ?>
                        </span>

                    <?php endif; ?>

                </a>

            </div>

        <?php endif; ?>


        <!-- ========================================================
             ACCOUNT
        ========================================================= -->

        <div class="mt-8">

            <p
                class="mb-2 px-3 text-xs font-semibold
                       uppercase tracking-wider text-slate-500"
            >
                Account
            </p>


            <div class="space-y-1">

                <!-- Profile -->

                <a
                    href="<?= $isAdminArea || $isCustomerArea
                        ? 'profile.php'
                        : '#' ?>"
                    class="flex items-center gap-3 rounded-lg
                           px-3 py-3 text-sm font-medium transition
                           <?= nav_active(
                               'profile.php',
                               $currentPage
                           ) ?>"
                >

                    <span class="w-6 shrink-0 text-center">
                        👤
                    </span>

                    <span>
                        Profile
                    </span>

                </a>


                <!-- Settings -->

                <a
                    href="<?= $isAdminArea || $isCustomerArea
                        ? 'settings.php'
                        : '#' ?>"
                    class="flex items-center gap-3 rounded-lg
                           px-3 py-3 text-sm font-medium transition
                           <?= nav_active(
                               'settings.php',
                               $currentPage
                           ) ?>"
                >

                    <span class="w-6 shrink-0 text-center">
                        ⚙️
                    </span>

                    <span>
                        Settings
                    </span>

                </a>

            </div>

        </div>

    </nav>


    <!-- ============================================================
         LOGOUT
    ============================================================= -->

    <div
        class="shrink-0 border-t border-slate-800 p-3"
    >

        <a
            href="<?= $rootUrl ?>logout.php"
            class="flex w-full items-center gap-3 rounded-lg
                   px-3 py-3 text-sm font-medium
                   text-slate-300 transition
                   hover:bg-red-500/10 hover:text-red-400"
        >

            <span class="w-6 shrink-0 text-center">
                🚪
            </span>

            <span>
                Logout
            </span>

        </a>

    </div>

</aside>
```
