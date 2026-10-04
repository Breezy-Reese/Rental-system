<?php

/**
 * ============================================================
 * PropertyPro - Lease Management
 * ============================================================
 */

require_once "../../includes/admin.php";
require_admin();

require_once "../../includes/data.php";

/*
|--------------------------------------------------------------------------
| Handle lease assignment
|--------------------------------------------------------------------------
*/

$leaseMessage = '';
$leaseMessageType = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['assign_lease'])
) {

    $customerUserId =
        trim($_POST['userId'] ?? '');

    $propertyId =
        trim($_POST['propertyId'] ?? '');

    $unitId =
        trim($_POST['unitId'] ?? '');

    $leaseId =
        trim($_POST['leaseId'] ?? '');

    $startDate =
        trim($_POST['startDate'] ?? '');

    $endDate =
        trim($_POST['endDate'] ?? '');

    $deposit =
        (float) (
            $_POST['deposit']
            ?? 0
        );

    /*
     * Validate required fields.
     */
    if (
        $customerUserId === '' ||
        $propertyId === '' ||
        $unitId === '' ||
        $leaseId === '' ||
        $startDate === '' ||
        $endDate === ''
    ) {

        $leaseMessage =
            'Please fill in all required lease fields.';

        $leaseMessageType =
            'error';

    } else {

        /*
         * Send the CUSTOMER USER ID.
         *
         * The backend creates the Tenant automatically
         * if this customer does not have one yet.
         */
        $assignmentResponse =
            api_post(
                '/leases/assign-customer',
                [
                    'userId' =>
                        $customerUserId,

                    'propertyId' =>
                        $propertyId,

                    'unitId' =>
                        $unitId,

                    'leaseId' =>
                        $leaseId,

                    'startDate' =>
                        $startDate,

                    'endDate' =>
                        $endDate,

                    'deposit' =>
                        $deposit,
                ]
            );

        if (
            isset($assignmentResponse['success']) &&
            $assignmentResponse['success'] === true
        ) {

            $leaseMessage =
                $assignmentResponse['message']
                ?? 'Lease assigned successfully.';

            $leaseMessageType =
                'success';

            /*
             * Reload all administrator data after
             * successful assignment.
             */
            $propertyResponse =
                api_get('/properties');

            $unitResponse =
                api_get('/units');

            $tenantResponse =
                api_get('/tenants');

            $leaseResponse =
                api_get('/leases');

            $customersResponse =
                api_get('/admin/customers');

            $properties =
                api_rows(
                    $propertyResponse,
                    'properties'
                );

            $units =
                api_rows(
                    $unitResponse,
                    'units'
                );

            $tenants =
                api_rows(
                    $tenantResponse,
                    'tenants'
                );

            $leases =
                api_rows(
                    $leaseResponse,
                    'leases'
                );

            $customers =
                api_rows(
                    $customersResponse,
                    'customers'
                );

            if (empty($customers)) {
                $customers =
                    api_rows(
                        $customersResponse,
                        'users'
                    );
            }

        } else {

            $leaseMessage =
                $assignmentResponse['message']
                ?? 'Unable to assign the lease.';

            $leaseMessageType =
                'error';
        }
    }
}

/*
|--------------------------------------------------------------------------
| Normalize properties for this page
|--------------------------------------------------------------------------
*/

foreach ($properties as &$property) {

    $property['_id'] =
        $property['_id']
        ?? $property['id']
        ?? '';

    $property['id'] =
        $property['id']
        ?? $property['propertyId']
        ?? $property['_id']
        ?? '';

    $property['name'] =
        $property['name']
        ?? $property['propertyName']
        ?? 'Unnamed Property';

    $property['location'] =
        $property['location']
        ?? '';
}

unset($property);

/*
|--------------------------------------------------------------------------
| Normalize units for this page
|--------------------------------------------------------------------------
*/

