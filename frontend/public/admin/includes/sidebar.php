<?php

$currentPage = basename($_SERVER['PHP_SELF']);

/*
|--------------------------------------------------------------------------
| Detect whether current page is inside /admin/
|--------------------------------------------------------------------------
*/
$isAdminArea = str_contains(
    str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''),
    '/admin/'
);

/*
|--------------------------------------------------------------------------
| Base URL for navigation
|--------------------------------------------------------------------------
*/
$baseUrl = $isAdminArea ? '' : 'admin/';

/*
|--------------------------------------------------------------------------
| Current User
|--------------------------------------------------------------------------
*/
$user = function_exists('current_user')
    ? current_user()
    : [
        'name' => 'Property Manager',
        'email' => 'admin@example.com',
        'role' => 'Administrator'
    ];

$userName = $user['name'] ?? 'Property Manager';
$userRole = $user['role'] ?? 'Administrator';

$nameParts = preg_split('/\s+/', trim($userName));

$initials = '';

foreach ($nameParts as $part) {
    if ($part !== '') {
        $initials .= strtoupper(substr($part, 0, 1));
    }
}

$initials = substr($initials, 0, 2);

if ($initials === '') {
    $initials = 'BM';
}


/*
|--------------------------------------------------------------------------
| Navigation helper
|--------------------------------------------------------------------------
*/
function nav_active(string $page, string $currentPage): string
{
    return $page === $currentPage
        ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/20'
        : 'text-slate-300 hover:bg-slate-800 hover:text-white';
}

?>

<!-- Mobile Overlay -->
<div
    id="sidebarOverlay"
    class="fixed inset-0 z-40 hidden bg-black/50 lg:hidden">
</div>


