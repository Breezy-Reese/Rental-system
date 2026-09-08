<?php

require_once "../../includes/admin.php";
require_admin();

require_once "../../includes/data.php";

$pageTitle = "Leases";

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
                    Leases
                </h1>

                <p class="hidden text-xs text-slate-500 sm:block">
                    Manage tenant leases
                </p>
            </div>

            <a
                href="#"
                class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                + New Lease
            </a>

        </div>

    </header>


    <div class="p-4 sm:p-6 lg:p-8">

        <div class="mb-6">

            <h2 class="text-2xl font-bold text-slate-900">
                Lease Agreements
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Manage active and expired rental agreements.
            </p>

        </div>


        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">

            <?php foreach ($leases as $lease): ?>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="flex items-center justify-between">

                        <span class="font-semibold text-slate-900">
                            <?= e($lease['id']) ?>
                        </span>

                        <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700">
                            Active
                        </span>

                    </div>

                    <div class="mt-5 space-y-3 text-sm">

                        <div>
                            <p class="text-xs text-slate-500">Tenant</p>
                            <p class="font-medium text-slate-900">
                                <?= e($lease['tenant']) ?>
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-slate-500">Property</p>
                            <p class="font-medium text-slate-900">
                                <?= e($lease['property']) ?>
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-slate-500">Unit</p>
                            <p class="font-medium text-slate-900">
                                <?= e($lease['unit']) ?>
                            </p>
                        </div>

                    </div>

                    <div class="mt-5 border-t border-slate-100 pt-4">

                        <a
                            href="#"
                            class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                            View Lease →
                        </a>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

</main>

<?php require_once "../../includes/footer.php"; ?>