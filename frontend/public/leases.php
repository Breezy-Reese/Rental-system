<?php

$pageTitle = "Leases";

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
                Leases
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Manage tenant leases and expiry dates.
            </p>
        </div>

        <button
            class="rounded-lg bg-primary-600 px-4 py-2.5
                   text-sm font-semibold text-white
                   hover:bg-primary-700">

            + Create Lease

        </button>

    </div>


    <div class="overflow-hidden rounded-xl border bg-white">

        <div class="border-b p-5">

            <input
                type="text"
                placeholder="Search leases..."
                class="rounded-lg border border-slate-300
                       px-4 py-2.5 text-sm md:w-80">

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-left text-sm">

                <thead class="bg-slate-50 text-xs uppercase
                              text-slate-500">

                    <tr>

                        <th class="px-6 py-4">Lease</th>
                        <th class="px-6 py-4">Tenant</th>
                        <th class="px-6 py-4">Unit</th>
                        <th class="px-6 py-4">Start</th>
                        <th class="px-6 py-4">End</th>
                        <th class="px-6 py-4">Rent</th>
                        <th class="px-6 py-4">Status</th>

                    </tr>

                </thead>


                <tbody class="divide-y">

                    <tr class="hover:bg-slate-50">

                        <td class="px-6 py-4 font-medium">
                            LS-00124
                        </td>

                        <td class="px-6 py-4">
                            John Mwangi
                        </td>

                        <td class="px-6 py-4">
                            A-101
                        </td>

                        <td class="px-6 py-4">
                            Jan 1, 2026
                        </td>

                        <td class="px-6 py-4">
                            Dec 31, 2026
                        </td>

                        <td class="px-6 py-4 font-medium">
                            KSh 25,000
                        </td>

                        <td class="px-6 py-4">

                            <span class="rounded-full bg-emerald-50
                                         px-3 py-1 text-xs
                                         font-medium text-emerald-700">
                                Active
                            </span>

                        </td>

                    </tr>


                    <tr class="hover:bg-slate-50">

                        <td class="px-6 py-4 font-medium">
                            LS-00125
                        </td>

                        <td class="px-6 py-4">
                            Mary Wanjiku
                        </td>

                        <td class="px-6 py-4">
                            B-204
                        </td>

                        <td class="px-6 py-4">
                            Mar 1, 2026
                        </td>

                        <td class="px-6 py-4">
                            Feb 28, 2027
                        </td>

                        <td class="px-6 py-4 font-medium">
                            KSh 35,000
                        </td>

                        <td class="px-6 py-4">

                            <span class="rounded-full bg-emerald-50
                                         px-3 py-1 text-xs
                                         font-medium text-emerald-700">
                                Active
                            </span>

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </div>

</div>

</main>

<?php require_once "../includes/footer.php"; ?>