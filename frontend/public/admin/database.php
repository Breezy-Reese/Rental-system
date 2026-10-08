<?php

/**
 * ============================================================
 * PropertyPro - Admin: Database Status
 * ============================================================
 */

require_once __DIR__ . '/../../includes/api.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

require_once __DIR__ . '/_system_guard.php';

$pageTitle = 'Database';

function pp_db_e($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function pp_db_count(string $endpoint, array $keys = ['data']): ?int
{
    try {

        $response = api_get($endpoint);

        if (!is_array($response)) {
            return null;
        }

        $status = (int)($response['status'] ?? 0);

        if ($status < 200 || $status >= 300) {
            return null;
        }

        foreach ($keys as $key) {

            if (
                isset($response[$key]) &&
                is_array($response[$key])
            ) {
                return count($response[$key]);
            }
        }

        if (array_is_list($response)) {
            return count($response);
        }

        return null;

    } catch (Throwable $e) {
        return null;
    }
}

$dbStats = null;
$dbStatsError = null;

try {

    $response = api_get('/system/db-stats');

    $status = (int)($response['status'] ?? 0);

    if (
        $status >= 200 &&
        $status < 300 &&
        !empty($response['data'])
    ) {
        $dbStats = $response['data'];
    } else {
        $dbStatsError = $response['message']
            ?? 'Endpoint /system/db-stats not available (HTTP ' . $status . ')';
    }

} catch (Throwable $e) {
    $dbStatsError = $e->getMessage();
}

$estimatedCounts = [
    'Properties'        => pp_db_count('/properties'),
    'Units'             => pp_db_count('/units'),
    'Tenants'           => pp_db_count('/tenants'),
    'Payments'          => pp_db_count('/payments'),
    'Leases'            => pp_db_count('/leases'),
    'Expenses'          => pp_db_count('/expenses'),
    'Maintenance'       => pp_db_count('/maintenance'),
    'Customers (admin)' => pp_db_count('/admin/customers', ['customers', 'users', 'data']),
    'Notifications'     => pp_db_count('/notifications'),
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

?>

<main class="min-h-screen bg-slate-50 lg:ml-64">

    <div class="px-4 py-6 sm:px-6 lg:px-8">

        <!-- Breadcrumb -->
        <nav class="mb-4 flex items-center gap-2 text-sm text-slate-500">
            <a href="system.php" class="hover:text-indigo-600">
                System
            </a>
            <span>/</span>
            <span class="font-medium text-slate-700">Database</span>
        </nav>

        <!-- Header -->

        <div class="mb-6">

            <h1 class="text-2xl font-bold text-slate-900">
                Database
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                MongoDB connection and collection stats.
            </p>

        </div>

        <?php if ($dbStats === null): ?>

            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">

                <p class="font-semibold">
                    Live DB stats endpoint not available.
                </p>

                <p class="mt-1">
                    Showing <strong>estimated counts</strong> from existing API endpoints.
                    To see real MongoDB stats (connection status, ping, per-collection doc counts),
                    add
                    <code class="rounded bg-amber-100 px-1.5 py-0.5 font-mono">GET /api/system/db-stats</code>
                    on the backend.
                </p>

                <?php if ($dbStatsError): ?>
                    <p class="mt-2 text-xs text-amber-700">
                        <?= pp_db_e($dbStatsError) ?>
                    </p>
                <?php endif; ?>

            </div>

        <?php endif; ?>

        <!-- Connection status -->

        <div class="mb-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">

            <div class="flex items-center gap-3">

                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold ring-1
                    <?= $dbStats
                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                        : 'bg-slate-50 text-slate-600 ring-slate-200' ?>">

                    <span class="h-2 w-2 rounded-full
                        <?= $dbStats ? 'bg-emerald-500' : 'bg-slate-400' ?>"></span>

                    <?= $dbStats
                        ? 'Connected'
                        : 'Estimated (endpoint unavailable)' ?>

                </span>

                <?php if ($dbStats): ?>
                    <span class="text-sm text-slate-500">
                        <?= pp_db_e($dbStats['db'] ?? $dbStats['database'] ?? 'MongoDB') ?>
                    </span>

                    <?php if (!empty($dbStats['pingMs'])): ?>
                        <span class="text-xs text-slate-400">
                            · ping <?= (int)$dbStats['pingMs'] ?> ms
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($dbStats['serverVersion'])): ?>
                        <span class="text-xs text-slate-400">
                            · v<?= pp_db_e($dbStats['serverVersion']) ?>
                        </span>
                    <?php endif; ?>
                <?php endif; ?>

            </div>

        </div>

        <!-- Collections table -->

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">

                <h2 class="text-lg font-semibold text-slate-900">
                    Collections
                </h2>

                <p class="text-xs text-slate-500 mt-1">
                    <?= $dbStats
                        ? 'From live DB stats endpoint'
                        : 'Estimated from API list endpoints' ?>
                </p>

            </div>

            <table class="min-w-full divide-y divide-slate-200">

                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Collection</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Documents</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200">

                    <?php if ($dbStats && !empty($dbStats['collections'])): ?>

                        <?php foreach ($dbStats['collections'] as $name => $count): ?>
                            <tr>
                                <td class="px-5 py-4 font-mono text-sm text-slate-800">
                                    <?= pp_db_e($name) ?>
                                </td>
                                <td class="px-5 py-4 text-right text-sm font-semibold text-slate-800">
                                    <?= $count === null ? '—' : number_format((int)$count) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php else: ?>

                        <?php foreach ($estimatedCounts as $label => $count): ?>
                            <tr>
                                <td class="px-5 py-4 font-mono text-sm text-slate-800">
                                    <?= pp_db_e($label) ?>
                                </td>
                                <td class="px-5 py-4 text-right text-sm font-semibold text-slate-800">
                                    <?= $count === null ? '—' : number_format($count) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <div class="mt-6 flex justify-end">

            <button
                type="button"
                onclick="location.reload()"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Refresh
            </button>

        </div>

    </div>

</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>