foreach ($units as &$unit) {

    $unit['_id'] =
        $unit['_id']
        ?? $unit['id']
        ?? '';

    $unit['id'] =
        $unit['id']
        ?? $unit['unitId']
        ?? $unit['_id']
        ?? '';

    $unit['unitNumber'] =
        $unit['unitNumber']
        ?? 'Unnamed Unit';

    $unit['status'] =
        $unit['status']
        ?? 'Vacant';

    $unit['rent'] =
        (float) (
            $unit['rent']
            ?? $unit['monthlyRent']
            ?? 0
        );

    if (
        isset($unit['propertyId']) &&
        is_array($unit['propertyId'])
    ) {

        $unit['propertyIdValue'] =
            $unit['propertyId']['_id']
            ?? $unit['propertyId']['id']
            ?? '';

        $unit['propertyName'] =
            $unit['propertyId']['name']
            ?? '';

    } else {

        $unit['propertyIdValue'] =
            $unit['propertyIdValue']
            ?? $unit['propertyId']
            ?? '';

        $unit['propertyName'] =
            $unit['property']
            ?? '';
    }
}

unset($unit);

/*
|--------------------------------------------------------------------------
| Normalize customers
|--------------------------------------------------------------------------
*/

foreach ($customers as &$customer) {

    $customer['_id'] =
        $customer['_id']
        ?? $customer['id']
        ?? $customer['userId']
        ?? '';

    $customer['id'] =
        $customer['id']
        ?? $customer['userId']
        ?? $customer['_id']
        ?? '';

    $customer['userId'] =
        $customer['userId']
        ?? $customer['_id']
        ?? $customer['id']
        ?? '';

    $customer['name'] =
        $customer['name']
        ?? trim(
            ($customer['firstName'] ?? '')
            . ' '
            . ($customer['lastName'] ?? '')
        );

    $customer['email'] =
        $customer['email']
        ?? '';

    $customer['phone'] =
        $customer['phone']
        ?? '';

    $customer['role'] =
        $customer['role']
        ?? 'Customer';

    $customer['status'] =
        $customer['status']
        ?? 'Active';
}

unset($customer);

/*
|--------------------------------------------------------------------------
| Available units
|--------------------------------------------------------------------------
*/

$availableUnits = [];

foreach ($units as $unit) {

    $status =
        strtolower(
            trim(
                $unit['status'] ?? ''
            )
        );

    if (
        $status === 'vacant' ||
        $status === 'available' ||
        $status === ''
    ) {

        $availableUnits[] =
            $unit;
    }
}

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

$pageTitle = "Leases";

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
?>

