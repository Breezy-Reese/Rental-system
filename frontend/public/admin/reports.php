<?php

require_once "../../includes/admin.php";
require_admin();

require_once "../../includes/data.php";

$pageTitle = "Reports";

/*
|--------------------------------------------------------------------------
| Selected Report
|--------------------------------------------------------------------------
*/

$allowedReports = [
    'rent-collection',
    'property-performance',
    'financial-summary',
    'tenant-report',
    'maintenance-report',
    'lease-report',
];

$selectedReport = trim(
    (string) ($_GET['report'] ?? '')
);

if (
    $selectedReport !== '' &&
    !in_array(
        $selectedReport,
        $allowedReports,
        true
    )
) {
    $selectedReport = '';
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
|
| Prefixed to avoid conflicts with functions already declared
| inside data.php.
|--------------------------------------------------------------------------
*/

function pp_reports_id($value): string
{
    if (is_array($value)) {

        return (string) (
            $value['_id']
            ?? $value['id']
            ?? $value['propertyId']
            ?? $value['tenantId']
            ?? $value['unitId']
            ?? $value['leaseId']
            ?? ''
        );
    }

    return (string) ($value ?? '');
}

function pp_reports_money($amount): string
{
    return 'KES ' . number_format(
        (float) ($amount ?? 0),
        2
    );
}

function pp_reports_date($value): string
{
    if (!$value) {
        return '-';
    }

    $timestamp = strtotime(
        (string) $value
    );

    if ($timestamp === false) {
        return '-';
    }

    return date(
        'd M Y',
        $timestamp
    );
}

function pp_reports_property_aliases(
    array $property
): array {

    $aliases = [];

    foreach (
        [
            $property['_id'] ?? null,
            $property['id'] ?? null,
            $property['propertyId'] ?? null,
        ]
        as $value
    ) {

        $id = pp_reports_id($value);

        if (
            $id !== '' &&
            !in_array(
                $id,
                $aliases,
                true
            )
        ) {
            $aliases[] = $id;
        }
    }

    return $aliases;
}

function pp_reports_unit_property_aliases(
    array $unit
): array {

    $aliases = [];

    foreach (
        [
            $unit['propertyId'] ?? null,
            $unit['property'] ?? null,
            $unit['property_id'] ?? null,
        ]
        as $value
    ) {

        $id = pp_reports_id($value);

        if (
            $id !== '' &&
            !in_array(
                $id,
                $aliases,
                true
            )
        ) {
            $aliases[] = $id;
        }
    }

    return $aliases;
}

function pp_reports_unit_id(
    array $unit
): string {

    return pp_reports_id(
        $unit['_id']
        ?? $unit['id']
        ?? $unit['unitId']
        ?? ''
    );
}

function pp_reports_tenant_id(
    array $tenant
): string {

    return pp_reports_id(
        $tenant['_id']
        ?? $tenant['id']
        ?? $tenant['tenantId']
        ?? ''
    );
}

function pp_reports_lease_id(
    array $lease
): string {

    return pp_reports_id(
        $lease['_id']
        ?? $lease['id']
        ?? $lease['leaseId']
        ?? ''
    );
}

function pp_reports_payment_tenant_id(
    array $payment
): string {

    return pp_reports_id(
        $payment['tenantId']
        ?? ''
    );
}

function pp_reports_maintenance_tenant_id(
    array $item
): string {

    return pp_reports_id(
        $item['tenantId']
        ?? ''
    );
}

function pp_reports_find_property(
    $propertyReference,
    array $properties
): ?array {

    $reference =
        pp_reports_id(
            $propertyReference
        );

    if ($reference === '') {
        return null;
    }

    foreach ($properties as $property) {

        if (!is_array($property)) {
            continue;
        }

        $aliases =
            pp_reports_property_aliases(
                $property
            );

        if (
            in_array(
                $reference,
                $aliases,
                true
            )
        ) {
            return $property;
        }
    }

    return null;
}

function pp_reports_property_name(
    $propertyReference,
    array $properties
): string {

    $property =
        pp_reports_find_property(
            $propertyReference,
            $properties
        );

    if (!$property) {
        return '-';
    }

    return (string) (
        $property['name']
        ?? $property['propertyName']
        ?? 'Unnamed Property'
    );
}

function pp_reports_find_tenant(
    $tenantReference,
    array $tenants
): ?array {

    $reference =
        pp_reports_id(
            $tenantReference
        );

    if ($reference === '') {
        return null;
    }

    foreach ($tenants as $tenant) {

        if (!is_array($tenant)) {
            continue;
        }

        $tenantId =
            pp_reports_tenant_id(
                $tenant
            );

        if (
            $tenantId === $reference
        ) {
            return $tenant;
        }
    }

    return null;
}

function pp_reports_tenant_name(
    $tenantReference,
    array $tenants
): string {

    $tenant =
        pp_reports_find_tenant(
            $tenantReference,
            $tenants
        );

    if (!$tenant) {
        return '-';
    }

    return (string) (
        $tenant['name']
        ?? $tenant['fullName']
        ?? $tenant['customerName']
        ?? 'Unknown Tenant'
    );
}

function pp_reports_find_unit(
    $unitReference,
    array $units
): ?array {

    $reference =
        pp_reports_id(
            $unitReference
        );

    if ($reference === '') {
        return null;
    }

    foreach ($units as $unit) {

        if (!is_array($unit)) {
            continue;
        }

        $unitId =
            pp_reports_unit_id(
                $unit
            );

        if (
            $unitId === $reference
        ) {
            return $unit;
        }
    }

    return null;
}

function pp_reports_unit_name(
    $unitReference,
    array $units
): string {

    $unit =
        pp_reports_find_unit(
            $unitReference,
            $units
        );

    if (!$unit) {
        return '-';
    }

    return (string) (
        $unit['unitNumber']
        ?? $unit['unitId']
        ?? 'Unknown Unit'
    );
}

function pp_reports_unit_is_occupied(
    array $unit
): bool {

    $status = strtolower(
        trim(
            (string) (
                $unit['status']
                ?? $unit['unitStatus']
                ?? ''
            )
        )
    );

    if (
        in_array(
            $status,
            [
                'occupied',
                'rented',
                'leased',
            ],
            true
        )
    ) {
        return true;
    }

    if (
        in_array(
            $status,
            [
                'vacant',
                'available',
                'empty',
            ],
            true
        )
    ) {
        return false;
    }

    return !empty(
        $unit['tenantId']
    );
}

function pp_reports_payment_status(
    array $payment
): string {

    return (string) (
        $payment['status']
        ?? 'Pending'
    );
}

function pp_reports_payment_is_paid(
    array $payment
): bool {

    $status = strtolower(
        trim(
            pp_reports_payment_status(
                $payment
            )
        )
    );

    return in_array(
        $status,
        [
            'paid',
            'completed',
            'approved',
            'success',
            'successful',
            'confirmed',
        ],
        true
    );
}

function pp_reports_status_class(
    string $status
): string {

    $value =
        strtolower(
            trim($status)
        );

    if (
        in_array(
            $value,
            [
                'active',
                'paid',
                'completed',
                'approved',
                'success',
                'successful',
                'confirmed',
                'resolved',
                'occupied',
            ],
            true
        )
    ) {
        return 'bg-emerald-100 text-emerald-700';
    }

    if (
        in_array(
            $value,
            [
                'pending',
                'medium',
                'processing',
                'submitted',
            ],
            true
        )
    ) {
        return 'bg-amber-100 text-amber-700';
    }

    if (
        in_array(
            $value,
            [
                'cancelled',
                'canceled',
                'rejected',
                'terminated',
                'inactive',
            ],
            true
        )
    ) {
        return 'bg-red-100 text-red-700';
    }

    return 'bg-slate-100 text-slate-700';
}

/*
|--------------------------------------------------------------------------
| Report Names
|--------------------------------------------------------------------------
*/

$reportTitles = [

    'rent-collection' =>
        'Rent Collection',

    'property-performance' =>
        'Property Performance',

    'financial-summary' =>
        'Financial Summary',

    'tenant-report' =>
        'Tenant Report',

    'maintenance-report' =>
        'Maintenance Report',

    'lease-report' =>
        'Lease Report',
];

$reportDescriptions = [

    'rent-collection' =>
        'View rent payments, collection totals and outstanding balances.',

    'property-performance' =>
        'Analyze occupancy and unit performance across your properties.',

    'financial-summary' =>
        'Review income, expenses and net financial performance.',

    'tenant-report' =>
        'Review tenant occupancy, property and lease information.',

    'maintenance-report' =>
        'Review maintenance requests, priorities and statuses.',

    'lease-report' =>
        'Review active, expired and upcoming lease expirations.',
];

/*
|--------------------------------------------------------------------------
| Rent Collection Calculations
|--------------------------------------------------------------------------
*/

$totalCollected = 0;
$totalPending = 0;
$totalPayments = count($payments);

foreach ($payments as $payment) {

    if (!is_array($payment)) {
        continue;
    }

    $amount =
        (float) (
            $payment['amount']
            ?? 0
        );

    if (
        pp_reports_payment_is_paid(
            $payment
        )
    ) {

        $totalCollected +=
            $amount;

    } else {

        $totalPending +=
            $amount;
    }
}

/*
|--------------------------------------------------------------------------
| Current Month Payments
|--------------------------------------------------------------------------
*/

$currentMonth =
    date('Y-m');

$paidThisMonth = 0;

foreach ($payments as $payment) {

    if (!is_array($payment)) {
        continue;
    }

    if (
        !pp_reports_payment_is_paid(
            $payment
        )
    ) {
        continue;
    }

    $paymentDate =
        $payment['paymentDate']
        ?? $payment['date']
        ?? $payment['createdAt']
        ?? '';

    if (
        $paymentDate !== '' &&
        strpos(
            (string) $paymentDate,
            $currentMonth
        ) === 0
    ) {

        $paidThisMonth +=
            (float) (
                $payment['amount']
                ?? 0
            );
    }
}

/*
|--------------------------------------------------------------------------
| Outstanding Balances
|--------------------------------------------------------------------------
*/

$outstandingTotal = 0;
$outstandingRows = [];

foreach ($leases as $lease) {

    if (!is_array($lease)) {
        continue;
    }

    $leaseStatus = strtolower(
        trim(
            (string) (
                $lease['status']
                ?? ''
            )
        )
    );

    if (
        !in_array(
            $leaseStatus,
            [
                'active',
                'current',
            ],
            true
        )
    ) {
        continue;
    }

    $rent =
        (float) (
            $lease['rent']
            ?? $lease['monthlyRent']
            ?? 0
        );

    $tenantId =
        $lease['tenantId']
        ?? '';

    $propertyId =
        $lease['propertyId']
        ?? '';

    if ($rent <= 0) {
        continue;
    }

    $paidForTenant =
        0;

    foreach ($payments as $payment) {

        if (!is_array($payment)) {
            continue;
        }

        if (
            !pp_reports_payment_is_paid(
                $payment
            )
        ) {
            continue;
        }

        $paymentTenantId =
            pp_reports_payment_tenant_id(
                $payment
            );

        $leaseTenantId =
            pp_reports_id(
                $tenantId
            );

        if (
            $paymentTenantId !== '' &&
            $leaseTenantId !== '' &&
            $paymentTenantId === $leaseTenantId
        ) {

            $paymentDate =
                $payment['paymentDate']
                ?? $payment['date']
                ?? $payment['createdAt']
                ?? '';

            if (
                $paymentDate !== '' &&
                strpos(
                    (string) $paymentDate,
                    $currentMonth
                ) === 0
            ) {

                $paidForTenant +=
                    (float) (
                        $payment['amount']
                        ?? 0
                    );
            }
        }
    }

    $balance =
        max(
            0,
            $rent - $paidForTenant
        );

    if ($balance <= 0) {
        continue;
    }

    $outstandingTotal +=
        $balance;

    $outstandingRows[] = [

        'tenant' =>
            pp_reports_tenant_name(
                $tenantId,
                $tenants
            ),

        'property' =>
            pp_reports_property_name(
                $propertyId,
                $properties
            ),

        'rent' =>
            $rent,

        'paid' =>
            $paidForTenant,

        'balance' =>
            $balance,
    ];
}

/*
|--------------------------------------------------------------------------
| Property Performance
|--------------------------------------------------------------------------
*/

$propertyPerformance = [];

foreach ($properties as $property) {

    if (!is_array($property)) {
        continue;
    }

    $aliases =
        pp_reports_property_aliases(
            $property
        );

    $total =
        0;

    $occupied =
        0;

    foreach ($units as $unit) {

        if (!is_array($unit)) {
            continue;
        }

        $unitAliases =
            pp_reports_unit_property_aliases(
                $unit
            );

        $matches = false;

        foreach ($aliases as $alias) {

            if (
                in_array(
                    $alias,
                    $unitAliases,
                    true
                )
            ) {

                $matches = true;
                break;
            }
        }

        if (!$matches) {
            continue;
        }

        $total++;

        if (
            pp_reports_unit_is_occupied(
                $unit
            )
        ) {
            $occupied++;
        }
    }

    $vacant =
        max(
            0,
            $total - $occupied
        );

    $occupancy =
        $total > 0
            ? ($occupied / $total) * 100
            : 0;

    $propertyPerformance[] = [

        'name' =>
            $property['name']
            ?? 'Unnamed Property',

        'location' =>
            $property['location']
            ?? '-',

        'total' =>
            $total,

        'occupied' =>
            $occupied,

        'vacant' =>
            $vacant,

        'occupancy' =>
            $occupancy,
    ];
}

/*
|--------------------------------------------------------------------------
| Financial Summary
|--------------------------------------------------------------------------
*/

$totalIncome = 0;

foreach ($payments as $payment) {

    if (
        is_array($payment) &&
        pp_reports_payment_is_paid(
            $payment
        )
    ) {

        $totalIncome +=
            (float) (
                $payment['amount']
                ?? 0
            );
    }
}

$totalExpenses = 0;

foreach ($expenses as $expense) {

    if (!is_array($expense)) {
        continue;
    }

    $totalExpenses +=
        (float) (
            $expense['amount']
            ?? $expense['cost']
            ?? 0
        );
}

$netRevenue =
    $totalIncome -
    $totalExpenses;

$incomeExpenseTotal =
    $totalIncome +
    $totalExpenses;

$incomePercentage =
    $incomeExpenseTotal > 0
        ? ($totalIncome / $incomeExpenseTotal) * 100
        : 0;

/*
|--------------------------------------------------------------------------
| Tenant Statistics
|--------------------------------------------------------------------------
*/

$totalTenants =
    count($tenants);

$activeTenants =
    0;

foreach ($tenants as $tenant) {

    if (!is_array($tenant)) {
        continue;
    }

    $status =
        strtolower(
            trim(
                (string) (
                    $tenant['status']
                    ?? ''
                )
            )
        );

    if (
        in_array(
            $status,
            [
                'active',
                'current',
            ],
            true
        )
    ) {

        $activeTenants++;
    }
}

/*
|--------------------------------------------------------------------------
| Maintenance Statistics
|--------------------------------------------------------------------------
*/

$totalMaintenance =
    count($maintenanceRequests);

$pendingMaintenance =
    0;

$resolvedMaintenance =
    0;

foreach ($maintenanceRequests as $request) {

    if (!is_array($request)) {
        continue;
    }

    $status =
        strtolower(
            trim(
                (string) (
                    $request['status']
                    ?? ''
                )
            )
        );

    if (
        in_array(
            $status,
            [
                'pending',
                'open',
                'submitted',
            ],
            true
        )
    ) {

        $pendingMaintenance++;
    }

    if (
        in_array(
            $status,
            [
                'resolved',
                'completed',
                'closed',
            ],
            true
        )
    ) {

        $resolvedMaintenance++;
    }
}

/*
|--------------------------------------------------------------------------
| Lease Statistics
|--------------------------------------------------------------------------
*/

$totalLeases =
    count($leases);

$activeLeases =
    0;

$expiringLeases =
    0;

$expiredLeases =
    0;

$today =
    new DateTimeImmutable(
        'today'
    );

$thirtyDays =
    $today->modify(
        '+30 days'
    );

foreach ($leases as $lease) {

    if (!is_array($lease)) {
        continue;
    }

    $status =
        strtolower(
            trim(
                (string) (
                    $lease['status']
                    ?? ''
                )
            )
        );

    if (
        in_array(
            $status,
            [
                'active',
                'current',
            ],
            true
        )
    ) {
        $activeLeases++;
    }

    $endDate =
        $lease['endDate']
        ?? $lease['leaseEndDate']
        ?? '';

    if ($endDate === '') {
        continue;
    }

    try {

        $leaseEnd =
            new DateTimeImmutable(
                substr(
                    (string) $endDate,
                    0,
                    10
                )
            );

    } catch (Throwable $exception) {
        continue;
    }

    if ($leaseEnd < $today) {

        $expiredLeases++;

    } elseif (
        $leaseEnd >= $today &&
        $leaseEnd <= $thirtyDays
    ) {

        $expiringLeases++;
    }
}

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";

?>

<main class="min-h-screen bg-slate-50 lg:ml-64">

    <!-- ========================================================
         Header
    ========================================================= -->

    <header class="border-b border-slate-200 bg-white">

        <div class="flex min-h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">

            <div>

                <h1 class="text-xl font-bold text-slate-900">
                    Reports
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Property management reports
                </p>

            </div>

            <?php if ($selectedReport !== ''): ?>

                <div class="flex items-center gap-2">

                    <a
                        href="reports.php"
                        class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        ← All Reports
                    </a>

                    <button
                        type="button"
                        onclick="window.print()"
                        class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 print:hidden"
                    >
                        Print
                    </button>

                </div>

            <?php endif; ?>

        </div>

    </header>

    <div class="p-4 sm:p-6 lg:p-8">

        <?php if ($selectedReport === ''): ?>

            <!-- =================================================
                 Report Landing Page
            ================================================== -->

            <div class="mb-8">

                <h2 class="text-2xl font-bold text-slate-900">
                    Reports & Analytics
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Review your property's financial and operational performance.
                </p>

            </div>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">

                <!-- Rent Collection -->

                <a
                    href="reports.php?report=rent-collection"
                    class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
                >

                    <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-2xl">
                        💰
                    </div>

                    <h3 class="font-semibold text-slate-900 group-hover:text-indigo-600">
                        Rent Collection
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        View rent collection, payment totals and outstanding balances.
                    </p>

                    <div class="mt-5 text-sm font-semibold text-indigo-600">
                        Open Report →
                    </div>

                </a>

                <!-- Property Performance -->

                <a
                    href="reports.php?report=property-performance"
                    class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
                >

                    <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-2xl">
                        🏢
                    </div>

                    <h3 class="font-semibold text-slate-900 group-hover:text-indigo-600">
                        Property Performance
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Analyze occupancy, occupied units and vacant units by property.
                    </p>

                    <div class="mt-5 text-sm font-semibold text-indigo-600">
                        Open Report →
                    </div>

                </a>

                <!-- Financial Summary -->

                <a
                    href="reports.php?report=financial-summary"
                    class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
                >

                    <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-2xl">
                        📊
                    </div>

                    <h3 class="font-semibold text-slate-900 group-hover:text-indigo-600">
                        Financial Summary
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Review income, expenses and net revenue.
                    </p>

                    <div class="mt-5 text-sm font-semibold text-indigo-600">
                        Open Report →
                    </div>

                </a>

                <!-- Tenant Report -->

                <a
                    href="reports.php?report=tenant-report"
                    class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
                >

                    <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-violet-50 text-2xl">
                        👥
                    </div>

                    <h3 class="font-semibold text-slate-900 group-hover:text-indigo-600">
                        Tenant Report
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Review tenants, their properties, units and lease status.
                    </p>

                    <div class="mt-5 text-sm font-semibold text-indigo-600">
                        Open Report →
                    </div>

                </a>

                <!-- Maintenance -->

                <a
                    href="reports.php?report=maintenance-report"
                    class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
                >

                    <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-2xl">
                        🔧
                    </div>

                    <h3 class="font-semibold text-slate-900 group-hover:text-indigo-600">
                        Maintenance Report
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Review maintenance requests, priorities and statuses.
                    </p>

                    <div class="mt-5 text-sm font-semibold text-indigo-600">
                        Open Report →
                    </div>

                </a>

                <!-- Lease -->

                <a
                    href="reports.php?report=lease-report"
                    class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
                >

                    <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-2xl">
                        📄
                    </div>

                    <h3 class="font-semibold text-slate-900 group-hover:text-indigo-600">
                        Lease Report
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Review active leases, expired leases and upcoming expirations.
                    </p>

                    <div class="mt-5 text-sm font-semibold text-indigo-600">
                        Open Report →
                    </div>

                </a>

            </div>

        <?php else: ?>

            <!-- =================================================
                 Selected Report
            ================================================== -->

            <div class="mb-8">

                <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">
                    Reports & Analytics
                </p>

                <h2 class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">
                    <?= e($reportTitles[$selectedReport]) ?>
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    <?= e($reportDescriptions[$selectedReport]) ?>
                </p>

            </div>

            <?php if ($selectedReport === 'rent-collection'): ?>

                <!-- =================================================
                     RENT COLLECTION
                ================================================== -->

                <div class="mb-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-500">
                            Total Payments
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            <?= $totalPayments ?>
                        </p>

                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-500">
                            Total Collected
                        </p>

                        <p class="mt-2 text-2xl font-bold text-emerald-600">
                            <?= e(pp_reports_money($totalCollected)) ?>
                        </p>

                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-500">
                            Pending Payments
                        </p>

                        <p class="mt-2 text-2xl font-bold text-amber-600">
                            <?= e(pp_reports_money($totalPending)) ?>
                        </p>

                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-500">
                            Outstanding
                        </p>

                        <p class="mt-2 text-2xl font-bold text-red-600">
                            <?= e(pp_reports_money($outstandingTotal)) ?>
                        </p>

                    </div>

                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-200 px-5 py-5 sm:px-6">

                        <h3 class="text-lg font-semibold text-slate-900">
                            Outstanding Balances
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Estimated from active lease rent compared with payments recorded this month.
                        </p>

                    </div>

                    <?php if (empty($outstandingRows)): ?>

                        <div class="px-6 py-12 text-center">

                            <div class="text-4xl">
                                ✅
                            </div>

                            <p class="mt-3 font-semibold text-slate-900">
                                No outstanding balances found
                            </p>

                        </div>

                    <?php else: ?>

                        <div class="overflow-x-auto">

                            <table class="min-w-full text-left text-sm">

                                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">

                                    <tr>

                                        <th class="px-5 py-4">
                                            Tenant
                                        </th>

                                        <th class="px-5 py-4">
                                            Property
                                        </th>

                                        <th class="px-5 py-4">
                                            Monthly Rent
                                        </th>

                                        <th class="px-5 py-4">
                                            Paid
                                        </th>

                                        <th class="px-5 py-4">
                                            Outstanding
                                        </th>

                                    </tr>

                                </thead>

                                <tbody class="divide-y divide-slate-100">

                                    <?php foreach ($outstandingRows as $row): ?>

                                        <tr>

                                            <td class="px-5 py-4 font-medium text-slate-900">
                                                <?= e($row['tenant']) ?>
                                            </td>

                                            <td class="px-5 py-4 text-slate-600">
                                                <?= e($row['property']) ?>
                                            </td>

                                            <td class="px-5 py-4 text-slate-700">
                                                <?= e(pp_reports_money($row['rent'])) ?>
                                            </td>

                                            <td class="px-5 py-4 text-emerald-600">
                                                <?= e(pp_reports_money($row['paid'])) ?>
                                            </td>

                                            <td class="px-5 py-4 font-semibold text-red-600">
                                                <?= e(pp_reports_money($row['balance'])) ?>
                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php endif; ?>

                </div>

            <?php elseif ($selectedReport === 'property-performance'): ?>

                <!-- =================================================
                     PROPERTY PERFORMANCE
                ================================================== -->

                <div class="mb-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-500">
                            Properties
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            <?= count($propertyPerformance) ?>
                        </p>

                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-500">
                            Total Units
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            <?= array_sum(array_column($propertyPerformance, 'total')) ?>
                        </p>

                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-500">
                            Occupied
                        </p>

                        <p class="mt-2 text-3xl font-bold text-indigo-600">
                            <?= array_sum(array_column($propertyPerformance, 'occupied')) ?>
                        </p>

                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-500">
                            Vacant
                        </p>

                        <p class="mt-2 text-3xl font-bold text-emerald-600">
                            <?= array_sum(array_column($propertyPerformance, 'vacant')) ?>
                        </p>

                    </div>

                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="overflow-x-auto">

                        <table class="min-w-full text-left text-sm">

                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">

                                <tr>

                                    <th class="px-5 py-4">
                                        Property
                                    </th>

                                    <th class="px-5 py-4">
                                        Location
                                    </th>

                                    <th class="px-5 py-4">
                                        Units
                                    </th>

                                    <th class="px-5 py-4">
                                        Occupied
                                    </th>

                                    <th class="px-5 py-4">
                                        Vacant
                                    </th>

                                    <th class="px-5 py-4">
                                        Occupancy
                                    </th>

                                </tr>

                            </thead>

                            <tbody class="divide-y divide-slate-100">

                                <?php foreach ($propertyPerformance as $row): ?>

                                    <tr class="hover:bg-slate-50">

                                        <td class="px-5 py-4 font-semibold text-slate-900">
                                            <?= e($row['name']) ?>
                                        </td>

                                        <td class="px-5 py-4 text-slate-600">
                                            <?= e($row['location']) ?>
                                        </td>

                                        <td class="px-5 py-4">
                                            <?= (int) $row['total'] ?>
                                        </td>

                                        <td class="px-5 py-4 font-medium text-indigo-600">
                                            <?= (int) $row['occupied'] ?>
                                        </td>

                                        <td class="px-5 py-4 font-medium text-emerald-600">
                                            <?= (int) $row['vacant'] ?>
                                        </td>

                                        <td class="px-5 py-4 font-semibold text-slate-900">
                                            <?= number_format((float) $row['occupancy'], 1) ?>%
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            <?php elseif ($selectedReport === 'financial-summary'): ?>

                <!-- =================================================
                     FINANCIAL SUMMARY
                ================================================== -->

                <div class="mb-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-500">
                            Income
                        </p>

                        <p class="mt-2 text-2xl font-bold text-emerald-600">
                            <?= e(pp_reports_money($totalIncome)) ?>
                        </p>

                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-500">
                            Expenses
                        </p>

                        <p class="mt-2 text-2xl font-bold text-red-600">
                            <?= e(pp_reports_money($totalExpenses)) ?>
                        </p>

                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-500">
                            Net Revenue
                        </p>

                        <p class="mt-2 text-2xl font-bold <?= $netRevenue >= 0 ? 'text-indigo-600' : 'text-red-600' ?>">
                            <?= e(pp_reports_money($netRevenue)) ?>
                        </p>

                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-500">
                            Paid This Month
                        </p>

                        <p class="mt-2 text-2xl font-bold text-slate-900">
                            <?= e(pp_reports_money($paidThisMonth)) ?>
                        </p>

                    </div>

                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <h3 class="text-lg font-semibold text-slate-900">
                        Financial Overview
                    </h3>

                    <div class="mt-6">

                        <div class="flex items-center justify-between text-sm">

                            <span class="text-slate-500">
                                Income
                            </span>

                            <span class="font-semibold text-slate-900">
                                <?= e(pp_reports_money($totalIncome)) ?>
                            </span>

                        </div>

                        <div class="mt-2 h-3 overflow-hidden rounded-full bg-slate-100">

                            <div
                                class="h-full rounded-full bg-emerald-500"
                                style="width: <?= min(100, max(0, $incomePercentage)) ?>%;"
                            ></div>

                        </div>

                    </div>

                    <div class="mt-6">

                        <div class="flex items-center justify-between text-sm">

                            <span class="text-slate-500">
                                Expenses
                            </span>

                            <span class="font-semibold text-slate-900">
                                <?= e(pp_reports_money($totalExpenses)) ?>
                            </span>

                        </div>

                        <div class="mt-2 h-3 overflow-hidden rounded-full bg-slate-100">

                            <div
                                class="h-full rounded-full bg-red-500"
                                style="width: <?= $incomeExpenseTotal > 0 ? min(100, max(0, 100 - $incomePercentage)) : 0 ?>%;"
                            ></div>

                        </div>

                    </div>

                </div>

            <?php elseif ($selectedReport === 'tenant-report'): ?>

                <!-- =================================================
                     TENANT REPORT
                ================================================== -->

                <div class="mb-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-500">
                            Total Tenants
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            <?= $totalTenants ?>
                        </p>

                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-500">
                            Active Tenants
                        </p>

                        <p class="mt-2 text-3xl font-bold text-emerald-600">
                            <?= $activeTenants ?>
                        </p>

                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-500">
                            Active Leases
                        </p>

                        <p class="mt-2 text-3xl font-bold text-indigo-600">
                            <?= $activeLeases ?>
                        </p>

                    </div>

                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="overflow-x-auto">

                        <table class="min-w-full text-left text-sm">

                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">

                                <tr>

                                    <th class="px-5 py-4">
                                        Tenant
                                    </th>

                                    <th class="px-5 py-4">
                                        Property
                                    </th>

                                    <th class="px-5 py-4">
                                        Unit
                                    </th>

                                    <th class="px-5 py-4">
                                        Phone
                                    </th>

                                    <th class="px-5 py-4">
                                        Status
                                    </th>

                                </tr>

                            </thead>

                            <tbody class="divide-y divide-slate-100">

                                <?php foreach ($tenants as $tenant): ?>

                                    <?php

                                    if (!is_array($tenant)) {
                                        continue;
                                    }

                                    $tenantName =
                                        $tenant['name']
                                        ?? $tenant['fullName']
                                        ?? 'Unknown Tenant';

                                    $propertyId =
                                        $tenant['propertyId']
                                        ?? $tenant['property_id']
                                        ?? '';

                                    $unitId =
                                        $tenant['unitId']
                                        ?? $tenant['unit_id']
                                        ?? '';

                                    $tenantStatus =
                                        $tenant['status']
                                        ?? 'Active';

                                    ?>

                                    <tr class="hover:bg-slate-50">

                                        <td class="px-5 py-4">

                                            <div class="font-semibold text-slate-900">
                                                <?= e($tenantName) ?>
                                            </div>

                                            <div class="mt-1 text-xs text-slate-400">
                                                <?= e($tenant['email'] ?? '') ?>
                                            </div>

                                        </td>

                                        <td class="px-5 py-4 text-slate-600">
                                            <?= e(pp_reports_property_name($propertyId, $properties)) ?>
                                        </td>

                                        <td class="px-5 py-4 text-slate-600">
                                            <?= e(pp_reports_unit_name($unitId, $units)) ?>
                                        </td>

                                        <td class="px-5 py-4 text-slate-600">
                                            <?= e($tenant['phone'] ?? '-') ?>
                                        </td>

                                        <td class="px-5 py-4">

                                            <span
                                                class="rounded-full px-2.5 py-1 text-xs font-semibold <?= e(pp_reports_status_class($tenantStatus)) ?>"
                                            >
                                                <?= e($tenantStatus) ?>
                                            </span>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            <?php elseif ($selectedReport === 'maintenance-report'): ?>

                <!-- =================================================
                     MAINTENANCE REPORT
                ================================================== -->

                <div class="mb-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-500">
                            Total Requests
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            <?= $totalMaintenance ?>
                        </p>

                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-500">
                            Pending
                        </p>

                        <p class="mt-2 text-3xl font-bold text-amber-600">
                            <?= $pendingMaintenance ?>
                        </p>

                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-500">
                            Resolved
                        </p>

                        <p class="mt-2 text-3xl font-bold text-emerald-600">
                            <?= $resolvedMaintenance ?>
                        </p>

                    </div>

                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="overflow-x-auto">

                        <table class="min-w-full text-left text-sm">

                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">

                                <tr>

                                    <th class="px-5 py-4">
                                        Tenant
                                    </th>

                                    <th class="px-5 py-4">
                                        Property
                                    </th>

                                    <th class="px-5 py-4">
                                        Issue
                                    </th>

                                    <th class="px-5 py-4">
                                        Priority
                                    </th>

                                    <th class="px-5 py-4">
                                        Status
                                    </th>

                                    <th class="px-5 py-4">
                                        Date
                                    </th>

                                </tr>

                            </thead>

                            <tbody class="divide-y divide-slate-100">

                                <?php foreach ($maintenanceRequests as $request): ?>

                                    <?php

                                    if (!is_array($request)) {
                                        continue;
                                    }

                                    $tenantId =
                                        $request['tenantId']
                                        ?? '';

                                    $propertyId =
                                        $request['propertyId']
                                        ?? '';

                                    $issue =
                                        $request['issue']
                                        ?? $request['title']
                                        ?? 'Maintenance Request';

                                    $priority =
                                        $request['priority']
                                        ?? 'Medium';

                                    $status =
                                        $request['status']
                                        ?? 'Pending';

                                    $requestDate =
                                        $request['createdAt']
                                        ?? $request['date']
                                        ?? $request['requestDate']
                                        ?? '';

                                    ?>

                                    <tr class="hover:bg-slate-50">

                                        <td class="px-5 py-4 font-medium text-slate-900">
                                            <?= e(pp_reports_tenant_name($tenantId, $tenants)) ?>
                                        </td>

                                        <td class="px-5 py-4 text-slate-600">
                                            <?= e(pp_reports_property_name($propertyId, $properties)) ?>
                                        </td>

                                        <td class="max-w-xs px-5 py-4 text-slate-700">
                                            <?= e($issue) ?>
                                        </td>

                                        <td class="px-5 py-4">

                                            <span
                                                class="rounded-full px-2.5 py-1 text-xs font-semibold <?= e(pp_reports_status_class($priority)) ?>"
                                            >
                                                <?= e($priority) ?>
                                            </span>

                                        </td>

                                        <td class="px-5 py-4">

                                            <span
                                                class="rounded-full px-2.5 py-1 text-xs font-semibold <?= e(pp_reports_status_class($status)) ?>"
                                            >
                                                <?= e($status) ?>
                                            </span>

                                        </td>

                                        <td class="px-5 py-4 text-slate-500">
                                            <?= e(pp_reports_date($requestDate)) ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            <?php elseif ($selectedReport === 'lease-report'): ?>

                <!-- =================================================
                     LEASE REPORT
                ================================================== -->

                <div class="mb-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-500">
                            Total Leases
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            <?= $totalLeases ?>
                        </p>

                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-500">
                            Active
                        </p>

                        <p class="mt-2 text-3xl font-bold text-emerald-600">
                            <?= $activeLeases ?>
                        </p>

                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-500">
                            Expiring Soon
                        </p>

                        <p class="mt-2 text-3xl font-bold text-amber-600">
                            <?= $expiringLeases ?>
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            Within 30 days
                        </p>

                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-500">
                            Expired
                        </p>

                        <p class="mt-2 text-3xl font-bold text-red-600">
                            <?= $expiredLeases ?>
                        </p>

                    </div>

                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="overflow-x-auto">

                        <table class="min-w-full text-left text-sm">

                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">

                                <tr>

                                    <th class="px-5 py-4">
                                        Lease
                                    </th>

                                    <th class="px-5 py-4">
                                        Tenant
                                    </th>

                                    <th class="px-5 py-4">
                                        Property
                                    </th>

                                    <th class="px-5 py-4">
                                        Unit
                                    </th>

                                    <th class="px-5 py-4">
                                        Rent
                                    </th>

                                    <th class="px-5 py-4">
                                        End Date
                                    </th>

                                    <th class="px-5 py-4">
                                        Status
                                    </th>

                                </tr>

                            </thead>

                            <tbody class="divide-y divide-slate-100">

                                <?php foreach ($leases as $lease): ?>

                                    <?php

                                    if (!is_array($lease)) {
                                        continue;
                                    }

                                    $leaseId =
                                        $lease['leaseId']
                                        ?? $lease['id']
                                        ?? 'Lease';

                                    $tenantId =
                                        $lease['tenantId']
                                        ?? '';

                                    $propertyId =
                                        $lease['propertyId']
                                        ?? '';

                                    $unitId =
                                        $lease['unitId']
                                        ?? '';

                                    $rent =
                                        (float) (
                                            $lease['rent']
                                            ?? $lease['monthlyRent']
                                            ?? 0
                                        );

                                    $endDate =
                                        $lease['endDate']
                                        ?? $lease['leaseEndDate']
                                        ?? '';

                                    $leaseStatus =
                                        $lease['status']
                                        ?? 'Active';

                                    ?>

                                    <tr class="hover:bg-slate-50">

                                        <td class="px-5 py-4 font-medium text-slate-900">
                                            <?= e($leaseId) ?>
                                        </td>

                                        <td class="px-5 py-4 text-slate-700">
                                            <?= e(pp_reports_tenant_name($tenantId, $tenants)) ?>
                                        </td>

                                        <td class="px-5 py-4 text-slate-600">
                                            <?= e(pp_reports_property_name($propertyId, $properties)) ?>
                                        </td>

                                        <td class="px-5 py-4 text-slate-600">
                                            <?= e(pp_reports_unit_name($unitId, $units)) ?>
                                        </td>

                                        <td class="px-5 py-4 font-medium text-slate-900">
                                            <?= e(pp_reports_money($rent)) ?>
                                        </td>

                                        <td class="px-5 py-4 text-slate-600">
                                            <?= e(pp_reports_date($endDate)) ?>
                                        </td>

                                        <td class="px-5 py-4">

                                            <span
                                                class="rounded-full px-2.5 py-1 text-xs font-semibold <?= e(pp_reports_status_class($leaseStatus)) ?>"
                                            >
                                                <?= e($leaseStatus) ?>
                                            </span>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            <?php endif; ?>

        <?php endif; ?>

    </div>

</main>

<?php
require_once "../../includes/footer.php";
?>