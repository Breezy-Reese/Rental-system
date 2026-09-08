<?php

$pageTitle = "Maintenance";

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

?>

<main class="flex-1 lg:ml-64">

<?php require_once "../includes/navbar.php"; ?>

<div class="p-4 sm:p-6 lg:p-8">

    <div class="mb-6 flex flex-col justify-between gap-4
                sm:flex-row sm:items-center">

        <div>
            <h1 class="text-2xl font-bold">
                Maintenance
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Track property maintenance requests.
            </p>
        </div>

        <button
            class="rounded-lg bg-primary-600 px-4 py-2.5
                   text-sm font-semibold text-white">

            + New Request

        </button>

    </div>


    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">


        <div class="rounded-xl border bg-white p-5 shadow-sm">

            <div class="flex items-start justify-between">

                <div class="flex h-10 w-10 items-center
                            justify-center rounded-lg bg-red-50">

                    🔧

                </div>

                <span class="rounded-full bg-red-50 px-3 py-1
                             text-xs font-medium text-red-700">

                    Urgent

                </span>

            </div>


            <h3 class="mt-4 font-semibold">
                Broken water pipe
            </h3>

            <p class="mt-1 text-sm text-slate-500">
                Greenview Apartments • Unit A-204
            </p>

            <div class="mt-5 border-t pt-4">

                <div class="flex justify-between text-sm">

                    <span class="text-slate-500">
                        Reported by
                    </span>

                    <span class="font-medium">
                        John Mwangi
                    </span>

                </div>

                <div class="mt-2 flex justify-between text-sm">

                    <span class="text-slate-500">
                        Date
                    </span>

                    <span>
                        Sep 7, 2026
                    </span>

                </div>

            </div>

            <button
                class="mt-5 w-full rounded-lg bg-slate-100
                       px-4 py-2 text-sm font-medium
                       hover:bg-slate-200">

                View Request

            </button>

        </div>


        <div class="rounded-xl border bg-white p-5 shadow-sm">

            <div class="flex items-start justify-between">

                <div class="flex h-10 w-10 items-center
                            justify-center rounded-lg bg-amber-50">

                    💡

                </div>

                <span class="rounded-full bg-amber-50 px-3 py-1
                             text-xs font-medium text-amber-700">

                    Pending

                </span>

            </div>


            <h3 class="mt-4 font-semibold">
                Faulty electricity
            </h3>

            <p class="mt-1 text-sm text-slate-500">
                Sunrise Estate • Unit B-112
            </p>

            <div class="mt-5 border-t pt-4">

                <div class="flex justify-between text-sm">

                    <span class="text-slate-500">
                        Reported by
                    </span>

                    <span class="font-medium">
                        Mary Wanjiku
                    </span>

                </div>

                <div class="mt-2 flex justify-between text-sm">

                    <span class="text-slate-500">
                        Date
                    </span>

                    <span>
                        Sep 6, 2026
                    </span>

                </div>

            </div>

            <button
                class="mt-5 w-full rounded-lg bg-slate-100
                       px-4 py-2 text-sm font-medium
                       hover:bg-slate-200">

                View Request

            </button>

        </div>


        <div class="rounded-xl border bg-white p-5 shadow-sm">

            <div class="flex items-start justify-between">

                <div class="flex h-10 w-10 items-center
                            justify-center rounded-lg bg-blue-50">

                    🚿

                </div>

                <span class="rounded-full bg-blue-50 px-3 py-1
                             text-xs font-medium text-blue-700">

                    Assigned

                </span>

            </div>


            <h3 class="mt-4 font-semibold">
                Leaking shower
            </h3>

            <p class="mt-1 text-sm text-slate-500">
                Palm Heights • Unit 12
            </p>

            <div class="mt-5 border-t pt-4">

                <div class="flex justify-between text-sm">

                    <span class="text-slate-500">
                        Technician
                    </span>

                    <span class="font-medium">
                        David Repairs
                    </span>

                </div>

                <div class="mt-2 flex justify-between text-sm">

                    <span class="text-slate-500">
                        Date
                    </span>

                    <span>
                        Sep 5, 2026
                    </span>

                </div>

            </div>

            <button
                class="mt-5 w-full rounded-lg bg-slate-100
                       px-4 py-2 text-sm font-medium">

                View Request

            </button>

        </div>

    </div>

</div>

</main>

<?php require_once "../includes/footer.php"; ?>