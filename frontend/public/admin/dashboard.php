<?php

require_once __DIR__ . "/../../includes/admin.php";
require_admin();

require_once __DIR__ . "/../../includes/data.php";

$pageTitle = "Admin Dashboard";

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";

$user = current_user();

/*
|--------------------------------------------------------------------------
| Dashboard calculations
|--------------------------------------------------------------------------
*/

$totalProperties = count($properties);
$totalUnits      = count($units);
$totalTenants    = count($tenants);

$occupiedUnits = 0;
$vacantUnits   = 0;

foreach ($units as $unit) {
    if (($unit['status'] ?? '') === 'Occupied') {
        $occupiedUnits++;
    } else {
        $vacantUnits++;
    }
}

$totalPaid    = 0;
$totalPending = 0;

foreach ($payments as $payment) {
    $amount = (float)($payment['amount'] ?? 0);

    if (($payment['status'] ?? '') === 'Paid') {
        $totalPaid += $amount;
    } else {
        $totalPending += $amount;
    }
}

$maintenanceCount = count($maintenanceRequests);

/*
|--------------------------------------------------------------------------
| Chart data — Revenue over last 6 months
|--------------------------------------------------------------------------
*/

$monthKeys   = [];
$monthLabels = [];

for ($i = 5; $i >= 0; $i--) {
    $ts  = strtotime("first day of -{$i} month");
    $key = date('Y-m', $ts);
    $monthKeys[$key]   = 0;
    $monthLabels[$key] = date('M Y', $ts);
}

$revenueByMonth = $monthKeys;

foreach ($payments as $payment) {
    $dateStr = $payment['date'] ?? '';

    if ($dateStr === '') {
        continue;
    }

    $ts = strtotime($dateStr);

    if ($ts === false) {
        continue;
    }

    $key = date('Y-m', $ts);

    if (isset($revenueByMonth[$key])) {
        $revenueByMonth[$key] += (float)($payment['amount'] ?? 0);
    }
}

$revenueLabels = [];
$revenueValues = [];

foreach ($revenueByMonth as $key => $total) {
    $revenueLabels[] = $monthLabels[$key];
    $revenueValues[] = round($total, 2);
}

/*
|--------------------------------------------------------------------------
| Chart data — Maintenance by status
|--------------------------------------------------------------------------
*/

$maintenanceByStatus = [
    'Pending'     => 0,
    'In Progress' => 0,
    'Resolved'    => 0,
    'Other'       => 0,
];

foreach ($maintenanceRequests as $request) {
    $status = $request['status'] ?? 'Pending';

    if (isset($maintenanceByStatus[$status])) {
        $maintenanceByStatus[$status]++;
    } else {
        $maintenanceByStatus['Other']++;
    }
}

/*
|--------------------------------------------------------------------------
| Chart data — Payments by method
|--------------------------------------------------------------------------
*/

$paymentsByMethod = [];

foreach ($payments as $payment) {
    $method = trim((string)($payment['method'] ?? 'Other'));

    if ($method === '' || $method === '-') {
        $method = 'Other';
    }

    if (!isset($paymentsByMethod[$method])) {
        $paymentsByMethod[$method] = 0;
    }

    $paymentsByMethod[$method] += (float)($payment['amount'] ?? 0);
}
?>

