<?php

require_once "../../includes/admin.php";
require_admin();

require_once "../../includes/api.php";

/*
|--------------------------------------------------------------------------
| Handle status/response update
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['maintenance_id'])) {

    $id = trim($_POST['maintenance_id'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $response = trim($_POST['response'] ?? '');

    if ($id !== '') {
        api_put('/maintenance/' . $id, [
            'status' => $status,
            'response' => $response,
        ]);
    }

    header('Location: maintenance.php?updated=1');
    exit;
}

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

            <div class="w-9"></div>

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

        <?php if (isset($_GET['updated'])): ?>

            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                Request updated successfully.
            </div>

        <?php endif; ?>


        <div class="space-y-4">

            <?php if (empty($maintenanceRequests)): ?>

                <div class="rounded-xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
                    No maintenance requests found.
                </div>

            <?php endif; ?>

            <?php foreach ($maintenanceRequests as $request): ?>

                <?php

                $status = $request['status'] ?? 'Pending';

                $statusClass = match ($status) {
                    'Urgent' => 'bg-red-100 text-red-700',
                    'Assigned' => 'bg-blue-100 text-blue-700',
                    'In Progress' => 'bg-blue-100 text-blue-700',
                    'Completed' => 'bg-green-100 text-green-700',
                    'Cancelled' => 'bg-slate-200 text-slate-600',
                    default => 'bg-amber-100 text-amber-700'
                };

                $requestId = $request['_id'] ?? $request['id'] ?? '';

                ?>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                        <div class="flex items-start gap-4">

                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-xl">
                                🔧
                            </div>

                            <div>

                                <h3 class="font-semibold text-slate-900">
                                    <?= e($request['issue'] ?? 'Maintenance request') ?>
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    <?= e($request['property'] ?? 'N/A') ?>
                                    •
                                    <?= e($request['unit'] ?? 'N/A') ?>
                                </p>

                                <p class="mt-2 text-sm text-slate-600">
                                    Reported by:
                                    <?= e($request['tenant'] ?? '-') ?>
                                </p>

                                <?php if (!empty($request['description'])): ?>
                                    <p class="mt-2 text-sm text-slate-600">
                                        <?= e($request['description']) ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($request['response'])): ?>
                                    <div class="mt-3 rounded-lg bg-indigo-50 px-3 py-2 text-sm text-indigo-800">
                                        <span class="font-medium">Your response:</span>
                                        <?= e($request['response']) ?>
                                    </div>
                                <?php endif; ?>

                            </div>

                        </div>

                        <div class="flex flex-col items-end gap-3">

                            <span class="w-fit rounded-full px-3 py-1 text-xs font-medium <?= $statusClass ?>">
                                <?= e($status) ?>
                            </span>

                            <button
                                type="button"
                                onclick="openRespond('<?= e($requestId) ?>', '<?= e($status) ?>', <?= json_encode($request['response'] ?? '') ?>)"
                                class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                                Respond
                            </button>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

</main>


<!-- Respond Modal -->
<div
    id="respondModal"
    class="fixed inset-0 z-[100] hidden overflow-y-auto bg-black/50 px-4 py-8">

    <div class="mx-auto max-w-lg rounded-xl bg-white shadow-xl">

        <div class="flex items-center justify-between border-b px-6 py-5">

            <h2 class="text-lg font-semibold text-slate-900">
                Respond to Request
            </h2>

            <button
                type="button"
                onclick="document.getElementById('respondModal').classList.add('hidden')"
                class="text-2xl text-slate-400 hover:text-slate-700">
                ×
            </button>

        </div>

        <form method="POST" class="space-y-5 p-6">

            <input type="hidden" name="maintenance_id" id="respond_id">

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Status
                </label>

                <select
                    name="status"
                    id="respond_status"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="Pending">Pending</option>
                    <option value="Assigned">Assigned</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Response to tenant
                </label>

                <textarea
                    name="response"
                    id="respond_text"
                    rows="4"
                    placeholder="Let the tenant know what's happening..."
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-2">

                <button
                    type="button"
                    onclick="document.getElementById('respondModal').classList.add('hidden')"
                    class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>

                <button
                    type="submit"
                    class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                    Save Response
                </button>

            </div>

        </form>

    </div>

</div>

<script>
function openRespond(id, status, response) {
    document.getElementById('respond_id').value = id;
    document.getElementById('respond_status').value = status;
    document.getElementById('respond_text').value = response;
    document.getElementById('respondModal').classList.remove('hidden');
}
</script>

<?php require_once "../../includes/footer.php"; ?>