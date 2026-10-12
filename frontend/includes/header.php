<?php

/**
 * ============================================================
 * PropertyPro - Main Header
 * ============================================================
 */

$pageTitle = $pageTitle ?? 'PropertyPro';
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

/*
|--------------------------------------------------------------------------
| Pages that do NOT use the sidebar
|--------------------------------------------------------------------------
*/

$publicPages = [
    'login.php',
    'register.php',
];

/*
|--------------------------------------------------------------------------
| Determine whether the mobile menu should be displayed
|--------------------------------------------------------------------------
*/

$showMobileMenu = !in_array($currentPage, $publicPages, true);

?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <!-- ========================================================
         PropertyPro PWA Configuration
    ========================================================= -->

    <meta name="theme-color" content="#4f46e5">

    <meta
        name="description"
        content="PropertyPro - Manage rental properties, tenants, leases and payments."
    >

    <meta name="mobile-web-app-capable" content="yes">

    <meta name="apple-mobile-web-app-capable" content="yes">

    <meta
        name="apple-mobile-web-app-status-bar-style"
        content="default"
    >

    <meta
        name="apple-mobile-web-app-title"
        content="PropertyPro"
    >

    <link
        rel="manifest"
        href="/manifest.json"
    >

    <link
        rel="icon"
        href="/assets/images/propertypro-icon.svg"
        type="image/svg+xml"
    >

    <link
        rel="apple-touch-icon"
        href="/assets/images/propertypro-icon.svg"
    >

    <title>
        <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>
        | PropertyPro
    </title>

    <!-- ========================================================
         Tailwind CSS
    ========================================================= -->

    <script src="https://cdn.tailwindcss.com"></script>

    <!-- ========================================================
         Tailwind Configuration
    ========================================================= -->

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3'
                        }
                    }
                }
            }
        };
    </script>

    <!-- ========================================================
         PropertyPro Service Worker
    ========================================================= -->

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker
                    .register('/service-worker.js')
                    .catch(function (error) {
                        console.error(
                            'PropertyPro service worker registration failed:',
                            error
                        );
                    });
            });
        }
    </script>

</head>

<body class="bg-slate-50 text-slate-800">

<!-- ============================================================
     MOBILE TOP BAR
============================================================= -->

<?php if ($showMobileMenu): ?>

<div
    class="sticky top-0 z-30 flex h-16 items-center
           border-b border-slate-200 bg-white px-4
           shadow-sm lg:hidden"
>

    <!-- Mobile Menu Button -->

    <button
        id="mobileMenuButton"
        type="button"
        aria-label="Open navigation menu"
        aria-controls="sidebar"
        aria-expanded="false"
        class="flex h-10 w-10 items-center justify-center
               rounded-lg text-slate-700
               transition hover:bg-slate-100
               focus:outline-none focus:ring-2
               focus:ring-indigo-500"
    >

        <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
            aria-hidden="true"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M4 6h16M4 12h16M4 18h16"
            />
        </svg>

    </button>

    <!-- Page Title -->

    <div class="ml-3 min-w-0">

        <h1 class="truncate text-lg font-semibold text-slate-900">
            <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>
        </h1>

    </div>

</div>

<?php endif; ?>

<!-- ============================================================
     MAIN PAGE WRAPPER
============================================================= -->

<div class="min-h-screen">
