<?php

require_once "../../includes/admin.php";
require_admin();

require_once "../../includes/data.php";

$pageTitle = "Reports";

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
?>

<main class="min-h-screen bg-slate-50 lg:ml-64">

    <header class="border-b border-slate-200 bg-white">

        <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">

            <button
                id="mobileMenuButton"
                type="button"
                class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden">
                ☰
            </button>

            <div>
                <h1 class="text-lg font-semibold text-slate-900">
                    Reports
                </h1>

                <p class="hidden text-xs text-slate-500 sm:block">
                    Property management reports
                </p>
            </div>

        </div>

    </header>


    <div class="p-4 sm:p-6 lg:p-8">

        <div class="mb-8">

            <h2 class="text-2xl font-bold text-slate-900">
                Reports & Analytics
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Review your property's financial and operational performance.
            </p>

        </div>


        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">

            <a
                href="#"
                class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">

                <div class="mb-4 text-3xl">
                    💰
                </div>

                <h3 class="font-semibold text-slate-900">
                    Rent Collection
                </h3>

                <p class="mt-2 text-sm text-slate-500">
                    View rent collection and outstanding balances.
                </p>

            </a>


            <a
                href="#"
                class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">

                <div class="mb-4 text-3xl">
                    🏢
                </div>

                <h3 class="font-semibold text-slate-900">
                    Property Performance
                </h3>

                <p class="mt-2 text-sm text-slate-500">
                    Analyze occupancy and property performance.
                </p>

            </a>


            <a
                href="#"
                class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">

                <div class="mb-4 text-3xl">
                    📊
                </div>

                <h3 class="font-semibold text-slate-900">
                    Financial Summary
                </h3>

                <p class="mt-2 text-sm text-slate-500">
                    Review income, expenses and net revenue.
                </p>

            </a>


            <a
                href="#"
                class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">

                <div class="mb-4 text-3xl">
                    👥
                </div>

                <h3 class="font-semibold text-slate-900">
                    Tenant Report
                </h3>

                <p class="mt-2 text-sm text-slate-500">
                    Review tenant occupancy and lease information.
                </p>

            </a>


            <a
                href="#"
                class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">

                <div class="mb-4 text-3xl">
                    🔧
                </div>

                <h3 class="font-semibold text-slate-900">
                    Maintenance Report
                </h3>

                <p class="mt-2 text-sm text-slate-500">
                    Review maintenance requests and expenses.
                </p>

            </a>


            <a
                href="#"
                class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">

                <div class="mb-4 text-3xl">
                    📄
                </div>

                <h3 class="font-semibold text-slate-900">
                    Lease Report
                </h3>

                <p class="mt-2 text-sm text-slate-500">
                    Review active and expiring leases.
                </p>

            </a>

        </div>

    </div>

</main>

<?php require_once "../../includes/footer.php"; ?>