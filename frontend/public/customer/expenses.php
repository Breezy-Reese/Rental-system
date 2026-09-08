<?php

$pageTitle = "Expenses";

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
                Expenses
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Track property-related expenses.
            </p>

        </div>

        <button
            class="rounded-lg bg-primary-600 px-4 py-2.5
                   text-sm font-semibold text-white">

            + Add Expense

        </button>

    </div>


    <div class="mb-6 grid gap-4 sm:grid-cols-3">

        <div class="rounded-xl border bg-white p-5">

            <p class="text-sm text-slate-500">
                This Month
            </p>

            <p class="mt-2 text-2xl font-bold">
                KSh 186,500
            </p>

        </div>


        <div class="rounded-xl border bg-white p-5">

            <p class="text-sm text-slate-500">
                Maintenance
            </p>

            <p class="mt-2 text-2xl font-bold">
                KSh 94,000
            </p>

        </div>


        <div class="rounded-xl border bg-white p-5">

            <p class="text-sm text-slate-500">
                Other Expenses
            </p>

            <p class="mt-2 text-2xl font-bold">
                KSh 92,500
            </p>

        </div>

    </div>


    <div class="overflow-hidden rounded-xl border bg-white">

        <div class="overflow-x-auto">

            <table class="w-full text-left text-sm">

                <thead class="bg-slate-50 text-xs uppercase
                              text-slate-500">

                    <tr>

                        <th class="px-6 py-4">Description</th>
                        <th class="px-6 py-4">Property</th>
                        <th class="px-6 py-4">Category</th>
                        <th class="px-6 py-4">Amount</th>
                        <th class="px-6 py-4">Date</th>

                    </tr>

                </thead>


                <tbody class="divide-y">

                    <tr class="hover:bg-slate-50">

                        <td class="px-6 py-4 font-medium">
                            Plumbing repairs
                        </td>

                        <td class="px-6 py-4">
                            Greenview Apartments
                        </td>

                        <td class="px-6 py-4">
                            Maintenance
                        </td>

                        <td class="px-6 py-4 font-semibold">
                            KSh 35,000
                        </td>

                        <td class="px-6 py-4">
                            Sep 7, 2026
                        </td>

                    </tr>


                    <tr class="hover:bg-slate-50">

                        <td class="px-6 py-4 font-medium">
                            Security services
                        </td>

                        <td class="px-6 py-4">
                            Sunrise Estate
                        </td>

                        <td class="px-6 py-4">
                            Security
                        </td>

                        <td class="px-6 py-4 font-semibold">
                            KSh 50,000
                        </td>

                        <td class="px-6 py-4">
                            Sep 5, 2026
                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </div>

</div>

</main>

<?php require_once "../includes/footer.php"; ?>