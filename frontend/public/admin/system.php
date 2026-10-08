<?php

/**
 * ============================================================
 * PropertyPro - Admin: System Logs
 * ============================================================
 *
 * Single page combining:
 *   - API health (endpoint reachability, latency)
 *   - Database connection + collections
 *   - PHP runtime + environment
 *   - Session info
 *
 * Guarded by OTP via _system_guard.php.
 */

require_once __DIR__ . '/../../includes/api.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

require_once __DIR__ . '/_system_guard.php';

$pageTitle = 'System Logs';

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function pp_sys_e($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function pp_sys_bytes($bytes): string
{
    $bytes = (int)$bytes;
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Ping an API endpoint and return status + latency.
 */
function pp_sys_ping(string $endpoint): array
{
    $start = microtime(true);

    try {
        $response = api_get($endpoint);
        $latency  = (int) round((microtime(true) - $start) * 1000);
        $status   = (int)($response['status'] ?? 0);
        $ok       = $status >= 200 && $status < 300;

        return [
            'ok'         => $ok,
            'latency_ms' => $latency,
            'status'     => $status,
            'error'      => $ok
                ? null
                : ($response['message'] ?? 'Request failed'),
        ];
    } catch (Throwable $e) {
        return [
            'ok'         => false,
            'latency_ms' => (int) round((microtime(true) - $start) * 1000),
            'status'     => 0,
            'error'      => $e->getMessage(),
        ];
    }
}

/*
|--------------------------------------------------------------------------
| 1. API health checks
|--------------------------------------------------------------------------
*/

$checks = [
    'API /properties' => pp_sys_ping('/properties'),
    'API /units'      => pp_sys_ping('/units'),
    'API /payments'   => pp_sys_ping('/payments'),
];

$allHealthy = true;
$totalLatency = 0;
$latencyCount = 0;

foreach ($checks as $c) {
    if (!$c['ok']) {
        $allHealthy = false;
    }
    $totalLatency += $c['latency_ms'];
    $latencyCount++;
}

$avgLatency = $latencyCount > 0
    ? (int) round($totalLatency / $latencyCount)
    : 0;

/*
|--------------------------------------------------------------------------
| 2. Database stats
|--------------------------------------------------------------------------
*/

$dbStats      = null;
$dbStatsError = null;

try {
    $response = api_get('/system/db-stats');
    $status   = (int)($response['status'] ?? 0);

    if ($status >= 200 && $status < 300 && !empty($response['data'])) {
        $dbStats = $response['data'];
    } else {
        $dbStatsError = $response['message']
            ?? 'Endpoint /system/db-stats not available (HTTP ' . $status . ')';
    }
} catch (Throwable $e) {
    $dbStatsError = $e->getMessage();
}

$dbConnected = is_array($dbStats)
    && ($dbStats['state'] ?? '') === 'connected';

$collectionCount = 0;
$totalDocuments  = 0;

if ($dbStats && !empty($dbStats['collections']) && is_array($dbStats['collections'])) {
    foreach ($dbStats['collections'] as $name => $count) {
        $collectionCount++;
        if (is_numeric($count)) {
            $totalDocuments += (int)$count;
        }
    }
}

/*
|--------------------------------------------------------------------------
| 3. Session + runtime
|--------------------------------------------------------------------------
*/

$tokenPresent = !empty($_SESSION['token']);
$apiBase      = api_base_url();
$isRender     = getenv('RENDER') === 'true' || getenv('RENDER_SERVICE_ID');

$loadedExtensions = get_loaded_extensions();
sort($loadedExtensions);

$requiredExtensions = ['curl', 'json', 'mbstring', 'openssl', 'session'];

$missingExtensions = array_values(
    array_diff($requiredExtensions, $loadedExtensions)
);

$curlVersion = 'not loaded';
if (function_exists('curl_version')) {
    $cv = curl_version();
    $curlVersion = $cv['version'] ?? 'unknown';
}

$runtime = [
    'PHP Version'        => PHP_VERSION,
    'PHP SAPI'           => PHP_SAPI,
    'Operating System'   => PHP_OS . ' ' . php_uname('r'),
    'Server Software'    => $_SERVER['SERVER_SOFTWARE'] ?? '—',
    'Server Time'        => date('Y-m-d H:i:s'),
    'Timezone'           => date_default_timezone_get(),
    'Memory Limit'       => ini_get('memory_limit'),
    'Memory Usage'       => pp_sys_bytes(memory_get_usage(true)),
    'Peak Memory'        => pp_sys_bytes(memory_get_peak_usage(true)),
    'Max Execution Time' => ini_get('max_execution_time') . ' s',
    'Curl Version'       => $curlVersion,
];

$environment = [
    'App Environment'    => $isRender ? 'production (Render)' : 'local',
    'API Base URL'       => $apiBase,
    'PROPERTYPRO_API_URL' => getenv('PROPERTYPRO_API_URL') ?: '(not set)',
    'Session Active'     => session_status() === PHP_SESSION_ACTIVE ? 'yes' : 'no',
    'JWT Token'          => $tokenPresent ? 'present' : 'missing',
    'Current Role'       => current_role() ?: '—',
];

$verifiedAt  = (int)($_SESSION['system_verified_at'] ?? 0);
$minutesLeft = max(0, (int)ceil(($verifiedAt + 1800 - time()) / 60));

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

?>

<main class="min-h-screen bg-slate-50 lg:ml-64">

    <div class="px-4 py-6 sm:px-6 lg:px-8">

        <!-- ============================================================
             HEADER
        ============================================================= -->

        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <p class="text-sm font-medium text-indigo-600">
                    Administration
                </p>

                <h1 class="mt-1 text-2xl font-bold text-slate-900">
                    System Logs
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Live health, database connection and runtime information.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <button
                    type="button"
                    onclick="location.reload()"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>

                <a
                    href="lock-system.php"
                    class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    Lock Access
                </a>
            </div>

        </div>

        <!-- Verified banner -->
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span>
                        Verified <?= date('H:i', $verifiedAt) ?> ·
                        session valid for another
                        <strong><?= $minutesLeft ?> min</strong>
                    </span>
                </div>
                <span class="text-xs text-emerald-700">
                    <?= pp_sys_e(current_user()['email'] ?? '') ?>
                </span>
            </div>
        </div>

        <!-- ============================================================
             KPI CARDS
        ============================================================= -->

        <div class="mb-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

            <!-- API status -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">API Status</p>
                        <p class="mt-2 text-2xl font-bold <?= $allHealthy ? 'text-emerald-600' : 'text-red-600' ?>">
                            <?= $allHealthy ? 'Healthy' : 'Degraded' ?>
                        </p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl text-2xl
                        <?= $allHealthy ? 'bg-emerald-50' : 'bg-red-50' ?>">
                        <?= $allHealthy ? '✅' : '⚠️' ?>
                    </div>
                </div>
                <p class="mt-3 text-xs text-slate-500">
                    Avg latency <strong><?= $avgLatency ?> ms</strong>
                </p>
            </div>

            <!-- Database -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Database</p>
                        <p class="mt-2 text-2xl font-bold <?= $dbConnected ? 'text-emerald-600' : 'text-slate-500' ?>">
                            <?= $dbConnected ? 'Connected' : 'Unknown' ?>
                        </p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl text-2xl
                        <?= $dbConnected ? 'bg-emerald-50' : 'bg-slate-50' ?>">
                        🗄️
                    </div>
                </div>
                <p class="mt-3 text-xs text-slate-500">
                    <?= $collectionCount ?> collection<?= $collectionCount === 1 ? '' : 's' ?>
                    ·
                    <?= number_format($totalDocuments) ?> docs
                </p>
            </div>

            <!-- Runtime -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">PHP</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">
                            <?= pp_sys_e(PHP_VERSION) ?>
                        </p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-2xl">
                        🐘
                    </div>
                </div>
                <p class="mt-3 text-xs <?= empty($missingExtensions) ? 'text-emerald-600' : 'text-red-600' ?>">
                    <?= empty($missingExtensions)
                        ? 'All required extensions loaded'
                        : count($missingExtensions) . ' extension(s) missing' ?>
                </p>
            </div>

            <!-- Environment -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Environment</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">
                            <?= $isRender ? 'Live' : 'Local' ?>
                        </p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 text-2xl">
                        <?= $isRender ? '☁️' : '💻' ?>
                    </div>
                </div>
                <p class="mt-3 text-xs text-slate-500">
                    Memory <strong><?= pp_sys_bytes(memory_get_usage(true)) ?></strong>
                </p>
            </div>

        </div>

        <!-- ============================================================
             API HEALTH
        ============================================================= -->

        <div class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        API Health
                    </h2>
                    <p class="text-xs text-slate-500">
                        Endpoint reachability and latency
                    </p>
                </div>
                <span class="text-xs text-slate-500">
                    Checked <?= date('H:i:s') ?>
                </span>
            </div>

            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Endpoint</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">HTTP</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Latency</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php foreach ($checks as $label => $c): ?>
                        <tr>
                            <td class="px-5 py-4 font-mono text-sm text-slate-800">
                                <?= pp_sys_e($label) ?>
                            </td>
                            <td class="px-5 py-4">
                                <?php if ($c['ok']): ?>
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        OK
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">
                                        FAIL
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-700">
                                <?= (int)$c['status'] ?>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-700">
                                <?= (int)$c['latency_ms'] ?> ms
                            </td>
                        </tr>
                        <?php if (!$c['ok'] && !empty($c['error'])): ?>
                            <tr>
                                <td colspan="4" class="bg-red-50/50 px-5 py-3 text-xs text-red-700">
                                    <?= pp_sys_e($c['error']) ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>

        <!-- ============================================================
             DATABASE
        ============================================================= -->

        <div class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        Database
                    </h2>
                    <p class="text-xs text-slate-500">
                        MongoDB connection and collections
                    </p>
                </div>
                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold ring-1
                    <?= $dbConnected
                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                        : 'bg-slate-50 text-slate-600 ring-slate-200' ?>">
                    <span class="h-2 w-2 rounded-full
                        <?= $dbConnected ? 'bg-emerald-500' : 'bg-slate-400' ?>"></span>
                    <?= $dbConnected ? 'Connected' : 'Unavailable' ?>
                </span>
            </div>

            <?php if ($dbStats): ?>

                <div class="grid gap-4 border-b border-slate-200 px-5 py-5 sm:grid-cols-4">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-500">Database</p>
                        <p class="mt-1 font-semibold text-slate-900">
                            <?= pp_sys_e($dbStats['db'] ?? '—') ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-500">Ping</p>
                        <p class="mt-1 font-semibold text-slate-900">
                            <?= isset($dbStats['pingMs'])
                                ? (int)$dbStats['pingMs'] . ' ms'
                                : '—' ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-500">Server Version</p>
                        <p class="mt-1 font-semibold text-slate-900">
                            <?= pp_sys_e($dbStats['serverVersion'] ?? '—') ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-500">Last Checked</p>
                        <p class="mt-1 font-semibold text-slate-900">
                            <?= pp_sys_e(
                                isset($dbStats['checkedAt'])
                                    ? date('H:i:s', strtotime($dbStats['checkedAt']))
                                    : '—'
                            ) ?>
                        </p>
                    </div>
                </div>

                <?php if (!empty($dbStats['collections'])): ?>

                    <div class="px-5 pt-5">
                        <h3 class="text-sm font-semibold text-slate-700">
                            Collections
                        </h3>
                    </div>

                    <table class="mt-3 min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Collection</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Documents</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php foreach ($dbStats['collections'] as $name => $count): ?>
                                <tr>
                                    <td class="px-5 py-3 font-mono text-sm text-slate-800">
                                        <?= pp_sys_e($name) ?>
                                    </td>
                                    <td class="px-5 py-3 text-right text-sm font-semibold text-slate-800">
                                        <?= $count === null ? '—' : number_format((int)$count) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php endif; ?>

            <?php else: ?>

                <div class="px-5 py-5">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        <p class="font-semibold">
                            Live DB stats endpoint not available.
                        </p>
                        <p class="mt-1">
                            Deploy
                            <code class="rounded bg-amber-100 px-1.5 py-0.5 font-mono">GET /api/system/db-stats</code>
                            on the backend to see real MongoDB collection counts here.
                        </p>
                        <?php if ($dbStatsError): ?>
                            <p class="mt-2 text-xs text-amber-700">
                                <?= pp_sys_e($dbStatsError) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

            <?php endif; ?>

        </div>

        <!-- ============================================================
             SESSION + ENVIRONMENT
        ============================================================= -->

        <div class="mb-6 grid gap-6 lg:grid-cols-2">

            <!-- Session -->
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">
                    Session
                </h2>

                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-slate-500">JWT token</dt>
                        <dd class="font-semibold <?= $tokenPresent ? 'text-emerald-600' : 'text-red-600' ?>">
                            <?= $tokenPresent ? 'Present' : 'Missing' ?>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Role</dt>
                        <dd class="font-semibold text-slate-800">
                            <?= pp_sys_e(current_role() ?: '—') ?>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Session ID</dt>
                        <dd class="font-mono text-xs text-slate-800">
                            <?= pp_sys_e(substr(session_id(), 0, 12)) ?>…
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Verified at</dt>
                        <dd class="font-semibold text-slate-800">
                            <?= $verifiedAt > 0 ? date('H:i:s', $verifiedAt) : '—' ?>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Expires in</dt>
                        <dd class="font-semibold text-slate-800">
                            <?= $minutesLeft ?> min
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Environment -->
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">
                    Environment
                </h2>

                <dl class="mt-4 space-y-3 text-sm">
                    <?php foreach ($environment as $label => $value): ?>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500 shrink-0">
                                <?= pp_sys_e($label) ?>
                            </dt>
                            <dd class="font-semibold text-slate-800 text-right break-all max-w-[65%]">
                                <?= pp_sys_e($value) ?>
                            </dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </div>

        </div>

        <!-- ============================================================
             PHP RUNTIME
        ============================================================= -->

        <div class="mb-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">
                PHP Runtime
            </h2>

            <dl class="mt-4 grid gap-3 sm:grid-cols-2">
                <?php foreach ($runtime as $label => $value): ?>
                    <div class="flex justify-between gap-3 border-b border-slate-100 pb-2">
                        <dt class="text-sm text-slate-500">
                            <?= pp_sys_e($label) ?>
                        </dt>
                        <dd class="text-sm font-semibold text-slate-800 text-right break-all max-w-[55%]">
                            <?= pp_sys_e($value) ?>
                        </dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </div>

        <!-- ============================================================
             REQUIRED EXTENSIONS
        ============================================================= -->

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">
                Required Extensions
            </h2>

            <?php if (!empty($missingExtensions)): ?>
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <p class="font-semibold">Missing extensions:</p>
                    <p class="mt-1 font-mono text-xs">
                        <?= pp_sys_e(implode(', ', $missingExtensions)) ?>
                    </p>
                </div>
            <?php endif; ?>

            <ul class="mt-4 space-y-2">
                <?php foreach ($requiredExtensions as $ext): ?>
                    <li class="flex items-center gap-2 text-sm">
                        <?php if (in_array($ext, $loadedExtensions, true)): ?>
                            <span class="text-emerald-600">✔</span>
                            <span class="text-slate-700">
                                <?= pp_sys_e($ext) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-red-600">✘</span>
                            <span class="text-red-700 font-semibold">
                                <?= pp_sys_e($ext) ?> (missing)
                            </span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

    </div>

</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>