<div class="lg:pl-64">

    <!-- Mobile Header -->
    <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 lg:px-8">

        <div class="ml-auto flex items-center gap-4">

            <div class="hidden text-right sm:block">
                <p class="text-sm font-semibold text-slate-800">
                    <?= htmlspecialchars($user['name'] ?? 'Administrator') ?>
                </p>
                <p class="text-xs text-slate-500">
                    Administrator
                </p>
            </div>

            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-600 font-semibold text-white">
                <?= htmlspecialchars(substr($user['name'] ?? 'A', 0, 1)) ?>
            </div>

        </div>

    </header>

    <!-- Main -->
    <main class="p-4 sm:p-6 lg:p-8">

        <!-- Heading -->
        <div class="mb-8">
            <p class="text-sm font-medium text-indigo-600">
                Administration
            </p>

            <h1 class="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">
                Admin Dashboard
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Overview of your property rental operations.
            </p>
        </div>

        <!-- =====================================================
             KPI CARDS
        ====================================================== -->
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

            <!-- Properties -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Properties</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            <?= $totalProperties ?>
                        </p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-2xl">
                        🏢
                    </div>
                </div>
                <a href="properties.php"
                   class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-700">
                    Manage properties →
                </a>
            </div>

            <!-- Units -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Units</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            <?= $totalUnits ?>
                        </p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-2xl">
                        🚪
                    </div>
                </div>
                <div class="mt-4 flex gap-4 text-xs">
                    <span class="text-green-600"><?= $occupiedUnits ?> occupied</span>
                    <span class="text-orange-600"><?= $vacantUnits ?> vacant</span>
                </div>
            </div>

            <!-- Tenants -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Tenants</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            <?= $totalTenants ?>
                        </p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 text-2xl">
                        👥
                    </div>
                </div>
                <a href="tenants.php"
                   class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-700">
                    View tenants →
                </a>
            </div>

            <!-- Payments -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Rent Collected</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">
                            KES <?= number_format($totalPaid) ?>
                        </p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-50 text-2xl">
                        💰
                    </div>
                </div>
                <p class="mt-4 text-xs text-orange-600">
                    KES <?= number_format($totalPending) ?> pending
                </p>
            </div>

        </div>

        <!-- =====================================================
             CHARTS — ROW 1
        ====================================================== -->
        <div class="mt-8 grid gap-6 lg:grid-cols-3">

            <!-- Revenue Line Chart -->
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Revenue</h2>
                        <p class="mt-1 text-sm text-slate-500">Payments over the last 6 months</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-slate-500">Total</p>
                        <p class="text-lg font-bold text-slate-900">
                            KES <?= number_format($totalPaid + $totalPending) ?>
                        </p>
                    </div>
                </div>
                <div class="mt-6 h-72">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Occupancy Donut -->
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Occupancy</h2>
                    <p class="mt-1 text-sm text-slate-500">Current unit distribution</p>
                </div>
                <div class="mt-6 flex h-56 items-center justify-center">
                    <canvas id="occupancyChart"></canvas>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div class="rounded-lg bg-green-50 p-3">
                        <p class="text-xs text-green-600">Occupied</p>
                        <p class="mt-1 text-xl font-bold text-green-700"><?= $occupiedUnits ?></p>
                    </div>
                    <div class="rounded-lg bg-orange-50 p-3">
                        <p class="text-xs text-orange-600">Vacant</p>
                        <p class="mt-1 text-xl font-bold text-orange-700"><?= $vacantUnits ?></p>
                    </div>
                </div>
            </div>

        </div>

        <!-- =====================================================
             CHARTS — ROW 2
        ====================================================== -->
        <div class="mt-6 grid gap-6 lg:grid-cols-2">

            <!-- Maintenance by Status -->
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Maintenance Requests</h2>
                    <p class="mt-1 text-sm text-slate-500">Breakdown by status</p>
                </div>
                <div class="mt-6 h-64">
                    <canvas id="maintenanceChart"></canvas>
                </div>
            </div>

            <!-- Payments by Method -->
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Payment Methods</h2>
                    <p class="mt-1 text-sm text-slate-500">Total collected by method</p>
                </div>
                <div class="mt-6 h-64">
                    <canvas id="methodChart"></canvas>
                </div>
            </div>

        </div>

        <!-- =====================================================
             MIDDLE SECTION
        ====================================================== -->
        <div class="mt-8 grid gap-6 lg:grid-cols-3">

            <!-- Quick Actions -->
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Quick Actions</h2>

                <div class="mt-5 space-y-3">
                    <a href="properties.php"
                       class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                        <span class="text-xl">🏢</span>
                        <span class="text-sm font-medium">Manage Properties</span>
                    </a>
                    <a href="tenants.php"
                       class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                        <span class="text-xl">👥</span>
                        <span class="text-sm font-medium">Manage Tenants</span>
                    </a>
                    <a href="payments.php"
                       class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                        <span class="text-xl">💳</span>
                        <span class="text-sm font-medium">View Payments</span>
                    </a>
                    <a href="maintenance.php"
                       class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                        <span class="text-xl">🔧</span>
                        <span class="text-sm font-medium">Maintenance Requests</span>
                    </a>
                </div>
            </div>

            <!-- Occupancy Overview -->
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Occupancy Overview</h2>
                        <p class="mt-1 text-sm text-slate-500">Current unit occupancy</p>
                    </div>
                    <a href="units.php"
                       class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                        View units →
                    </a>
                </div>

                <div class="mt-6">
                    <?php
                    $occupancyPercentage = $totalUnits > 0
                        ? round(($occupiedUnits / $totalUnits) * 100)
                        : 0;
                    ?>

                    <div class="mb-2 flex justify-between text-sm">
                        <span class="text-slate-600">Occupancy</span>
                        <span class="font-semibold text-slate-900"><?= $occupancyPercentage ?>%</span>
                    </div>

                    <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-indigo-600"
                             style="width: <?= $occupancyPercentage ?>%"></div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div class="rounded-lg bg-green-50 p-4">
                            <p class="text-xs text-green-600">Occupied</p>
                            <p class="mt-1 text-2xl font-bold text-green-700"><?= $occupiedUnits ?></p>
                        </div>
                        <div class="rounded-lg bg-orange-50 p-4">
                            <p class="text-xs text-orange-600">Vacant</p>
                            <p class="mt-1 text-2xl font-bold text-orange-700"><?= $vacantUnits ?></p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- =====================================================
             BOTTOM SECTION
        ====================================================== -->
        <div class="mt-8 grid gap-6 lg:grid-cols-2">

            <!-- Recent Payments -->
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between border-b border-slate-200 p-5">
                    <div>
                        <h2 class="font-semibold text-slate-900">Recent Payments</h2>
                        <p class="text-xs text-slate-500">Latest rental payments</p>
                    </div>
                    <a href="payments.php" class="text-sm font-medium text-indigo-600">View all</a>
                </div>

                <div class="divide-y divide-slate-100">
                    <?php foreach (array_slice($payments, 0, 4) as $payment): ?>
                        <div class="flex items-center justify-between p-4">
                            <div>
                                <p class="text-sm font-medium text-slate-800">
                                    <?= htmlspecialchars($payment['tenant'] ?? 'Tenant') ?>
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    <?= htmlspecialchars($payment['method'] ?? 'Payment') ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-slate-900">
                                    KES <?= number_format((float)($payment['amount'] ?? 0)) ?>
                                </p>
                                <span class="text-xs <?= ($payment['status'] ?? '') === 'Paid'
                                    ? 'text-green-600'
                                    : 'text-orange-600' ?>">
                                    <?= htmlspecialchars($payment['status'] ?? 'Pending') ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Maintenance -->
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between border-b border-slate-200 p-5">
                    <div>
                        <h2 class="font-semibold text-slate-900">Maintenance</h2>
                        <p class="text-xs text-slate-500">Current maintenance requests</p>
                    </div>
                    <a href="maintenance.php" class="text-sm font-medium text-indigo-600">View all</a>
                </div>

                <div class="p-5">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-orange-50 text-2xl">
                            🔧
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-900"><?= $maintenanceCount ?></p>
                            <p class="text-sm text-slate-500">Active requests</p>
                        </div>
                    </div>

                    <div class="mt-5">
                        <?php foreach (array_slice($maintenanceRequests, 0, 3) as $request): ?>
                            <div class="border-t border-slate-100 py-3">
                                <p class="text-sm font-medium text-slate-800">
                                    <?= htmlspecialchars($request['title'] ?? $request['issue'] ?? 'Maintenance request') ?>
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    <?= htmlspecialchars($request['property'] ?? '') ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>

    </main>

