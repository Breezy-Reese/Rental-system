<?php

require_once "../../includes/admin.php";
require_admin();

require_once "../../includes/data.php";

$id = $_GET['id'] ?? '';

$property = null;

foreach ($properties as $item) {
    if (($item['id'] ?? '') === $id) {
        $property = $item;
        break;
    }
}

if (!$property) {
    http_response_code(404);
    exit('Property not found.');
}

$pageTitle = $property['name'];

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
                href="properties.php"
                class="mr-4 text-sm text-indigo-600 hover:text-indigo-700">
                ← Properties
            </a>

            <h1 class="text-lg font-semibold text-slate-900">
                <?= e($property['name']) ?>
            </h1>

        </div>

    </header>


    <div class="p-4 sm:p-6 lg:p-8">

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

            <h2 class="text-2xl font-bold text-slate-900">
                <?= e($property['name']) ?>
            </h2>

            <p class="mt-1 text-slate-500">
                <?= e($property['location']) ?>
            </p>


            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                <div class="rounded-xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">Total Units</p>
                    <p class="mt-2 text-2xl font-bold">
                        <?= e($property['units']) ?>
                    </p>
                </div>

                <div class="rounded-xl bg-green-50 p-5">
                    <p class="text-sm text-green-700">Occupied</p>
                    <p class="mt-2 text-2xl font-bold text-green-700">
                        <?= e($property['occupied']) ?>
                    </p>
                </div>

                <div class="rounded-xl bg-amber-50 p-5">
                    <p class="text-sm text-amber-700">Vacant</p>
                    <p class="mt-2 text-2xl font-bold text-amber-700">
                        <?= e($property['vacant']) ?>
                    </p>
                </div>

                <div class="rounded-xl bg-indigo-50 p-5">
                    <p class="text-sm text-indigo-700">Monthly Income</p>
                    <p class="mt-2 text-2xl font-bold text-indigo-700">
                        <?= money($property['income'] ?? 0) ?>
                    </p>
                </div>

            </div>

        </div>

    </div>

</main>

<?php require_once "../../includes/footer.php"; ?>