<!-- Sidebar -->
<aside
    id="sidebar"
    class="fixed inset-y-0 left-0 z-50 flex w-64
           -translate-x-full lg:translate-x-0
           flex-col bg-slate-950 text-white
           transition-transform duration-300 ease-in-out">

    <!-- Logo -->
    <div class="flex h-16 shrink-0 items-center border-b border-slate-800 px-5">

        <a
            href="<?= $baseUrl ?>dashboard.php"
            class="flex items-center gap-3">

            <div
                class="flex h-10 w-10 items-center justify-center
                       rounded-xl bg-indigo-600 text-lg font-bold">
                P
            </div>

            <div>

                <div class="text-lg font-bold">
                    PropertyPro
                </div>

                <div class="text-xs text-slate-400">
                    Property Management
                </div>

            </div>

        </a>


        <!-- Mobile Close -->
        <button
            id="closeSidebar"
            type="button"
            class="ml-auto rounded-lg p-2 text-slate-400
                   hover:bg-slate-800 hover:text-white lg:hidden">

            ✕

        </button>

    </div>


    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto px-3 py-5">

        <!-- Main -->
        <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
            Main
        </p>


        <div class="space-y-1">

            <!-- Dashboard -->
            <a
                href="<?= $baseUrl ?>dashboard.php"
                class="<?= nav_active('dashboard.php', $currentPage) ?>
                       flex items-center gap-3 rounded-lg px-3 py-3
                       text-sm font-medium transition">

                <span class="w-6 text-center">
                    📊
                </span>

                <span>
                    Dashboard
                </span>

            </a>


            <!-- Properties -->
            <a
                href="<?= $baseUrl ?>properties.php"
                class="<?= nav_active('properties.php', $currentPage) ?>
                       flex items-center gap-3 rounded-lg px-3 py-3
                       text-sm font-medium transition">

                <span class="w-6 text-center">
                    🏢
                </span>

                <span>
                    Properties
                </span>

            </a>


            <!-- Units -->
            <a
                href="<?= $baseUrl ?>units.php"
                class="<?= nav_active('units.php', $currentPage) ?>
                       flex items-center gap-3 rounded-lg px-3 py-3
                       text-sm font-medium transition">

                <span class="w-6 text-center">
                    🚪
                </span>

                <span>
                    Units
                </span>

            </a>


            <!-- Tenants -->
            <a
                href="<?= $baseUrl ?>tenants.php"
                class="<?= nav_active('tenants.php', $currentPage) ?>
                       flex items-center gap-3 rounded-lg px-3 py-3
                       text-sm font-medium transition">

                <span class="w-6 text-center">
                    👥
                </span>

                <span>
                    Tenants
                </span>

            </a>


            <!-- Payments -->
            <a
                href="<?= $baseUrl ?>payments.php"
                class="<?= nav_active('payments.php', $currentPage) ?>
                       flex items-center gap-3 rounded-lg px-3 py-3
                       text-sm font-medium transition">

                <span class="w-6 text-center">
                    💰
                </span>

                <span>
                    Payments
                </span>

            </a>


            <!-- Leases -->
            <a
                href="<?= $baseUrl ?>leases.php"
                class="<?= nav_active('leases.php', $currentPage) ?>
                       flex items-center gap-3 rounded-lg px-3 py-3
                       text-sm font-medium transition">

                <span class="w-6 text-center">
                    📄
                </span>

                <span>
                    Leases
                </span>

            </a>


            <!-- Expenses -->
            <a
                href="<?= $baseUrl ?>expenses.php"
                class="<?= nav_active('expenses.php', $currentPage) ?>
                       flex items-center gap-3 rounded-lg px-3 py-3
                       text-sm font-medium transition">

                <span class="w-6 text-center">
                    💳
                </span>

                <span>
                    Expenses
                </span>

            </a>


            <!-- Maintenance -->
            <a
                href="<?= $baseUrl ?>maintenance.php"
                class="<?= nav_active('maintenance.php', $currentPage) ?>
                       flex items-center gap-3 rounded-lg px-3 py-3
                       text-sm font-medium transition">

                <span class="w-6 text-center">
                    🔧
                </span>

                <span>
                    Maintenance
                </span>

            </a>


            <!-- Reports -->
            <a
                href="<?= $baseUrl ?>reports.php"
                class="<?= nav_active('reports.php', $currentPage) ?>
                       flex items-center gap-3 rounded-lg px-3 py-3
                       text-sm font-medium transition">

                <span class="w-6 text-center">
                    📈
                </span>

                <span>
                    Reports
                </span>

            </a>

        </div>


        <!-- Account -->
        <p class="mb-2 mt-8 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
            Account
        </p>


        <div class="space-y-1">

            <!-- Profile -->
            <a
                href="<?= $baseUrl ?>profile.php"
                class="<?= nav_active('profile.php', $currentPage) ?>
                       flex items-center gap-3 rounded-lg px-3 py-3
                       text-sm font-medium transition">

                <span class="w-6 text-center">
                    👤
                </span>

                <span>
                    Profile
                </span>

            </a>


            <!-- Settings -->
            <a
                href="<?= $baseUrl ?>settings.php"
                class="<?= nav_active('settings.php', $currentPage) ?>
                       flex items-center gap-3 rounded-lg px-3 py-3
                       text-sm font-medium transition">

                <span class="w-6 text-center">
                    ⚙️
                </span>

                <span>
                    Settings
                </span>

            </a>

        </div>

    </nav>


    <!-- User Section -->
    <div class="shrink-0 border-t border-slate-800 p-3">

        <div class="mb-2 flex items-center gap-3 rounded-lg bg-slate-900 p-3">

            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center
                       rounded-full bg-indigo-600 font-semibold">

                <?= htmlspecialchars($initials) ?>

            </div>


            <div class="min-w-0">

                <p class="truncate text-sm font-semibold text-white">
                    <?= htmlspecialchars($userName) ?>
                </p>

                <p class="truncate text-xs text-slate-400">
                    <?= htmlspecialchars($userRole) ?>
                </p>

            </div>

        </div>


        <!-- Logout -->
        <a
            href="<?= $isAdminArea ? '../logout.php' : 'logout.php' ?>"
            class="flex w-full items-center gap-3 rounded-lg
                   px-3 py-3 text-sm font-medium
                   text-slate-300 transition
                   hover:bg-red-500/10 hover:text-red-400">

            <span class="w-6 text-center">
                🚪
            </span>

            <span>
                Logout
            </span>

        </a>

    </div>

</aside>