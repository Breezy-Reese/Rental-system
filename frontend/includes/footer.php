<?php
/**
 * ============================================================
 * PropertyPro Footer
 * ============================================================
 *
 * Shared footer for admin and customer pages.
 *
 * Loads page-specific JavaScript only when the actual
 * JavaScript file exists.
 * ============================================================
 */

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
?>

</main>

<footer class="border-t border-slate-200 bg-white px-4 py-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="flex flex-col items-center justify-between gap-2 text-center text-sm text-slate-500 sm:flex-row sm:text-left">

            <p>
                &copy; <?= date('Y') ?> PropertyPro. All rights reserved.
            </p>

            <p>
                Property &amp; Rental Management System
            </p>

        </div>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Mobile Sidebar
    |--------------------------------------------------------------------------
    */

    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    const openButtons = [
        document.getElementById('mobileMenuButton'),
        document.getElementById('openSidebar')
    ].filter(Boolean);

    const closeButtons = [
        document.getElementById('closeSidebar')
    ].filter(Boolean);

    function openSidebar() {
        if (!sidebar) {
            return;
        }

        sidebar.classList.remove('-translate-x-full');

        if (overlay) {
            overlay.classList.remove('hidden');
        }

        openButtons.forEach(function (button) {
            button.setAttribute('aria-expanded', 'true');
        });

        document.body.classList.add('overflow-hidden');
    }

    function closeSidebar() {
        if (!sidebar) {
            return;
        }

        sidebar.classList.add('-translate-x-full');

        if (overlay) {
            overlay.classList.add('hidden');
        }

        openButtons.forEach(function (button) {
            button.setAttribute('aria-expanded', 'false');
        });

        document.body.classList.remove('overflow-hidden');
    }

    openButtons.forEach(function (button) {
        button.addEventListener('click', openSidebar);
    });

    closeButtons.forEach(function (button) {
        button.addEventListener('click', closeSidebar);
    });

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Close sidebar after clicking a navigation link on mobile
    |--------------------------------------------------------------------------
    */

    if (sidebar) {
        sidebar.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth < 1024) {
                    closeSidebar();
                }
            });
        });
    }

});
</script>

<?php
/*
|--------------------------------------------------------------------------
| Page-specific JavaScript
|--------------------------------------------------------------------------
|
| The PHP filename is mapped to the correct .js file.
|
| Example:
|
| tenants.php
|     ↓
| /assets/js/tenants.js
|
| The file is checked on the SERVER before the browser
| receives the script tag.
|--------------------------------------------------------------------------
*/

$scriptMap = [
    'dashboard.php'  => 'dashboard.js',
    'payments.php'   => 'payments.js',
    'properties.php' => 'properties.js',
    'tenants.php'    => 'tenants.js',
];

$scriptFile = $scriptMap[$currentPage] ?? null;

if ($scriptFile !== null) {

    /*
    |--------------------------------------------------------------------------
    | Physical file check
    |--------------------------------------------------------------------------
    */

    $scriptPath = __DIR__ . '/../assets/js/' . $scriptFile;

    if (is_file($scriptPath)) {
        ?>
        <script
            src="/assets/js/<?= htmlspecialchars($scriptFile, ENT_QUOTES, 'UTF-8') ?>"
        ></script>
        <?php
    }
}
?>

</body>
</html>