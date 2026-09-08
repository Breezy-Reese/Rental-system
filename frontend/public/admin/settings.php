<?php

require_once "../../includes/admin.php";
require_admin();

$pageTitle = "Settings";

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
?>

<main class="min-h-screen bg-slate-50 lg:ml-64">

    <header class="border-b border-slate-200 bg-white">

        <div class="flex h-16 items-center px-4 sm:px-6 lg:px-8">

            <button
                id="mobileMenuButton"
                type="button"
                class="mr-4 rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden">
                ☰
            </button>

            <h1 class="text-lg font-semibold text-slate-900">
                Settings
            </h1>

        </div>

    </header>


    <div class="p-4 sm:p-6 lg:p-8">

        <div class="mx-auto max-w-3xl space-y-6">


            <!-- General Settings -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">

                    <h2 class="font-semibold text-slate-900">
                        General Settings
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Configure your property management system.
                    </p>

                </div>


                <div class="space-y-5 p-6">

                    <div>

                        <label class="mb-2 block text-sm font-medium text-slate-700">
                            Company Name
                        </label>

                        <input
                            type="text"
                            value="PropertyPro"
                            class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-medium text-slate-700">
                            Currency
                        </label>

                        <select
                            class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-indigo-500">

                            <option selected>
                                Kenyan Shilling (KSh)
                            </option>

                        </select>

                    </div>


                    <button
                        type="button"
                        class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                        Save Settings
                    </button>

                </div>

            </div>


            <!-- Security -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">

                    <h2 class="font-semibold text-slate-900">
                        Security
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Manage your account security.
                    </p>

                </div>


                <div class="p-6">

                    <a
                        href="../logout.php"
                        class="inline-flex rounded-lg border border-red-200 px-5 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50">
                        Logout
                    </a>

                </div>

            </div>

        </div>

    </div>

</main>

<?php require_once "../../includes/footer.php"; ?>