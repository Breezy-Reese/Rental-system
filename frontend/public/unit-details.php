<?php

$pageTitle = "Unit Details";

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

/*
|--------------------------------------------------------------------------
| Unit ID
|--------------------------------------------------------------------------
*/

$unitId = $_GET['id'] ?? 'UNIT-00101';

/*
|--------------------------------------------------------------------------
| Unit Data
|--------------------------------------------------------------------------
| Static for now.
| Later this will come from MongoDB.
|--------------------------------------------------------------------------
*/

$units = [

    'UNIT-00101' => [
        'id' => 'UNIT-00101',
        'unit_number' => 'A-101',
        'property_id' => 'PROP-001',
        'property' => 'Greenview Apartments',
        'location' => 'Kilimani, Nairobi',
        'type' => '2 Bedroom',
        'rent' => 25000,
        'deposit' => 50000,
        'status' => 'Occupied',
        'tenant_id' => 'TEN-00124',
        'tenant' => 'John Mwangi',
        'lease_id' => 'LS-00124',
        'lease_start' => 'Jan 1, 2026',
        'lease_end' => 'Dec 31, 2026',
        'balance' => 0,
    ],

    'UNIT-00102' => [
        'id' => 'UNIT-00102',
        'unit_number' => 'A-102',
        'property_id' => 'PROP-001',
        'property' => 'Greenview Apartments',
        'location' => 'Kilimani, Nairobi',
        'type' => '1 Bedroom',
        'rent' => 18000,
        'deposit' => 36000,
        'status' => 'Vacant',
        'tenant_id' => null,
        'tenant' => null,
        'lease_id' => null,
        'lease_start' => null,
        'lease_end' => null,
        'balance' => 0,
    ],

    'UNIT-00204' => [
        'id' => 'UNIT-00204',
        'unit_number' => 'B-204',
        'property_id' => 'PROP-002',
        'property' => 'Sunrise Estate',
        'location' => 'Nyali, Mombasa',
        'type' => '3 Bedroom',
        'rent' => 35000,
        'deposit' => 70000,
        'status' => 'Occupied',
        'tenant_id' => 'TEN-00125',
        'tenant' => 'Mary Wanjiku',
        'lease_id' => 'LS-00125',
        'lease_start' => 'Mar 1, 2026',
        'lease_end' => 'Feb 28, 2027',
        'balance' => 0,
    ],

    'UNIT-00302' => [
        'id' => 'UNIT-00302',
        'unit_number' => 'C-302',
        'property_id' => 'PROP-003',
        'property' => 'Palm Heights',
        'location' => 'Kilifi Town, Kilifi',
        'type' => '2 Bedroom',
        'rent' => 30000,
        'deposit' => 60000,
        'status' => 'Occupied',
        'tenant_id' => 'TEN-00126',
        'tenant' => 'Peter Kamau',
        'lease_id' => 'LS-00126',
        'lease_start' => 'Jun 1, 2026',
        'lease_end' => 'May 31, 2027',
        'balance' => 30000,
    ],

    'UNIT-00312' => [
        'id' => 'UNIT-00312',
        'unit_number' => 'C-312',
        'property_id' => 'PROP-003',
        'property' => 'Palm Heights',
        'location' => 'Kilifi Town, Kilifi',
        'type' => '1 Bedroom',
        'rent' => 20000,
        'deposit' => 40000,
        'status' => 'Maintenance',
        'tenant_id' => null,
        'tenant' => null,
        'lease_id' => null,
        'lease_start' => null,
        'lease_end' => null,
        'balance' => 0,
    ],

];

/*
|--------------------------------------------------------------------------
| Validate requested unit
|--------------------------------------------------------------------------
*/

if (!isset($units[$unitId])) {
    $unitId = 'UNIT-00101';
}

$unit = $units[$unitId];

?>

