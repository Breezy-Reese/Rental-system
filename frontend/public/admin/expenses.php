<?php

/**
 * ============================================================
 * PropertyPro - Expense Records
 * ============================================================
 */

require_once "../../includes/admin.php";
require_admin();

require_once "../../includes/api.php";

/*
|--------------------------------------------------------------------------
| Local helper functions
|--------------------------------------------------------------------------
*/

function expenses_escape($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function expenses_money($amount): string
{
    return 'KES ' . number_format(
        (float)($amount ?? 0),
        2
    );
}

/*
|--------------------------------------------------------------------------
| Page title
|--------------------------------------------------------------------------
*/

$pageTitle = "Expenses";

/*
|--------------------------------------------------------------------------
| Fetch expenses directly from the API
|--------------------------------------------------------------------------
|
| We intentionally do NOT load data.php here.
| The expenses page only needs /expenses.
|
*/

$apiResponse = api_get('/expenses');

$expenses = [];

if (
    isset($apiResponse['success']) &&
    $apiResponse['success'] === true
) {
    $expenses = $apiResponse['data'] ?? [];
}

/*
|--------------------------------------------------------------------------
| Normalize API data for display
|--------------------------------------------------------------------------
*/

$expenseRecords = [];

foreach ($expenses as $expense) {

    $property = $expense['propertyId'] ?? [];

    if (is_array($property)) {
        $propertyName = $property['name'] ?? '—';
    } else {
        $propertyName = '—';
    }

    $expenseRecords[] = [
        'id' => $expense['expenseId']
            ?? $expense['id']
            ?? $expense['_id']
            ?? '—',

        'description' => $expense['description']
            ?? '—',

        'property' => $propertyName,

        'location' => is_array($property)
            ? ($property['location'] ?? '')
            : '',

        'category' => $expense['category']
            ?? '—',

        'amount' => $expense['amount']
            ?? 0,

        'date' => $expense['expenseDate']
            ?? $expense['createdAt']
            ?? null,

        'status' => $expense['status']
            ?? 'Pending',
    ];
}

/*
|--------------------------------------------------------------------------
| Status badge helper
|--------------------------------------------------------------------------
*/

function expense_status_class(string $status): string
{
    switch (strtolower($status)) {

        case 'paid':
        case 'approved':
        case 'completed':
            return 'bg-emerald-100 text-emerald-700';

        case 'pending':
            return 'bg-amber-100 text-amber-700';

        case 'rejected':
        case 'cancelled':
            return 'bg-red-100 text-red-700';

        default:
            return 'bg-slate-100 text-slate-700';
    }
}

/*
|--------------------------------------------------------------------------
| Header
|--------------------------------------------------------------------------
*/

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";

?>

<main class="min-h-screen bg-slate-50">

    <!-- Mobile header -->
    <div class="lg:hidden bg-white border-b border-slate-200 px-4 py-3 flex items-center gap-3">

        <button
            id="mobileMenuButton"
            type="button"
            class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition"
            aria-label="Open navigation"
        >
            ☰
        </button>

        <div>
            <h1 class="text-lg font-bold text-slate-900">
                Expenses
            </h1>

            <p class="text-xs text-slate-500">
                Property expenses
            </p>
        </div>

    </div>

    <!-- Page content -->
    <div class="p-4 sm:p-6 lg:p-8">

        <!-- Page heading -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">

            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">
                    Expenses
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Track property expenses
                </p>
            </div>

            <a
                href="#"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-indigo-600 text-white font-semibold text-sm hover:bg-indigo-700 transition shadow-sm"
            >
                <span class="text-lg leading-none">+</span>
                Add Expense
            </a>

        </div>

        <!-- Expense records -->
        <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

            <div class="px-5 py-5 border-b border-slate-200">

                <h2 class="text-lg font-bold text-slate-900">
                    Expense Records
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Monitor property maintenance and operating expenses.
                </p>

            </div>

            <?php if (empty($expenseRecords)): ?>

                <div class="px-6 py-12 text-center">

                    <div class="text-4xl mb-3">
                        💰
                    </div>

                    <h3 class="text-lg font-semibold text-slate-900">
                        No expenses found
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        There are currently no expense records to display.
                    </p>

                </div>

            <?php else: ?>

                <!-- Desktop table -->
                <div class="hidden md:block overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Reference
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Description
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Property
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Category
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Amount
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Date
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Status
                                </th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">

                            <?php foreach ($expenseRecords as $expense): ?>

                                <tr class="hover:bg-slate-50 transition">

                                    <!-- Reference -->
                                    <td class="px-6 py-4 whitespace-nowrap">

                                        <span class="font-semibold text-slate-900">
                                            <?= expenses_escape($expense['id']) ?>
                                        </span>

                                    </td>

                                    <!-- Description -->
                                    <td class="px-6 py-4">

                                        <div class="text-sm font-medium text-slate-900">
                                            <?= expenses_escape($expense['description']) ?>
                                        </div>

                                    </td>

                                    <!-- Property -->
                                    <td class="px-6 py-4">

                                        <div class="text-sm font-medium text-slate-900">
                                            <?= expenses_escape($expense['property']) ?>
                                        </div>

                                        <?php if (!empty($expense['location'])): ?>

                                            <div class="text-xs text-slate-500 mt-0.5">
                                                <?= expenses_escape($expense['location']) ?>
                                            </div>

                                        <?php endif; ?>

                                    </td>

                                    <!-- Category -->
                                    <td class="px-6 py-4 whitespace-nowrap">

                                        <span class="text-sm text-slate-700">
                                            <?= expenses_escape($expense['category']) ?>
                                        </span>

                                    </td>

                                    <!-- Amount -->
                                    <td class="px-6 py-4 whitespace-nowrap">

                                        <span class="text-sm font-bold text-slate-900">
                                            <?= expenses_money($expense['amount']) ?>
                                        </span>

                                    </td>

                                    <!-- Date -->
                                    <td class="px-6 py-4 whitespace-nowrap">

                                        <?php

                                        $formattedDate = '—';

                                        if (!empty($expense['date'])) {

                                            $timestamp = strtotime(
                                                (string)$expense['date']
                                            );

                                            if ($timestamp !== false) {

                                                $formattedDate = date(
                                                    'd M Y',
                                                    $timestamp
                                                );
                                            }
                                        }

                                        ?>

                                        <span class="text-sm text-slate-700">
                                            <?= expenses_escape($formattedDate) ?>
                                        </span>

                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-4 whitespace-nowrap">

                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= expense_status_class($expense['status']) ?>"
                                        >
                                            <?= expenses_escape($expense['status']) ?>
                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

                <!-- Mobile cards -->
                <div class="md:hidden divide-y divide-slate-200">

                    <?php foreach ($expenseRecords as $expense): ?>

                        <?php

                        $formattedDate = '—';

                        if (!empty($expense['date'])) {

                            $timestamp = strtotime(
                                (string)$expense['date']
                            );

                            if ($timestamp !== false) {

                                $formattedDate = date(
                                    'd M Y',
                                    $timestamp
                                );
                            }
                        }

                        ?>

                        <div class="p-5">

                            <div class="flex items-start justify-between gap-3">

                                <div>

                                    <p class="text-sm font-bold text-slate-900">
                                        <?= expenses_escape($expense['id']) ?>
                                    </p>

                                    <p class="mt-1 text-sm text-slate-600">
                                        <?= expenses_escape($expense['description']) ?>
                                    </p>

                                </div>

                                <span
                                    class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= expense_status_class($expense['status']) ?>"
                                >
                                    <?= expenses_escape($expense['status']) ?>
                                </span>

                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-4">

                                <div>

                                    <p class="text-xs text-slate-400">
                                        Property
                                    </p>

                                    <p class="mt-1 text-sm font-medium text-slate-800">
                                        <?= expenses_escape($expense['property']) ?>
                                    </p>

                                </div>

                                <div>

                                    <p class="text-xs text-slate-400">
                                        Category
                                    </p>

                                    <p class="mt-1 text-sm font-medium text-slate-800">
                                        <?= expenses_escape($expense['category']) ?>
                                    </p>

                                </div>

                                <div>

                                    <p class="text-xs text-slate-400">
                                        Amount
                                    </p>

                                    <p class="mt-1 text-sm font-bold text-slate-900">
                                        <?= expenses_money($expense['amount']) ?>
                                    </p>

                                </div>

                                <div>

                                    <p class="text-xs text-slate-400">
                                        Date
                                    </p>

                                    <p class="mt-1 text-sm font-medium text-slate-800">
                                        <?= expenses_escape($formattedDate) ?>
                                    </p>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </div>

</main>

<?php

require_once "../../includes/footer.php";

?>