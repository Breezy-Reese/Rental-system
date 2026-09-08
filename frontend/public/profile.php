<?php

$pageTitle = "My Profile";

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

?>

<main class="flex-1 lg:ml-64">

<?php require_once "../includes/navbar.php"; ?>

<div class="mx-auto max-w-4xl p-4 sm:p-6 lg:p-8">

    <div class="rounded-xl border bg-white">

        <div class="border-b p-6">

            <h1 class="text-lg font-semibold">
                Profile Information
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Update your personal information.
            </p>

        </div>


        <form class="p-6">

            <div class="grid gap-6 sm:grid-cols-2">

                <div>

                    <label class="mb-2 block text-sm font-medium">
                        First name
                    </label>

                    <input
                        type="text"
                        value="Basil"
                        class="w-full rounded-lg border border-slate-300
                               px-4 py-3 text-sm">

                </div>


                <div>

                    <label class="mb-2 block text-sm font-medium">
                        Last name
                    </label>

                    <input
                        type="text"
                        value="Mutuku"
                        class="w-full rounded-lg border border-slate-300
                               px-4 py-3 text-sm">

                </div>


                <div>

                    <label class="mb-2 block text-sm font-medium">
                        Email
                    </label>

                    <input
                        type="email"
                        value="admin@example.com"
                        class="w-full rounded-lg border border-slate-300
                               px-4 py-3 text-sm">

                </div>


                <div>

                    <label class="mb-2 block text-sm font-medium">
                        Phone
                    </label>

                    <input
                        type="tel"
                        placeholder="+254..."
                        class="w-full rounded-lg border border-slate-300
                               px-4 py-3 text-sm">

                </div>

            </div>


            <div class="mt-6 flex justify-end">

                <button
                    type="submit"
                    class="rounded-lg bg-primary-600 px-5 py-2.5
                           text-sm font-semibold text-white">

                    Save Changes

                </button>

            </div>

        </form>

    </div>

</div>

</main>

<?php require_once "../includes/footer.php"; ?>