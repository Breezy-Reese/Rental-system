<?php
$pageTitle = $pageTitle ?? 'PropertyPro';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | PropertyPro</title>

    <script src="https://cdn.tailwindcss.com"></script>

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
</head>

<body class="bg-slate-50 text-slate-800">

<!-- ============================================================
     MOBILE TOP BAR
============================================================= -->

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
        <!-- Hamburger icon -->
        <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M4 6h16M4 12h16M4 18h16"
            />
        </svg>
    </button>

    <!-- Page title -->
    <div class="ml-3 min-w-0">
        <h1 class="truncate text-lg font-semibold text-slate-900">
            <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>
        </h1>
    </div>

</div>


<!-- ============================================================
     MAIN PAGE WRAPPER
============================================================= -->

<div class="min-h-screen">
