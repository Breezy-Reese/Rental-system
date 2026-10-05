<?php

$pageTitle = "Maintenance Requests";

require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../includes/api.php";

require_login();

if (current_role() !== "Customer") {
    header("Location: ../admin/dashboard.php");
    exit;
}

$submitError = "";
$submitSuccess = false;

/*
|--------------------------------------------------------------------------
| Submit maintenance request
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["issue"])
) {

    $issue =
        trim($_POST["issue"] ?? "");

    $priority =
        ucfirst(
            trim($_POST["priority"] ?? "Medium")
        );

    $description =
        trim($_POST["description"] ?? "");

    if ($issue === "") {

        $submitError =
            "Please describe the issue.";

    } else {

        $maintenanceId =
            "MR-" .
            date("YmdHis") .
            "-" .
            strtoupper(substr(uniqid(), -5));

        $result = api_post(
            "/customer/maintenance",
            [
                "maintenanceId" =>
                    $maintenanceId,

                "issue" =>
                    $issue,

                "priority" =>
                    $priority,

                "description" =>
                    $description,
            ]
        );

        if (!empty($result["success"])) {

            header(
                "Location: maintenance.php?submitted=1"
            );

            exit;

        } else {

            $submitError =
                $result["message"]
                ?? "Failed to submit request. Please try again.";
        }
    }
}

if (isset($_GET["submitted"])) {
    $submitSuccess = true;
}

/*
|--------------------------------------------------------------------------
| Customer data
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../../includes/data.php";

$user = current_user();

$customerRequests =
    $maintenanceRequests ?? [];

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

$totalRequests =
    count($customerRequests);

$pendingRequests = 0;
$completedRequests = 0;
$urgentRequests = 0;

foreach ($customerRequests as $request) {

    $requestStatus =
        strtolower(
            $request["status"] ?? ""
        );

    $requestPriority =
        strtolower(
            $request["priority"] ?? ""
        );

    if (
        $requestStatus === "pending" ||
        $requestStatus === "assigned" ||
        $requestStatus === "in progress"
    ) {
        $pendingRequests++;
    }

    if (
        $requestStatus === "completed" ||
        $requestStatus === "resolved"
    ) {
        $completedRequests++;
    }

    if ($requestPriority === "urgent") {
        $urgentRequests++;
    }
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function maintenance_escape($value): string
{
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";

?>

<div class="lg:pl-64">

    <main class="min-h-screen p-4 sm:p-6 lg:p-8">

        <!-- Header -->

        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h1 class="text-2xl font-bold text-slate-900">
                    Maintenance Requests
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Report and track maintenance issues for your rental unit.
                </p>

            </div>

            <button
                type="button"
                onclick="document.getElementById('requestModal').classList.remove('hidden')"
                class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
            >
                + New Request
            </button>

        </div>

        <?php if ($submitSuccess): ?>

            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                Your maintenance request was submitted successfully.
            </div>

        <?php endif; ?>

        <!-- Statistics -->

        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

            <div class="rounded-xl border bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Total Requests
                </p>

                <p class="mt-2 text-2xl font-bold text-slate-900">
                    <?= $totalRequests ?>
                </p>

            </div>

            <div class="rounded-xl border bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Active Requests
                </p>

                <p class="mt-2 text-2xl font-bold text-amber-600">
                    <?= $pendingRequests ?>
                </p>

            </div>

            <div class="rounded-xl border bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Urgent
                </p>

                <p class="mt-2 text-2xl font-bold text-red-600">
                    <?= $urgentRequests ?>
                </p>

            </div>

            <div class="rounded-xl border bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Completed
                </p>

                <p class="mt-2 text-2xl font-bold text-emerald-600">
                    <?= $completedRequests ?>
                </p>

            </div>

        </div>

        <!-- Requests -->

        <section class="overflow-hidden rounded-xl border bg-white shadow-sm">

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

                                $requestStatus =
                                    strtolower(
                                        $request["status"] ?? "pending"
                                    );

                                $requestPriority =
                                    strtolower(
                                        $request["priority"] ?? "normal"
                                    );

                                $statusClass = match ($requestStatus) {

                                    "completed",
                                    "resolved" =>
                                        "bg-emerald-50 text-emerald-700",

                                    "pending" =>
                                        "bg-amber-50 text-amber-700",

                                    "assigned",
                                    "in progress" =>
                                        "bg-blue-50 text-blue-700",

                                    default =>
                                        "bg-slate-100 text-slate-700",
                                };

                                $priorityClass = match ($requestPriority) {

                                    "urgent",
                                    "high" =>
                                        "bg-red-50 text-red-700",

                                    "medium" =>
                                        "bg-amber-50 text-amber-700",

                                    default =>
                                        "bg-slate-100 text-slate-700",
                                };

                                $requestId =
                                    $request["maintenanceId"]
                                    ?? $request["id"]
                                    ?? $request["_id"]
                                    ?? "N/A";

                                $issue =
                                    $request["issue"]
                                    ?? $request["description"]
                                    ?? "Maintenance issue";

                                $property =
                                    $request["property"]
                                    ?? "";

                                $unit =
                                    $request["unit"]
                                    ?? "";

                                /*
                                |--------------------------------------------------------------------------
                                | Handle populated API relationships
                                |--------------------------------------------------------------------------
                                */

                                if (
                                    isset($request["propertyId"]) &&
                                    is_array($request["propertyId"])
                                ) {
                                    $property =
                                        $request["propertyId"]["name"]
                                        ?? $property;
                                }

                                if (
                                    isset($request["unitId"]) &&
                                    is_array($request["unitId"])
                                ) {
                                    $unit =
                                        $request["unitId"]["unitNumber"]
                                        ?? $unit;
                                }

                                ?>

                                <tr class="hover:bg-slate-50">

                                    <td class="px-6 py-4 font-medium text-slate-900">
                                        <?= maintenance_escape($requestId) ?>
                                    </td>

                                    <td class="px-6 py-4">

                                        <div class="font-medium text-slate-900">
                                            <?= maintenance_escape($issue) ?>
                                        </div>

                                        <?php if (!empty($request["description"])): ?>

                                            <div class="mt-1 max-w-xs text-xs text-slate-500">
                                                <?= maintenance_escape($request["description"]) ?>
                                            </div>

                                        <?php endif; ?>

                                    </td>

                                    <td class="px-6 py-4 text-slate-600">
                                        <?= maintenance_escape(
                                            $property !== ""
                                                ? $property
                                                : "N/A"
                                        ) ?>
                                    </td>

                                    <td class="px-6 py-4 text-slate-600">
                                        <?= maintenance_escape(
                                            $unit !== ""
                                                ? $unit
                                                : "N/A"
                                        ) ?>
                                    </td>

                                    <td class="px-6 py-4">

                                        <span
                                            class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= $priorityClass ?>"
                                        >
                                            <?= maintenance_escape(
                                                ucfirst(
                                                    $request["priority"]
                                                    ?? "Normal"
                                                )
                                            ) ?>
                                        </span>

                                    </td>

                                    <td class="px-6 py-4">

                                        <span
                                            class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= $statusClass ?>"
                                        >
                                            <?= maintenance_escape(
                                                ucfirst(
                                                    $request["status"]
                                                    ?? "Pending"
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

                <div class="p-10 text-center">

                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-3xl">
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
                        class="mt-5 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        Submit Request
                    </button>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

<!-- Maintenance Modal -->

<div
    id="requestModal"
    class="fixed inset-0 z-[100] <?= $submitError ? "" : "hidden" ?> overflow-y-auto bg-black/50 px-4 py-8"
>

    <div class="mx-auto max-w-lg rounded-xl bg-white shadow-xl">

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
                class="text-2xl text-slate-400 hover:text-slate-700"
            >
                ×
            </button>

        </div>

        <?php if ($submitError): ?>

            <div class="mx-6 mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <?= maintenance_escape($submitError) ?>
            </div>

        <?php endif; ?>

        <form method="POST" class="space-y-5 p-6">

            <div>

                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Issue
                </label>

                <input
                    type="text"
                    name="issue"
                    required
                    value="<?= maintenance_escape($_POST["issue"] ?? "") ?>"
                    placeholder="e.g. Broken water pipe"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                >

            </div>

            <div>

                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Priority
                </label>

                <?php
                $selectedPriority =
                    $_POST["priority"] ?? "Medium";
                ?>

                <select
                    name="priority"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                >

                    <option value="Low" <?= $selectedPriority === "Low" ? "selected" : "" ?>>
                        Low
                    </option>

                    <option value="Medium" <?= $selectedPriority === "Medium" ? "selected" : "" ?>>
                        Medium
                    </option>

                    <option value="High" <?= $selectedPriority === "High" ? "selected" : "" ?>>
                        High
                    </option>

                    <option value="Urgent" <?= $selectedPriority === "Urgent" ? "selected" : "" ?>>
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
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                ><?= maintenance_escape($_POST["description"] ?? "") ?></textarea>

            </div>

            <div class="flex justify-end gap-3 pt-2">

                <button
                    type="button"
                    onclick="document.getElementById('requestModal').classList.add('hidden')"
                    class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                >
                    Submit Request
                </button>

            </div>

        </form>

    </div>

</div>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>