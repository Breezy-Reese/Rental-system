<?php

require_once "../../includes/admin.php";
require_admin();

require_once "../../includes/data.php";

$pageTitle = "Properties";

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
                    Properties
                </h1>

                <p class="hidden text-xs text-slate-500 sm:block">
                    Manage your rental properties
                </p>
            </div>

            <a
                href="#"
                class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                + Add Property
            </a>

        </div>
    </header>


    <div class="p-4 sm:p-6 lg:p-8">

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-slate-900">
                All Properties
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                View and manage all properties in your portfolio.
            </p>
        </div>


        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">

            <?php foreach ($properties as $property): ?>

                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

                    <div class="h-36 bg-gradient-to-r from-indigo-500 to-purple-600">
                    </div>

                    <div class="p-5">

                        <div class="flex items-start justify-between">

                            <div>
                                <h3 class="font-semibold text-slate-900">
                                    <?= e($property['name']) ?>
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    <?= e($property['location']) ?>
                                </p>
                            </div>

                            <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700">
                                Active
                            </span>

                        </div>


                        <div class="mt-5 grid grid-cols-3 gap-3 text-center">

                            <div class="rounded-lg bg-slate-50 p-3">
                                <p class="text-lg font-bold text-slate-900">
                                    <?= e($property['units']) ?>
                                </p>
                                <p class="text-xs text-slate-500">
                                    Units
                                </p>
                            </div>

                            <div class="rounded-lg bg-green-50 p-3">
                                <p class="text-lg font-bold text-green-700">
                                    <?= e($property['occupied']) ?>
                                </p>
                                <p class="text-xs text-slate-500">
                                    Occupied
                                </p>
                            </div>

                            <div class="rounded-lg bg-amber-50 p-3">
                                <p class="text-lg font-bold text-amber-700">
                                    <?= e($property['vacant']) ?>
                                </p>
                                <p class="text-xs text-slate-500">
                                    Vacant
                                </p>
                            </div>

                        </div>


                        <div class="mt-5">

                            <a
                                href="property-details.php?id=<?= urlencode($property['id']) ?>"
                                class="block rounded-lg border border-slate-200 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                View Property
                            </a>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

</main>

<?php require_once "../../includes/footer.php"; ?>