<?php

/**
 * ============================================================
 * PropertyPro - Admin: System Health
 * ============================================================
 */

require_once __DIR__ . '/../../includes/api.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

require_once __DIR__ . '/_system_guard.php';

$pageTitle = 'System Health';

function pp_health_e($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function pp_health_ping(string $endpoint): array
{
    $start = microtime(true);

    try {

        $response = api_get($endpoint);

        $latency = (int) round(
            (microtime(true) - $start) * 1000
        );

        $status = (int)($response['status'] ?? 0);
        $ok = $status >= 200 && $status < 300;

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
            'latency_ms' => (int) round(
                (microtime(true) - $start) * 1000
            ),
            'status'     => 0,
            'error'      => $e->getMessage(),
        ];
    }
}

$checks = [
    'API /properties' => pp_health_ping('/properties'),
    'API /units'      => pp_health_ping('/units'),
    'API /payments'   => pp_health_ping('/payments'),
];

$tokenPresent = !empty($_SESSION['token']);
$apiBase      = api_base_url();

$allOk = true;

foreach ($checks as $c) {
    if (!$c['ok']) {
        $allOk = false;
        break;
    }
}

$statusLabel = $allOk ? 'Healthy' : 'Degraded';

$statusColor = $allOk
    ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
    : 'bg-red-50 text-red-700 ring-red-200';

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
            <span class="font-medium text-slate-700">Health</span>
        </nav>

        <!-- Header -->

        <div class="mb-6">

            <h1 class="text-2xl font-bold text-slate-900">
                System Health
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Live status of the PropertyPro API and session.
            </p>

        </div>

        <!-- Status banner -->

        <div class="mb-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">

            <div class="flex flex-wrap items-center justify-between gap-4">

                <div class="flex items-center gap-4">

                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold ring-1 <?= $statusColor ?>">

                        <span class="h-2 w-2 rounded-full <?= $allOk ? 'bg-emerald-500' : 'bg-red-500' ?>"></span>

                        <?= pp_health_e($statusLabel) ?>

                    </span>

                    <span class="text-sm text-slate-500">
                        Checked <?= date('H:i:s') ?>
                    </span>

                </div>

                <button
                    type="button"
                    onclick="location.reload()"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Re-check
                </button>

            </div>

        </div>

        <!-- Checks table -->

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">

                <h2 class="text-lg font-semibold text-slate-900">
                    API Endpoints
                </h2>

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
                                <?= pp_health_e($label) ?>
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
                                    <?= pp_health_e($c['error']) ?>
                                </td>
                            </tr>

                        <?php endif; ?>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <!-- Session / Config -->

        <div class="mt-6 grid gap-6 lg:grid-cols-2">

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
                            <?= pp_health_e(current_role() ?: '—') ?>
                        </dd>
                    </div>

                    <div class="flex justify-between">
                        <dt class="text-slate-500">Session ID</dt>
                        <dd class="font-mono text-xs text-slate-800">
                            <?= pp_health_e(substr(session_id(), 0, 12)) ?>…
                        </dd>
                    </div>

                </dl>

            </div>

            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">

                <h2 class="text-lg font-semibold text-slate-900">
                    Configuration
                </h2>

                <dl class="mt-4 space-y-3 text-sm">

                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">API base URL</dt>
                        <dd class="font-mono text-xs text-slate-800 break-all text-right max-w-[60%]">
                            <?= pp_health_e($apiBase) ?>
                        </dd>
                    </div>

                    <div class="flex justify-between">
                        <dt class="text-slate-500">Environment</dt>
                        <dd class="font-semibold text-slate-800">
                            <?= getenv('RENDER') === 'true' || getenv('RENDER_SERVICE_ID')
                                ? 'production'
                                : 'local' ?>
                        </dd>
                    </div>

                    <div class="flex justify-between">
                        <dt class="text-slate-500">Server time</dt>
                        <dd class="font-semibold text-slate-800">
                            <?= date('Y-m-d H:i:s') ?>
                        </dd>
                    </div>

                </dl>

            </div>

        </div>

    </div>

</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>