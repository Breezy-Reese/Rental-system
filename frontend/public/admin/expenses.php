<?php

require_once "../../includes/admin.php";
require_admin();

require_once "../../includes/data.php";

$pageTitle = "Expenses";

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
                    Expenses
                </h1>

                <p class="hidden text-xs text-slate-500 sm:block">
                    Track property expenses
                </p>
            </div>

            <a
                href="#"
                class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                + Add Expense
            </a>

        </div>

    </header>


    <div class="p-4 sm:p-6 lg:p-8">

        <div class="mb-6">

            <h2 class="text-2xl font-bold text-slate-900">
                Expense Records
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Monitor property maintenance and operating expenses.
            </p>

        </div>


        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="overflow-x-auto">

                <table class="w-full text-left text-sm">

                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">

                        <tr>
                            <th class="px-6 py-4">Reference</th>
                            <th class="px-6 py-4">Description</th>
                            <th class="px-6 py-4">Property</th>
                            <th class="px-6 py-4">Amount</th>
                            <th class="px-6 py-4">Date</th>
                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        <?php foreach ($expenses as $expense): ?>

                            <tr class="hover:bg-slate-50">

                                <td class="px-6 py-4 font-semibold text-slate-900">
                                    <?= e($expense['id']) ?>
                                </td>

                                <td class="px-6 py-4 text-slate-600">
                                    <?= e($expense['description']) ?>
                                </td>

                                <td class="px-6 py-4 text-slate-600">
                                    <?= e($expense['property']) ?>
                                </td>

                                <td class="px-6 py-4 font-semibold text-red-600">
                                    <?= money($expense['amount']) ?>
                                </td>

                                <td class="px-6 py-4 text-slate-500">
                                    <?= e($expense['date'] ?? '-') ?>
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