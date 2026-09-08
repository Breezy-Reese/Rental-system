<?php

require_once "../../includes/admin.php";
require_admin();

require_once "../../includes/data.php";

$id = $_GET['id'] ?? '';

$tenant = null;

foreach ($tenants as $item) {
    if (($item['id'] ?? '') === $id) {
        $tenant = $item;
        break;
    }
}

if (!$tenant) {
    http_response_code(404);
    exit('Tenant not found.');
}

$pageTitle = $tenant['name'];

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

            <a
                href="tenants.php"
                class="mr-4 text-sm text-indigo-600 hover:text-indigo-700">
                ← Tenants
            </a>

            <h1 class="text-lg font-semibold text-slate-900">
                Tenant Details
            </h1>

        </div>

    </header>


    <div class="p-4 sm:p-6 lg:p-8">

        <div class="mx-auto max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex items-center gap-4">

                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100 text-xl font-bold text-indigo-700">
                    <?= strtoupper(substr($tenant['name'], 0, 2)) ?>
                </div>

                <div>

                    <h2 class="text-2xl font-bold text-slate-900">
                        <?= e($tenant['name']) ?>
                    </h2>

                    <p class="text-sm text-slate-500">
                        <?= e($tenant['id']) ?>
                    </p>

                </div>

            </div>


            <div class="mt-8 grid gap-5 sm:grid-cols-2">

                <div>
                    <p class="text-xs uppercase text-slate-500">
                        Property
                    </p>

                    <p class="mt-1 font-medium text-slate-900">
                        <?= e($tenant['property']) ?>
                    </p>
                </div>


                <div>
                    <p class="text-xs uppercase text-slate-500">
                        Unit
                    </p>

                    <p class="mt-1 font-medium text-slate-900">
                        <?= e($tenant['unit']) ?>
                    </p>
                </div>


                <div>
                    <p class="text-xs uppercase text-slate-500">
                        Phone
                    </p>

                    <p class="mt-1 font-medium text-slate-900">
                        <?= e($tenant['phone'] ?? '-') ?>
                    </p>
                </div>


                <div>
                    <p class="text-xs uppercase text-slate-500">
                        Email
                    </p>

                    <p class="mt-1 font-medium text-slate-900">
                        <?= e($tenant['email'] ?? '-') ?>
                    </p>
                </div>

            </div>

        </div>

    </div>

</main>

<?php require_once "../../includes/footer.php"; ?>