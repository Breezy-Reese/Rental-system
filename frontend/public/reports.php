<?php

$pageTitle = "Reports";

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

?>

<main class="flex-1 lg:ml-64">

<?php require_once "../includes/navbar.php"; ?>

<div class="p-4 sm:p-6 lg:p-8">

    <div class="mb-8">

        <h1 class="text-2xl font-bold">
            Reports & Analytics
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Monitor your property portfolio performance.
        </p>

    </div>


    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">

        <a href="#"
           class="group rounded-xl border bg-white p-6
                  shadow-sm transition hover:-translate-y-1
                  hover:shadow-md">

            <div class="flex h-12 w-12 items-center justify-center
                        rounded-xl bg-primary-50 text-2xl">

                💰

            </div>

            <h2 class="mt-5 font-semibold">
                Rent Collection Report
            </h2>

            <p class="mt-2 text-sm text-slate-500">
                View collected and outstanding rent.
            </p>

            <p class="mt-4 text-sm font-medium text-primary-600">
                View report →
            </p>

        </a>


        <a href="#"
           class="group rounded-xl border bg-white p-6
                  shadow-sm transition hover:-translate-y-1
                  hover:shadow-md">

            <div class="flex h-12 w-12 items-center justify-center
                        rounded-xl bg-emerald-50 text-2xl">

                🏢

            </div>

            <h2 class="mt-5 font-semibold">
                Occupancy Report
            </h2>

            <p class="mt-2 text-sm text-slate-500">
                Analyze occupied and vacant units.
            </p>

            <p class="mt-4 text-sm font-medium text-primary-600">
                View report →
            </p>

        </a>


        <a href="#"
           class="group rounded-xl border bg-white p-6
                  shadow-sm transition hover:-translate-y-1
                  hover:shadow-md">

            <div class="flex h-12 w-12 items-center justify-center
                        rounded-xl bg-red-50 text-2xl">

                📉

            </div>

            <h2 class="mt-5 font-semibold">
                Expense Report
            </h2>

            <p class="mt-2 text-sm text-slate-500">
                Track expenses and property costs.
            </p>

            <p class="mt-4 text-sm font-medium text-primary-600">
                View report →
            </p>

        </a>


        <a href="#"
           class="group rounded-xl border bg-white p-6
                  shadow-sm transition hover:-translate-y-1
                  hover:shadow-md">

            <div class="flex h-12 w-12 items-center justify-center
                        rounded-xl bg-blue-50 text-2xl">

                👥

            </div>

            <h2 class="mt-5 font-semibold">
                Tenant Report
            </h2>

            <p class="mt-2 text-sm text-slate-500">
                View tenant activity and history.
            </p>

            <p class="mt-4 text-sm font-medium text-primary-600">
                View report →
            </p>

        </a>

    </div>

</div>

</main>

<?php require_once "../includes/footer.php"; ?>