<?php

/**
 * ============================================================
 * PropertyPro - Tenant Directory
 * ============================================================
 */

require_once "../../includes/admin.php";
require_admin();

require_once "../../includes/data.php";

$pageTitle = "Tenants";

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";

/*
|--------------------------------------------------------------------------
| Safely prepare tenant display values
|--------------------------------------------------------------------------
*/

function tenant_property_name(array $tenant): string
{
    /*
    | Already formatted by the API/data layer
    */
    if (!empty($tenant['property'])) {
        return (string) $tenant['property'];
    }

    if (!empty($tenant['propertyName'])) {
        return (string) $tenant['propertyName'];
    }

    /*
    | Some API responses may return a populated property object.
    */
    if (isset($tenant['propertyId']) && is_array($tenant['propertyId'])) {
        return (string) (
            $tenant['propertyId']['name']
            ?? $tenant['propertyId']['propertyName']
            ?? '-'
        );
    }

    if (isset($tenant['propertyId']) && is_object($tenant['propertyId'])) {
        return (string) (
            $tenant['propertyId']->name
            ?? $tenant['propertyId']->propertyName
            ?? '-'
        );
    }

    return '-';
}

function tenant_unit_name(array $tenant): string
{
    /*
    | Already formatted by the API/data layer
    */
    if (!empty($tenant['unit'])) {
        return (string) $tenant['unit'];
    }

    if (!empty($tenant['unitName'])) {
        return (string) $tenant['unitName'];
    }

    /*
    | Some API responses may return a populated unit object.
    */
    if (isset($tenant['unitId']) && is_array($tenant['unitId'])) {
        return (string) (
            $tenant['unitId']['unitNumber']
            ?? $tenant['unitId']['name']
            ?? $tenant['unitId']['unit']
            ?? '-'
        );
    }

    if (isset($tenant['unitId']) && is_object($tenant['unitId'])) {
        return (string) (
            $tenant['unitId']->unitNumber
            ?? $tenant['unitId']->name
            ?? $tenant['unitId']->unit
            ?? '-'
        );
    }

    return '-';
}

function tenant_status_class(string $status): string
{
    $status = strtolower(trim($status));

    switch ($status) {
        case 'active':
            return 'bg-green-100 text-green-700';

        case 'inactive':
            return 'bg-slate-100 text-slate-600';

        case 'pending':
            return 'bg-yellow-100 text-yellow-700';

        case 'terminated':
            return 'bg-red-100 text-red-700';

        default:
            return 'bg-slate-100 text-slate-600';
    }
}

?>

<main class="min-h-screen bg-slate-50 lg:ml-64">

    <!-- =======================================================
         Header
         ======================================================= -->

    <header class="border-b border-slate-200 bg-white">

        <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">

            <div>
                <h1 class="text-lg font-semibold text-slate-900">
                    Tenants
                </h1>

                <p class="hidden text-xs text-slate-500 sm:block">
                    Manage registered tenants
                </p>
            </div>

            <a
                href="#"
                class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700"
            >
                + Add Tenant
            </a>

        </div>

    </header>


    <!-- =======================================================
         Main Content
         ======================================================= -->

    <div class="p-4 sm:p-6 lg:p-8">

        <div class="mb-6">

            <h2 class="text-2xl font-bold text-slate-900">
                Tenant Directory
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                View and manage your tenants.
            </p>

        </div>


        <!-- ===================================================
             Tenant Table
             =================================================== -->

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="overflow-x-auto">

                <table class="w-full text-left text-sm">

                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">

                        <tr>

                            <th class="px-6 py-4">
                                Tenant
                            </th>

                            <th class="px-6 py-4">
                                Property
                            </th>

                            <th class="px-6 py-4">
                                Unit
                            </th>

                            <th class="px-6 py-4">
                                Phone
                            </th>

                            <th class="px-6 py-4">
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        <?php if (empty($tenants)): ?>

                            <tr>

                                <td
                                    colspan="5"
                                    class="px-6 py-12 text-center"
                                >

                                    <div class="flex flex-col items-center">

                                        <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-2xl">
                                            👥
                                        </div>

                                        <p class="font-semibold text-slate-900">
                                            No tenants found
                                        </p>

                                        <p class="mt-1 text-sm text-slate-500">
                                            There are currently no registered tenants.
                                        </p>

                                    </div>

                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($tenants as $tenant): ?>

                                <?php

                                $tenantName = $tenant['name']
                                    ?? $tenant['fullName']
                                    ?? '-';

                                $tenantId = $tenant['id']
                                    ?? $tenant['_id']
                                    ?? $tenant['tenantId']
                                    ?? '';

                                $propertyName = tenant_property_name($tenant);

                                $unitName = tenant_unit_name($tenant);

                                $phone = $tenant['phone']
                                    ?? $tenant['mobile']
                                    ?? '-';

                                $status = $tenant['status']
                                    ?? 'Active';

                                $statusClass = tenant_status_class($status);

                                ?>

                                <tr class="transition hover:bg-slate-50">

                                    <!-- Tenant -->

                                    <td class="px-6 py-4">

                                        <?php if ($tenantId !== ''): ?>

                                            <a
                                                href="tenant-details.php?id=<?= urlencode((string) $tenantId) ?>"
                                                class="font-semibold text-indigo-600 hover:text-indigo-700"
                                            >
                                                <?= e($tenantName) ?>
                                            </a>

                                        <?php else: ?>

                                            <span class="font-semibold text-slate-900">
                                                <?= e($tenantName) ?>
                                            </span>

                                        <?php endif; ?>

                                        <?php if (!empty($tenant['email'])): ?>

                                            <p class="mt-1 text-xs text-slate-500">
                                                <?= e($tenant['email']) ?>
                                            </p>

                                        <?php endif; ?>

                                    </td>


                                    <!-- Property -->

                                    <td class="px-6 py-4 text-slate-700">

                                        <?= e($propertyName) ?>

                                    </td>


                                    <!-- Unit -->

                                    <td class="px-6 py-4 text-slate-700">

                                        <?= e($unitName) ?>

                                    </td>


                                    <!-- Phone -->

                                    <td class="px-6 py-4 text-slate-700">

                                        <?= e($phone) ?>

                                    </td>


                                    <!-- Status -->

                                    <td class="px-6 py-4">

                                        <span
                                            class="rounded-full px-3 py-1 text-xs font-medium <?= e($statusClass) ?>"
                                        >
                                            <?= e(ucfirst((string) $status)) ?>
                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</main>

<?php require_once "../../includes/footer.php"; ?>