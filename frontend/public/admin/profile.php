<?php

require_once "../../includes/admin.php";
require_admin();

$user = current_user();

$pageTitle = "Profile";

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
                Profile
            </h1>

        </div>

    </header>


    <div class="p-4 sm:p-6 lg:p-8">

        <div class="mx-auto max-w-3xl">

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">

                    <h2 class="text-lg font-semibold text-slate-900">
                        Administrator Profile
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Your account information.
                    </p>

                </div>


                <div class="space-y-6 p-6">

                    <div>

                        <label class="mb-2 block text-sm font-medium text-slate-700">
                            Full Name
                        </label>

                        <input
                            type="text"
                            value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                            readonly
                            class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                        >

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-medium text-slate-700">
                            Email
                        </label>

                        <input
                            type="email"
                            value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                            readonly
                            class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                        >

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-medium text-slate-700">
                            Role
                        </label>

                        <input
                            type="text"
                            value="<?= htmlspecialchars($user['role'] ?? '') ?>"
                            readonly
                            class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                        >

                    </div>

                </div>

            </div>

        </div>

    </div>

</main>

<?php require_once "../../includes/footer.php"; ?>