<main class="min-h-screen bg-slate-50 lg:ml-64">

    <!-- Header -->
    <header class="border-b border-slate-200 bg-white">

        <div class="flex min-h-16 items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">

            <div class="flex items-center gap-3">

                <button
                    id="mobileMenuButton"
                    type="button"
                    aria-label="Open navigation menu"
                    class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden"
                >
                    ☰
                </button>

                <div>

                    <h1 class="text-lg font-semibold text-slate-900">
                        Leases
                    </h1>

                    <p class="hidden text-xs text-slate-500 sm:block">
                        Manage tenant leases
                    </p>

                </div>

            </div>

            <a
                href="#new-lease"
                class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
            >
                + New Lease
            </a>

        </div>

    </header>

    <div class="p-4 sm:p-6 lg:p-8">

        <!-- Page heading -->
        <div class="mb-6">

            <h2 class="text-2xl font-bold text-slate-900">
                Lease Agreements
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Assign properties and units to registered customers.
            </p>

        </div>

        <!-- Messages -->
        <?php if ($leaseMessage !== ''): ?>

            <div
                class="mb-6 rounded-xl border p-4
                <?= $leaseMessageType === 'success'
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                    : 'border-red-200 bg-red-50 text-red-800'
                ?>"
            >

                <div class="flex items-start gap-3">

                    <div class="text-lg">
                        <?= $leaseMessageType === 'success' ? '✓' : '!' ?>
                    </div>

                    <div>

                        <p class="font-semibold">
                            <?= e($leaseMessage) ?>
                        </p>

                    </div>

                </div>

            </div>

        <?php endif; ?>


        <!-- New Lease -->
        <section
            id="new-lease"
            class="mb-8 rounded-xl border border-slate-200 bg-white shadow-sm"
        >

            <div class="border-b border-slate-200 px-5 py-4">

                <h3 class="text-lg font-semibold text-slate-900">
                    Create New Lease
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Select a registered customer and assign a vacant unit.
                </p>

            </div>

            <form
                method="POST"
                action=""
                class="p-5"
            >

                <input
                    type="hidden"
                    name="assign_lease"
                    value="1"
                >

                <div class="grid gap-5 md:grid-cols-2">

                    <!-- Customer -->
                    <div>

                        <label
                            for="userId"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Customer
                        </label>

                        <select
                            id="userId"
                            name="userId"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >

                            <option value="">
                                Select customer
                            </option>

                            <?php foreach ($customers as $customer): ?>

                                <?php

                                $customerId =
                                    $customer['userId']
                                    ?? $customer['_id']
                                    ?? $customer['id']
                                    ?? '';

                                $customerName =
                                    trim(
                                        $customer['name']
                                        ?? ''
                                    );

                                if ($customerName === '') {

                                    $customerName =
                                        trim(
                                            ($customer['firstName'] ?? '')
                                            . ' '
                                            . ($customer['lastName'] ?? '')
                                        );
                                }

                                if ($customerName === '') {
                                    $customerName = 'Unnamed Customer';
                                }

                                $customerEmail =
                                    $customer['email']
                                    ?? '';

                                $customerStatus =
                                    strtolower(
                                        trim(
                                            $customer['status']
                                            ?? 'active'
                                        )
                                    );

                                ?>

                                <?php if ($customerId !== ''): ?>

                                    <option
                                        value="<?= e($customerId) ?>"
                                    >
                                        <?= e($customerName) ?>
                                        <?php if ($customerEmail !== ''): ?>
                                            — <?= e($customerEmail) ?>
                                        <?php endif; ?>
                                    </option>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        </select>

                        <?php if (empty($customers)): ?>

                            <p class="mt-2 text-xs text-amber-600">
                                No registered customers were found.
                            </p>

                        <?php else: ?>

                            <p class="mt-2 text-xs text-slate-500">
                                <?= count($customers) ?>
                                registered customer(s) available.
                            </p>

                        <?php endif; ?>

                    </div>


                    <!-- Property -->
                    <div>

                        <label
                            for="propertyId"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Property
                        </label>

                        <select
                            id="propertyId"
                            name="propertyId"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >

                            <option value="">
                                Select property
                            </option>

                            <?php foreach ($properties as $property): ?>

                                <?php
                                $propertyId =
                                    $property['_id']
                                    ?? $property['id']
                                    ?? '';

                                $propertyName =
                                    $property['name']
                                    ?? 'Unnamed Property';
                                ?>

                                <?php if ($propertyId !== ''): ?>

                                    <option
                                        value="<?= e($propertyId) ?>"
                                    >
                                        <?= e($propertyName) ?>

                                        <?php if (!empty($property['location'])): ?>
                                            — <?= e($property['location']) ?>
                                        <?php endif; ?>

                                    </option>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- Unit -->
                    <div>

                        <label
                            for="unitId"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Vacant Unit
                        </label>

                        <select
                            id="unitId"
                            name="unitId"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >

                            <option value="">
                                Select vacant unit
                            </option>

                            <?php foreach ($availableUnits as $unit): ?>

                                <?php

                                $unitId =
                                    $unit['_id']
                                    ?? $unit['id']
                                    ?? '';

                                $unitNumber =
                                    $unit['unitNumber']
                                    ?? 'Unnamed Unit';

                                $propertyId =
                                    $unit['propertyIdValue']
                                    ?? '';

                                $propertyName =
                                    $unit['propertyName']
                                    ?? '';

                                $rent =
                                    (float) (
                                        $unit['rent']
                                        ?? 0
                                    );

                                ?>

                                <?php if ($unitId !== ''): ?>

                                    <option
                                        value="<?= e($unitId) ?>"
                                        data-property="<?= e($propertyId) ?>"
                                        data-rent="<?= e($rent) ?>"
                                    >
                                        <?= e($unitNumber) ?>

                                        <?php if ($propertyName !== ''): ?>
                                            — <?= e($propertyName) ?>
                                        <?php endif; ?>

                                        <?php if ($rent > 0): ?>
                                            — KSh <?= number_format($rent, 2) ?>
                                        <?php endif; ?>

                                    </option>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        </select>

                        <?php if (empty($availableUnits)): ?>

                            <p class="mt-2 text-xs text-red-600">
                                There are currently no vacant units available.
                            </p>

                        <?php endif; ?>

                    </div>


                    <!-- Lease ID -->
                    <div>

                        <label
                            for="leaseId"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Lease Reference
                        </label>

                        <input
                            type="text"
                            id="leaseId"
                            name="leaseId"
                            required
                            placeholder="e.g. LSE-<?= date('Ymd') ?>-001"
                            value="<?= e($_POST['leaseId'] ?? '') ?>"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >

                    </div>


                    <!-- Start Date -->
                    <div>

                        <label
                            for="startDate"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Start Date
                        </label>

                        <input
                            type="date"
                            id="startDate"
                            name="startDate"
                            required
                            value="<?= e($_POST['startDate'] ?? date('Y-m-d')) ?>"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >

                    </div>


                    <!-- End Date -->
                    <div>

                        <label
                            for="endDate"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            End Date
                        </label>

                        <input
                            type="date"
                            id="endDate"
                            name="endDate"
                            required
                            value="<?= e($_POST['endDate'] ?? '') ?>"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >

                    </div>


                    <!-- Deposit -->
                    <div>

                        <label
                            for="deposit"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Security Deposit
                        </label>

                        <div class="relative">

                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-500">
                                KSh
                            </span>

                            <input
                                type="number"
                                id="deposit"
                                name="deposit"
                                min="0"
                                step="0.01"
                                value="<?= e($_POST['deposit'] ?? '') ?>"
                                placeholder="0.00"
                                class="w-full rounded-lg border border-slate-300 py-2.5 pl-14 pr-3 text-sm text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                            >

                        </div>

                    </div>

                </div>


                <!-- Selected unit information -->
                <div
                    id="unitInformation"
                    class="mt-5 hidden rounded-lg bg-indigo-50 p-4"
                >

                    <p class="text-sm font-semibold text-indigo-900">
                        Selected Unit
                    </p>

                    <p
                        id="selectedUnitText"
                        class="mt-1 text-sm text-indigo-700"
                    ></p>

                </div>


                <!-- Submit -->
                <div class="mt-6 flex flex-wrap items-center gap-3">

                    <button
                        type="submit"
                        class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Assign Lease
                    </button>

                    <a
                        href="leases.php"
                        class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </section>


        <!-- Registered Customers -->
        <section class="mb-8">

            <div class="mb-4">

                <h3 class="text-lg font-semibold text-slate-900">
                    Registered Customers
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Customers who have registered in PropertyPro.
                </p>

            </div>


            <?php if (empty($customers)): ?>

                <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center">

                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-xl">
                        👤
                    </div>

                    <h4 class="mt-4 font-semibold text-slate-900">
                        No customers found
                    </h4>

                    <p class="mt-1 text-sm text-slate-500">
                        New customer registrations will appear here.
                    </p>

                </div>

            <?php else: ?>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">

                    <?php foreach ($customers as $customer): ?>

                        <?php

                        $customerName =
                            trim(
                                $customer['name']
                                ?? ''
                            );

                        if ($customerName === '') {

                            $customerName =
                                trim(
                                    ($customer['firstName'] ?? '')
                                    . ' '
                                    . ($customer['lastName'] ?? '')
                                );
                        }

                        if ($customerName === '') {
                            $customerName = 'Unnamed Customer';
                        }

                        $customerEmail =
                            $customer['email']
                            ?? '';

                        $customerPhone =
                            $customer['phone']
                            ?? '';

                        $customerId =
                            $customer['userId']
                            ?? $customer['_id']
                            ?? $customer['id']
                            ?? '';

                        ?>

                        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                            <div class="flex items-start justify-between gap-3">

                                <div class="flex min-w-0 items-center gap-3">

                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-100 font-bold text-indigo-700">
                                        <?= e(strtoupper(substr($customerName, 0, 1))) ?>
                                    </div>

                                    <div class="min-w-0">

                                        <h4 class="truncate font-semibold text-slate-900">
                                            <?= e($customerName) ?>
                                        </h4>

                                        <p class="truncate text-xs text-slate-500">
                                            Customer
                                        </p>

                                    </div>

                                </div>

                                <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                    <?= e($customer['status'] ?? 'Active') ?>
                                </span>

                            </div>


                            <div class="mt-4 space-y-2 text-sm">

                                <?php if ($customerEmail !== ''): ?>

                                    <p class="text-slate-600">
                                        ✉ <?= e($customerEmail) ?>
                                    </p>

                                <?php endif; ?>

                                <?php if ($customerPhone !== ''): ?>

                                    <p class="text-slate-600">
                                        ☎ <?= e($customerPhone) ?>
                                    </p>

                                <?php endif; ?>

                            </div>


                            <div class="mt-4 border-t border-slate-100 pt-4">

                                <button
                                    type="button"
                                    onclick="selectCustomer('<?= e($customerId) ?>')"
                                    class="w-full rounded-lg bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-100"
                                >
                                    Lease Property to Customer
                                </button>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>


        <!-- Existing Leases -->
        <section>

            <div class="mb-4">

                <h3 class="text-lg font-semibold text-slate-900">
                    Existing Lease Agreements
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Active and previous rental agreements.
                </p>

            </div>


            <?php if (empty($leases)): ?>

                <div class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center">

                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-2xl">
                        📄
                    </div>

                    <h3 class="mt-4 text-lg font-semibold text-slate-900">
                        No leases found
                    </h3>

                    <p class="mt-2 text-sm text-slate-500">
                        There are currently no lease agreements in the system.
                    </p>

                </div>

            <?php else: ?>

                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">

                    <?php foreach ($leases as $lease): ?>

                        <?php

                        $databaseId =
                            $lease['_id']
                            ?? '';

                        $leaseDisplayId =
                            $lease['leaseId']
                            ?? $lease['id']
                            ?? $databaseId
                            ?? 'N/A';

                        $tenantName =
                            $lease['tenant']
                            ?? (
                                is_array($lease['tenantId'] ?? null)
                                    ? (
                                        $lease['tenantId']['name']
                                        ?? 'Not available'
                                    )
                                    : 'Not available'
                            );

                        $propertyName =
                            $lease['property']
                            ?? (
                                is_array($lease['propertyId'] ?? null)
                                    ? (
                                        $lease['propertyId']['name']
                                        ?? 'Not available'
                                    )
                                    : 'Not available'
                            );

                        $unitName =
                            $lease['unit']
                            ?? (
                                is_array($lease['unitId'] ?? null)
                                    ? (
                                        $lease['unitId']['unitNumber']
                                        ?? 'Not available'
                                    )
                                    : 'Not available'
                            );

                        $rent =
                            (float) (
                                $lease['rent']
                                ?? $lease['monthlyRent']
                                ?? 0
                            );

                        $status =
                            $lease['status']
                            ?? 'Active';

                        $statusLower =
                            strtolower(
                                trim($status)
                            );

                        if ($statusLower === 'active') {

                            $statusClass =
                                'bg-emerald-50 text-emerald-700';

                        } elseif (
                            $statusLower === 'expired' ||
                            $statusLower === 'terminated'
                        ) {

                            $statusClass =
                                'bg-red-50 text-red-700';

                        } else {

                            $statusClass =
                                'bg-amber-50 text-amber-700';
                        }

                        ?>

                        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                            <div class="flex items-center justify-between gap-3">

                                <span class="break-all font-semibold text-slate-900">
                                    <?= e($leaseDisplayId) ?>
                                </span>

                                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium <?= $statusClass ?>">
                                    <?= e($status) ?>
                                </span>

                            </div>


                            <div class="mt-5 space-y-4 text-sm">

                                <div>

                                    <p class="text-xs text-slate-500">
                                        Tenant
                                    </p>

                                    <p class="mt-1 font-medium text-slate-900">
                                        <?= e($tenantName) ?>
                                    </p>

                                </div>


                                <div>

                                    <p class="text-xs text-slate-500">
                                        Property
                                    </p>

                                    <p class="mt-1 font-medium text-slate-900">
                                        <?= e($propertyName) ?>
                                    </p>

                                </div>


                                <div>

                                    <p class="text-xs text-slate-500">
                                        Unit
                                    </p>

                                    <p class="mt-1 font-medium text-slate-900">
                                        <?= e($unitName) ?>
                                    </p>

                                </div>


                                <div>

                                    <p class="text-xs text-slate-500">
                                        Monthly Rent
                                    </p>

                                    <p class="mt-1 font-semibold text-slate-900">
                                        KSh <?= number_format($rent, 2) ?>
                                    </p>

                                </div>

                            </div>


                            <div class="mt-5 border-t border-slate-100 pt-4">

                                <?php if ($databaseId !== ''): ?>

                                    <a
                                        href="lease-details.php?id=<?= urlencode($databaseId) ?>"
                                        class="inline-flex items-center text-sm font-semibold text-indigo-600 hover:text-indigo-700"
                                    >
                                        View Lease →
                                    </a>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </div>

