<?php

require_once "../../includes/admin.php";
require_admin();

require_once "../../includes/data.php";

$pageTitle = "Maintenance";

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
                    Maintenance
                </h1>

                <p class="hidden text-xs text-slate-500 sm:block">
                    Manage maintenance requests
                </p>
            </div>

            <a
                href="#"
                class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                + New Request
            </a>

        </div>

    </header>


    <div class="p-4 sm:p-6 lg:p-8">

        <div class="mb-6">

            <h2 class="text-2xl font-bold text-slate-900">
                Maintenance Requests
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Track and manage property maintenance issues.
            </p>

        </div>


        <div class="space-y-4">

            <?php foreach ($maintenanceRequests as $request): ?>

                <?php

                $status = $request['status'] ?? 'Pending';

                $statusClass = match ($status) {
                    'Urgent' => 'bg-red-100 text-red-700',
                    'Assigned' => 'bg-blue-100 text-blue-700',
                    'Completed' => 'bg-green-100 text-green-700',
                    default => 'bg-amber-100 text-amber-700'
                };

                ?>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                        <div class="flex items-start gap-4">

                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-xl">
                                🔧
                            </div>

                            <div>

                                <h3 class="font-semibold text-slate-900">
                                    <?= e($request['title']) ?>
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    <?= e($request['property']) ?>
                                    •
                                    <?= e($request['unit']) ?>
                                </p>

                                <p class="mt-2 text-sm text-slate-600">
                                    Reported by:
                                    <?= e($request['tenant'] ?? '-') ?>
                                </p>

                            </div>

                        </div>

                        <span class="w-fit rounded-full px-3 py-1 text-xs font-medium <?= $statusClass ?>">
                            <?= e($status) ?>
                        </span>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

</main>

<?php require_once "../../includes/footer.php"; ?>