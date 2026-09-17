<?php

require_once "../../includes/admin.php";
require_admin();

require_once "../../includes/data.php";

$pageTitle = "Leases";

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
?>

<main class="min-h-screen bg-slate-50 lg:ml-64">

    <header class="border-b border-slate-200 bg-white">

        <div class="flex min-h-16 items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">

            <div class="flex items-center gap-3">

                <button
                    id="mobileMenuButton"
                    type="button"
                    aria-label="Open navigation menu"
                    class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden"
                >
                    ☰
                </button>

                <div>

                    <h1 class="text-lg font-semibold text-slate-900">
                        Leases
                    </h1>

                    <p class="hidden text-xs text-slate-500 sm:block">
                        Manage tenant leases
                    </p>

                </div>

            </div>

            <a
                href="#"
                class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
            >
                + New Lease
            </a>

        </div>

    </header>

    <div class="p-4 sm:p-6 lg:p-8">

        <div class="mb-6">

            <h2 class="text-2xl font-bold text-slate-900">
                Lease Agreements
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Manage active and expired rental agreements.
            </p>

        </div>

        <?php if (empty($leases)): ?>

            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center">

                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-2xl">
                    📄
                </div>

                <h3 class="mt-4 text-lg font-semibold text-slate-900">
                    No leases found
                </h3>

                <p class="mt-2 text-sm text-slate-500">
                    There are currently no lease agreements in the system.
                </p>

            </div>

        <?php else: ?>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">

                <?php foreach ($leases as $lease): ?>

                    <?php

                    $databaseId = $lease["_id"] ?? "";

                    $leaseDisplayId =
                        $lease["leaseId"]
                        ?? $lease["id"]
                        ?? $databaseId
                        ?? "N/A";

                    $tenant =
                        $lease["tenant"]
                        ?? (
                            is_array($lease["tenantId"] ?? null)
                                ? ($lease["tenantId"]["name"] ?? "Not available")
                                : "Not available"
                        );

                    $property =
                        $lease["property"]
                        ?? (
                            is_array($lease["propertyId"] ?? null)
                                ? ($lease["propertyId"]["name"] ?? "Not available")
                                : "Not available"
                        );

                    $unit =
                        $lease["unit"]
                        ?? (
                            is_array($lease["unitId"] ?? null)
                                ? ($lease["unitId"]["unitNumber"] ?? "Not available")
                                : "Not available"
                        );

                    $rent = (float) (
                        $lease["rent"]
                        ?? $lease["monthlyRent"]
                        ?? 0
                    );

                    $status =
                        $lease["status"]
                        ?? "Active";

                    $statusLower = strtolower($status);

                    if ($statusLower === "active") {
                        $statusClass =
                            "bg-emerald-50 text-emerald-700";
                    } elseif (
                        $statusLower === "expired" ||
                        $statusLower === "terminated"
                    ) {
                        $statusClass =
                            "bg-red-50 text-red-700";
                    } else {
                        $statusClass =
                            "bg-amber-50 text-amber-700";
                    }

                    ?>

                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                        <div class="flex items-center justify-between gap-3">

                            <span class="break-all font-semibold text-slate-900">
                                <?= e($leaseDisplayId) ?>
                            </span>

                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium <?= $statusClass ?>">
                                <?= e($status) ?>
                            </span>

                        </div>

                        <div class="mt-5 space-y-4 text-sm">

                            <div>

                                <p class="text-xs text-slate-500">
                                    Tenant
                                </p>

                                <p class="mt-1 font-medium text-slate-900">
                                    <?= e($tenant) ?>
                                </p>

                            </div>

                            <div>

                                <p class="text-xs text-slate-500">
                                    Property
                                </p>

                                <p class="mt-1 font-medium text-slate-900">
                                    <?= e($property) ?>
                                </p>

                            </div>

                            <div>

                                <p class="text-xs text-slate-500">
                                    Unit
                                </p>

                                <p class="mt-1 font-medium text-slate-900">
                                    <?= e($unit) ?>
                                </p>

                            </div>

                            <div>

                                <p class="text-xs text-slate-500">
                                    Monthly Rent
                                </p>

                                <p class="mt-1 font-semibold text-slate-900">
                                    KSh <?= number_format($rent, 2) ?>
                                </p>

                            </div>

                        </div>

                        <div class="mt-5 border-t border-slate-100 pt-4">

                            <a
                                href="lease-details.php?id=<?= urlencode($databaseId ?: $leaseDisplayId) ?>"
                                class="inline-flex items-center text-sm font-semibold text-indigo-600 hover:text-indigo-700"
                            >
                                View Lease →
                            </a>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>

</main>

<?php require_once "../../includes/footer.php"; ?>