<main class="flex-1 lg:ml-64">

    <?php require_once "../includes/navbar.php"; ?>

    <div class="p-4 sm:p-6 lg:p-8">

        <!-- Back -->
        <div class="mb-6">

            <a
                href="units.php"
                class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-primary-600">

                ← Back to Units

            </a>

        </div>

        <!-- Header -->
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

            <div>

                <div class="flex flex-wrap items-center gap-3">

                    <h1 class="text-2xl font-bold text-slate-900">
                        Unit <?= htmlspecialchars($unit['unit_number']) ?>
                    </h1>

                    <?php if ($unit['status'] === 'Occupied'): ?>

                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                            Occupied
                        </span>

                    <?php elseif ($unit['status'] === 'Vacant'): ?>

                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                            Vacant
                        </span>

                    <?php else: ?>

                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                            Maintenance
                        </span>

                    <?php endif; ?>

                </div>

                <p class="mt-2 text-sm text-slate-500">
                    <?= htmlspecialchars($unit['type']) ?>
                    ·
                    <?= htmlspecialchars($unit['property']) ?>
                </p>

            </div>

            <div class="flex gap-2">

                <a
                    href="property-details.php?id=<?= urlencode($unit['property_id']) ?>"
                    class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">

                    View Property

                </a>

                <?php if ($unit['status'] === 'Vacant'): ?>

                    <button
                        type="button"
                        class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">

                        Assign Tenant

                    </button>

                <?php endif; ?>

            </div>

        </div>

        <!-- Main Grid -->
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

            <!-- Unit Information -->
            <div class="xl:col-span-2">

                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-200 px-6 py-5">

                        <h2 class="font-semibold text-slate-900">
                            Unit Information
                        </h2>

                    </div>

                    <div class="grid grid-cols-1 gap-6 p-6 sm:grid-cols-2">

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Unit Number
                            </p>

                            <p class="mt-1 font-semibold text-slate-800">
                                <?= htmlspecialchars($unit['unit_number']) ?>
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Property
                            </p>

                            <a
                                href="property-details.php?id=<?= urlencode($unit['property_id']) ?>"
                                class="mt-1 inline-block font-semibold text-primary-600 hover:underline">

                                <?= htmlspecialchars($unit['property']) ?>

                            </a>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Location
                            </p>

                            <p class="mt-1 font-semibold text-slate-800">
                                <?= htmlspecialchars($unit['location']) ?>
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Unit Type
                            </p>

                            <p class="mt-1 font-semibold text-slate-800">
                                <?= htmlspecialchars($unit['type']) ?>
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Monthly Rent
                            </p>

                            <p class="mt-1 text-xl font-bold text-slate-900">
                                KSh <?= number_format($unit['rent']) ?>
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Security Deposit
                            </p>

                            <p class="mt-1 text-xl font-bold text-slate-900">
                                KSh <?= number_format($unit['deposit']) ?>
                            </p>
                        </div>

                    </div>

                </div>

                <!-- Tenant -->
                <div class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-200 px-6 py-5">

                        <h2 class="font-semibold text-slate-900">
                            Current Tenant
                        </h2>

                    </div>

                    <div class="p-6">

                        <?php if ($unit['tenant_id']): ?>

                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                                <div class="flex items-center gap-4">

                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-100 font-semibold text-primary-700">
                                        <?= strtoupper(substr($unit['tenant'], 0, 1)) ?>
                                    </div>

                                    <div>

                                        <a
                                            href="tenant-details.php?id=<?= urlencode($unit['tenant_id']) ?>"
                                            class="font-semibold text-slate-900 hover:text-primary-600">

                                            <?= htmlspecialchars($unit['tenant']) ?>

                                        </a>

                                        <p class="mt-1 text-sm text-slate-500">
                                            Current tenant
                                        </p>

                                    </div>

                                </div>

                                <a
                                    href="tenant-details.php?id=<?= urlencode($unit['tenant_id']) ?>"
                                    class="text-sm font-semibold text-primary-600 hover:text-primary-800">

                                    View Tenant →

                                </a>

                            </div>

                        <?php else: ?>

                            <div class="rounded-lg bg-slate-50 p-5 text-center">

                                <p class="font-medium text-slate-700">
                                    No tenant assigned
                                </p>

                                <p class="mt-1 text-sm text-slate-500">
                                    This unit is currently available for rent.
                                </p>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

            <!-- Side Information -->
            <div class="space-y-6">

                <!-- Financial -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-200 px-6 py-5">

                        <h2 class="font-semibold text-slate-900">
                            Financial Summary
                        </h2>

                    </div>

                    <div class="space-y-5 p-6">

                        <div>
                            <p class="text-sm text-slate-500">
                                Monthly Rent
                            </p>

                            <p class="mt-1 text-xl font-bold text-slate-900">
                                KSh <?= number_format($unit['rent']) ?>
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-slate-500">
                                Outstanding Balance
                            </p>

                            <p class="mt-1 text-xl font-bold <?= $unit['balance'] > 0 ? 'text-red-600' : 'text-emerald-600' ?>">
                                KSh <?= number_format($unit['balance']) ?>
                            </p>
                        </div>

                    </div>

                </div>

                <!-- Lease -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-200 px-6 py-5">

                        <h2 class="font-semibold text-slate-900">
                            Lease Information
                        </h2>

                    </div>

                    <div class="p-6">

                        <?php if ($unit['lease_id']): ?>

                            <div class="space-y-4">

                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-400">
                                        Lease ID
                                    </p>

                                    <p class="mt-1 font-semibold text-slate-800">
                                        <?= htmlspecialchars($unit['lease_id']) ?>
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-400">
                                        Start Date
                                    </p>

                                    <p class="mt-1 font-medium text-slate-700">
                                        <?= htmlspecialchars($unit['lease_start']) ?>
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-400">
                                        End Date
                                    </p>

                                    <p class="mt-1 font-medium text-slate-700">
                                        <?= htmlspecialchars($unit['lease_end']) ?>
                                    </p>
                                </div>

                                <a
                                    href="leases.php"
                                    class="inline-block text-sm font-semibold text-primary-600 hover:text-primary-800">

                                    View Lease →

                                </a>

                            </div>

                        <?php else: ?>

                            <p class="text-sm text-slate-500">
                                No active lease for this unit.
                            </p>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

        <!-- Recent Activity -->
        <div class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-5">

                <div class="flex items-center justify-between">

                    <h2 class="font-semibold text-slate-900">
                        Recent Activity
                    </h2>

                    <?php if ($unit['tenant_id']): ?>

                        <a
                            href="payments.php"
                            class="text-sm font-semibold text-primary-600 hover:text-primary-800">

                            View Payments →

                        </a>

                    <?php endif; ?>

                </div>

            </div>

            <div class="divide-y divide-slate-100">

                <div class="flex items-center justify-between px-6 py-4">

                    <div>

                        <p class="font-medium text-slate-800">
                            Monthly rent payment
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            September 7, 2026
                        </p>

                    </div>

                    <span class="font-semibold text-emerald-600">
                        KSh <?= number_format($unit['rent']) ?>
                    </span>

                </div>

                <div class="flex items-center justify-between px-6 py-4">

                    <div>

                        <p class="font-medium text-slate-800">
                            Unit inspection
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            September 3, 2026
                        </p>

                    </div>

                    <span class="text-sm text-slate-500">
                        Completed
                    </span>

                </div>

            </div>

        </div>

    </div>

</main>

<?php require_once "../includes/footer.php"; ?>