</main>


<script>

/*
|--------------------------------------------------------------------------
| Select customer from customer card
|--------------------------------------------------------------------------
*/

function selectCustomer(customerId) {

    const customerSelect =
        document.getElementById('userId');

    const leaseSection =
        document.getElementById('new-lease');

    if (!customerSelect) {
        return;
    }

    customerSelect.value =
        customerId;

    if (leaseSection) {

        leaseSection.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }

    customerSelect.focus();
}


/*
|--------------------------------------------------------------------------
| Property / Unit filtering
|--------------------------------------------------------------------------
*/

const propertySelect =
    document.getElementById('propertyId');

const unitSelect =
    document.getElementById('unitId');

const unitInformation =
    document.getElementById('unitInformation');

const selectedUnitText =
    document.getElementById('selectedUnitText');


function filterUnits() {

    if (!propertySelect || !unitSelect) {
        return;
    }

    const selectedProperty =
        propertySelect.value;

    const options =
        unitSelect.querySelectorAll(
            'option[data-property]'
        );

    options.forEach(function(option) {

        const unitProperty =
            option.dataset.property || '';

        if (
            selectedProperty === '' ||
            unitProperty === selectedProperty
        ) {

            option.hidden = false;

        } else {

            option.hidden = true;

            if (
                unitSelect.value === option.value
            ) {
                unitSelect.value = '';
            }
        }

    });

    updateUnitInformation();
}


function updateUnitInformation() {

    if (!unitSelect) {
        return;
    }

    const option =
        unitSelect.options[
            unitSelect.selectedIndex
        ];

    if (
        !option ||
        !option.value
    ) {

        if (unitInformation) {
            unitInformation.classList.add('hidden');
        }

        return;
    }

    const unitName =
        option.textContent.trim();

    const rent =
        parseFloat(
            option.dataset.rent || '0'
        );

    if (selectedUnitText) {

        selectedUnitText.textContent =
            rent > 0
                ? unitName + ' — Monthly rent: KSh ' +
                  rent.toLocaleString(
                      'en-KE',
                      {
                          minimumFractionDigits: 2,
                          maximumFractionDigits: 2
                      }
                  )
                : unitName;
    }

    if (unitInformation) {
        unitInformation.classList.remove('hidden');
    }
}


if (propertySelect) {

    propertySelect.addEventListener(
        'change',
        filterUnits
    );
}


if (unitSelect) {

    unitSelect.addEventListener(
        'change',
        updateUnitInformation
    );
}


/*
|--------------------------------------------------------------------------
| Initial filtering
|--------------------------------------------------------------------------
*/

filterUnits();

</script>

<?php require_once "../../includes/footer.php"; ?>