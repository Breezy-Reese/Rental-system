<?php

$pageTitle = "Maintenance Requests";

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../../includes/auth.php";

require_login();

/*
|--------------------------------------------------------------------------
| Customer-only access
|--------------------------------------------------------------------------
*/

if (current_role() !== 'Customer') {
    header("Location: ../admin/dashboard.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Load data
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../../includes/data.php";

/*
|--------------------------------------------------------------------------
| Current customer
|--------------------------------------------------------------------------
*/

$user = current_user();

$customerName = $user['name'] ?? '';

/*
|--------------------------------------------------------------------------
| Get customer's maintenance requests
|--------------------------------------------------------------------------
*/

$customerRequests = [];

foreach ($maintenanceRequests as $request) {

    if (
        isset($request['tenant']) &&
        strcasecmp($request['tenant'], $customerName) === 0
    ) {
        $customerRequests[] = $request;
    }
}

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

$totalRequests = count($customerRequests);

$pendingRequests = 0;
$completedRequests = 0;
$urgentRequests = 0;

foreach ($customerRequests as $request) {

    $status = strtolower($request['status'] ?? '');
    $priority = strtolower($request['priority'] ?? '');

    if (
        $status === 'pending' ||
        $status === 'assigned' ||
        $status === 'in progress'
    ) {
        $pendingRequests++;
    }

    if (
        $status === 'completed' ||
        $status === 'resolved'
    ) {
        $completedRequests++;
    }

    if ($priority === 'urgent') {
        $urgentRequests++;
    }
}

/*
|--------------------------------------------------------------------------
| Page layout
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";

?>

<main class="flex-1 lg:ml-64">

    <?php require_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div class="p-4 sm:p-6 lg:p-8">

        <!-- Page Header -->
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h1 class="text-2xl font-bold text-slate-900">
                    Maintenance Requests
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Report and track maintenance issues for your rental unit.
                </p>

            </div>

            <!-- New Request -->
            <button
                type="button"
                onclick="document.getElementById('requestModal').classList.remove('hidden')"
                class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">

                + New Request

            </button>

        </div>


        <!-- Statistics -->
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

            <!-- Total -->
            <div class="rounded-xl border bg-white p-5">

                <p class="text-sm text-slate-500">
                    Total Requests
                </p>

                <p class="mt-2 text-2xl font-bold text-slate-900">
                    <?= $totalRequests ?>
                </p>

            </div>


            <!-- Pending -->
            <div class="rounded-xl border bg-white p-5">

                <p class="text-sm text-slate-500">
                    Active Requests
                </p>

                <p class="mt-2 text-2xl font-bold text-amber-600">
                    <?= $pendingRequests ?>
                </p>

            </div>


            <!-- Urgent -->
            <div class="rounded-xl border bg-white p-5">

                <p class="text-sm text-slate-500">
                    Urgent
                </p>

                <p class="mt-2 text-2xl font-bold text-red-600">
                    <?= $urgentRequests ?>
                </p>

            </div>


            <!-- Completed -->
            <div class="rounded-xl border bg-white p-5">

                <p class="text-sm text-slate-500">
                    Completed
                </p>

                <p class="mt-2 text-2xl font-bold text-emerald-600">
                    <?= $completedRequests ?>
                </p>

            </div>

        </div>


        <!-- Requests -->
        <div class="overflow-hidden rounded-xl border bg-white">

            <div class="border-b px-6 py-5">

                <h2 class="text-lg font-semibold text-slate-900">
                    My Requests
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Maintenance requests submitted for your rental unit.
                </p>

            </div>


            <?php if (!empty($customerRequests)): ?>

                <div class="overflow-x-auto">

                    <table class="w-full text-left text-sm">

                        <thead class="bg-slate-50 text-xs uppercase text-slate-500">

                            <tr>

                                <th class="px-6 py-4">
                                    Request
                                </th>

                                <th class="px-6 py-4">
                                    Issue
                                </th>

                                <th class="px-6 py-4">
                                    Property
                                </th>

                                <th class="px-6 py-4">
                                    Unit
                                </th>

                                <th class="px-6 py-4">
                                    Priority
                                </th>

                                <th class="px-6 py-4">
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y">

                            <?php foreach ($customerRequests as $request): ?>

                                <?php

                                $status = strtolower($request['status'] ?? 'pending');

                                $priority = strtolower($request['priority'] ?? 'normal');

                                $statusClass = match ($status) {

                                    'completed',
                                    'resolved'
                                        => 'bg-emerald-50 text-emerald-700',

                                    'pending'
                                        => 'bg-amber-50 text-amber-700',

                                    'assigned',
                                    'in progress'
                                        => 'bg-blue-50 text-blue-700',

                                    default
                                        => 'bg-slate-100 text-slate-700',
                                };


                                $priorityClass = match ($priority) {

                                    'urgent',
                                    'high'
                                        => 'bg-red-50 text-red-700',

                                    'medium'
                                        => 'bg-amber-50 text-amber-700',

                                    default
                                        => 'bg-slate-100 text-slate-700',
                                };

                                ?>

                                <tr class="hover:bg-slate-50">

                                    <!-- Request ID -->
                                    <td class="px-6 py-4 font-medium text-slate-900">

                                        <?= htmlspecialchars(
                                            $request['id'] ?? 'N/A'
                                        ) ?>

                                    </td>


                                    <!-- Issue -->
                                    <td class="px-6 py-4">

                                        <div class="font-medium text-slate-900">

                                            <?= htmlspecialchars(
                                                $request['issue']
                                                ?? $request['description']
                                                ?? 'Maintenance issue'
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- Property -->
                                    <td class="px-6 py-4 text-slate-600">

                                        <?= htmlspecialchars(
                                            $request['property']
                                            ?? 'N/A'
                                        ) ?>

                                    </td>


                                    <!-- Unit -->
                                    <td class="px-6 py-4 text-slate-600">

                                        <?= htmlspecialchars(
                                            $request['unit']
                                            ?? 'N/A'
                                        ) ?>

                                    </td>


                                    <!-- Priority -->
                                    <td class="px-6 py-4">

                                        <span
                                            class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= $priorityClass ?>">

                                            <?= htmlspecialchars(
                                                ucfirst(
                                                    $request['priority']
                                                    ?? 'Normal'
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- Status -->
                                    <td class="px-6 py-4">

                                        <span
                                            class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= $statusClass ?>">

                                            <?= htmlspecialchars(
                                                ucfirst(
                                                    $request['status']
                                                    ?? 'Pending'
                                                )
                                            ) ?>

                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <!-- No Requests -->
                <div class="p-10 text-center">

                    <div
                        class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-3xl">
                        🔧
                    </div>

                    <h3 class="mt-4 text-lg font-semibold text-slate-900">
                        No Maintenance Requests
                    </h3>

                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                        You haven't submitted any maintenance requests yet.
                    </p>

                    <button
                        type="button"
                        onclick="document.getElementById('requestModal').classList.remove('hidden')"
                        class="mt-5 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">

                        Submit Request

                    </button>

                </div>

            <?php endif; ?>

        </div>

    </div>

</main>


<!-- ============================================================= -->
<!-- NEW MAINTENANCE REQUEST MODAL -->
<!-- ============================================================= -->

<div
    id="requestModal"
    class="fixed inset-0 z-[100] hidden overflow-y-auto bg-black/50 px-4 py-8">

    <div class="mx-auto max-w-lg rounded-xl bg-white shadow-xl">

        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b px-6 py-5">

            <div>

                <h2 class="text-lg font-semibold text-slate-900">
                    New Maintenance Request
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Report an issue with your rental unit.
                </p>

            </div>

            <button
                type="button"
                onclick="document.getElementById('requestModal').classList.add('hidden')"
                class="text-2xl text-slate-400 hover:text-slate-700">

                ×

            </button>

        </div>


        <!-- Form -->
        <form method="POST" class="space-y-5 p-6">

            <div>

                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Issue
                </label>

                <input
                    type="text"
                    name="issue"
                    required
                    placeholder="e.g. Broken water pipe"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">

            </div>


            <div>

                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Priority
                </label>

                <select
                    name="priority"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">

                    <option value="normal">
                        Normal
                    </option>

                    <option value="medium">
                        Medium
                    </option>

                    <option value="high">
                        High
                    </option>

                    <option value="urgent">
                        Urgent
                    </option>

                </select>

            </div>


            <div>

                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Description
                </label>

                <textarea
                    name="description"
                    rows="4"
                    placeholder="Describe the problem..."
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"></textarea>

            </div>


            <div class="flex justify-end gap-3 pt-2">

                <button
                    type="button"
                    onclick="document.getElementById('requestModal').classList.add('hidden')"
                    class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">

                    Cancel

                </button>

                <button
                    type="submit"
                    class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">

                    Submit Request

                </button>

            </div>

        </form>

    </div>

</div>


<?php require_once __DIR__ . "/../../includes/footer.php"; ?>