</div>

<!-- =========================================================
     CHART.JS
========================================================= -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
(function () {

    Chart.defaults.font.family =
        'ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif';
    Chart.defaults.font.size = 12;
    Chart.defaults.color     = '#64748b';

    /* --------------------------------------------------------
     * 1. Revenue — Line Chart
     * -------------------------------------------------------- */
    const revenueCtx = document.getElementById('revenueChart');

    if (revenueCtx) {
        const ctx = revenueCtx.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.35)');
        gradient.addColorStop(1, 'rgba(99, 102, 241, 0.00)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($revenueLabels) ?>,
                datasets: [{
                    label: 'Revenue (KES)',
                    data: <?= json_encode($revenueValues) ?>,
                    borderColor: '#6366f1',
                    backgroundColor: gradient,
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (context) {
                                const value = context.parsed.y || 0;
                                return 'KES ' + value.toLocaleString();
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { color: '#94a3b8' },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        border: { display: false },
                        ticks: {
                            color: '#94a3b8',
                            callback: function (value) {
                                if (value >= 1000000) return (value / 1000000) + 'M';
                                if (value >= 1000)    return (value / 1000) + 'K';
                                return value;
                            },
                        },
                    },
                },
            },
        });
    }

    /* --------------------------------------------------------
     * 2. Occupancy — Doughnut
     * -------------------------------------------------------- */
    const occupancyCtx = document.getElementById('occupancyChart');

    if (occupancyCtx) {
        new Chart(occupancyCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Occupied', 'Vacant'],
                datasets: [{
                    data: [<?= (int)$occupiedUnits ?>, <?= (int)$vacantUnits ?>],
                    backgroundColor: ['#10b981', '#f59e0b'],
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 8,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 16,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            color: '#475569',
                            font: { size: 12, weight: '500' },
                        },
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (context) {
                                const total = context.dataset.data.reduce(
                                    (a, b) => a + b, 0
                                );
                                const pct = total
                                    ? Math.round((context.parsed / total) * 100)
                                    : 0;
                                return ' ' + context.label + ': ' +
                                    context.parsed + ' (' + pct + '%)';
                            },
                        },
                    },
                },
            },
        });
    }

    /* --------------------------------------------------------
     * 3. Maintenance by Status — Bar
     * -------------------------------------------------------- */
    const maintenanceCtx = document.getElementById('maintenanceChart');

    if (maintenanceCtx) {
        new Chart(maintenanceCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Pending', 'In Progress', 'Resolved', 'Other'],
                datasets: [{
                    label: 'Requests',
                    data: [
                        <?= (int)$maintenanceByStatus['Pending'] ?>,
                        <?= (int)$maintenanceByStatus['In Progress'] ?>,
                        <?= (int)$maintenanceByStatus['Resolved'] ?>,
                        <?= (int)$maintenanceByStatus['Other'] ?>
                    ],
                    backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#94a3b8'],
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 48,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 12,
                        cornerRadius: 8,
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { color: '#64748b' },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        border: { display: false },
                        ticks: { color: '#94a3b8', precision: 0 },
                    },
                },
            },
        });
    }

    /* --------------------------------------------------------
     * 4. Payments by Method — Doughnut
     * -------------------------------------------------------- */
    const methodCtx = document.getElementById('methodChart');

    if (methodCtx) {
        const methodLabels = <?= json_encode(array_keys($paymentsByMethod)) ?>;
        const methodValues = <?= json_encode(array_values($paymentsByMethod)) ?>;

        const palette = [
            '#6366f1', '#10b981', '#f59e0b',
            '#ef4444', '#8b5cf6', '#06b6d4',
            '#ec4899', '#84cc16'
        ];

        new Chart(methodCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: methodLabels.length ? methodLabels : ['No payments'],
                datasets: [{
                    data: methodValues.length ? methodValues : [1],
                    backgroundColor: methodValues.length
                        ? palette.slice(0, methodLabels.length)
                        : ['#e2e8f0'],
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 8,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 14,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            color: '#475569',
                            font: { size: 12, weight: '500' },
                        },
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (context) {
                                if (!methodValues.length) {
                                    return ' No payments yet';
                                }
                                const value = context.parsed || 0;
                                return ' KES ' + value.toLocaleString();
                            },
                        },
                    },
                },
            },
        });
    }

})();
</script>

<?php
require_once __DIR__ . "/../../includes/footer.php";
?>