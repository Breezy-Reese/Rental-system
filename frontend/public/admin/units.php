<?php

require_once "../../includes/admin.php";
require_admin();

require_once "../../includes/data.php";

$pageTitle = "Units";

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
                    Units
                </h1>

                <p class="hidden text-xs text-slate-500 sm:block">
                    Manage rental units
                </p>
            </div>

        </div>

    </header>


    <div class="p-4 sm:p-6 lg:p-8">

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-slate-900">
                All Units
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Monitor unit occupancy and tenant assignments.
            </p>
        </div>


        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="overflow-x-auto">

                <table class="w-full text-left text-sm">

                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">

                        <tr>
                            <th class="px-6 py-4">Unit</th>
                            <th class="px-6 py-4">Property</th>
                            <th class="px-6 py-4">Tenant</th>
                            <th class="px-6 py-4">Rent</th>
                            <th class="px-6 py-4">Status</th>
                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        <?php foreach ($units as $unit): ?>

                            <tr class="hover:bg-slate-50">

                                <td class="px-6 py-4 font-semibold text-slate-900">
                                    <?= e($unit['unit']) ?>
                                </td>

                                <td class="px-6 py-4 text-slate-600">
                                    <?= e($unit['property']) ?>
                                </td>

                                <td class="px-6 py-4 text-slate-600">
                                    <?= e($unit['tenant'] ?? 'Vacant') ?>
                                </td>

                                <td class="px-6 py-4 font-semibold text-slate-900">
                                    <?= money($unit['rent'] ?? 0) ?>
                                </td>

                                <td class="px-6 py-4">

                                    <?php if (($unit['status'] ?? '') === 'Occupied'): ?>

                                        <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                                            Occupied
                                        </span>

                                    <?php else: ?>

                                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-700">
                                            Vacant
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</main>

<?php require_once "../../includes/footer.php"; ?>