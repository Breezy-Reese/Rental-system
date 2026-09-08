<?php

$pageTitle = "Settings";

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

?>

<main class="flex-1 lg:ml-64">

<?php require_once "../includes/navbar.php"; ?>

<div class="mx-auto max-w-5xl p-4 sm:p-6 lg:p-8">

    <div class="space-y-6">


        <!-- Business -->

        <section class="rounded-xl border bg-white">

            <div class="border-b p-6">

                <h2 class="font-semibold">
                    Business Information
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Configure your property management business.
                </p>

            </div>


            <div class="grid gap-5 p-6 sm:grid-cols-2">

                <div>

                    <label class="mb-2 block text-sm font-medium">
                        Business name
                    </label>

                    <input
                        type="text"
                        value="PropertyPro Management"
                        class="w-full rounded-lg border
                               border-slate-300 px-4 py-3 text-sm">

                </div>


                <div>

                    <label class="mb-2 block text-sm font-medium">
                        Currency
                    </label>

                    <select
                        class="w-full rounded-lg border
                               border-slate-300 px-4 py-3 text-sm">

                        <option>KES - Kenyan Shilling</option>
                        <option>USD - US Dollar</option>
                        <option>EUR - Euro</option>

                    </select>

                </div>

            </div>

        </section>


        <!-- Notifications -->

        <section class="rounded-xl border bg-white">

            <div class="border-b p-6">

                <h2 class="font-semibold">
                    Notifications
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Configure system notifications.
                </p>

            </div>


            <div class="space-y-5 p-6">

                <label class="flex items-center justify-between">

                    <div>

                        <p class="text-sm font-medium">
                            Rent reminders
                        </p>

                        <p class="text-xs text-slate-500">
                            Notify tenants about upcoming rent.
                        </p>

                    </div>

                    <input type="checkbox"
                           checked
                           class="h-5 w-5 rounded">

                </label>


                <label class="flex items-center justify-between">

                    <div>

                        <p class="text-sm font-medium">
                            Maintenance notifications
                        </p>

                        <p class="text-xs text-slate-500">
                            Receive updates about maintenance.
                        </p>

                    </div>

                    <input type="checkbox"
                           checked
                           class="h-5 w-5 rounded">

                </label>

            </div>

        </section>


        <div class="flex justify-end">

            <button
                class="rounded-lg bg-primary-600 px-5 py-2.5
                       text-sm font-semibold text-white">

                Save Settings

            </button>

        </div>

    </div>

</div>

</main>

<?php require_once "../includes/footer.php"; ?>