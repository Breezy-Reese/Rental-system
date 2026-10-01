<?php

require_once __DIR__ . '/../../includes/api.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/data.php';

require_admin();

$pageTitle = 'Units';

$successMessage = '';
$errorMessage = '';

/*
|--------------------------------------------------------------------------
| Helper functions
|--------------------------------------------------------------------------
*/

function pp_units_value(array $item, array $keys, $default = '')
{
    foreach ($keys as $key) {
        if (
            array_key_exists($key, $item) &&
            $item[$key] !== null
        ) {
            return $item[$key];
        }
    }

    return $default;
}

function pp_units_id($value): string
{
    if (is_array($value)) {
        return (string) (
            $value['_id']
            ?? $value['id']
            ?? $value['unitId']
            ?? $value['propertyId']
            ?? $value['tenantId']
            ?? ''
        );
    }

    return (string) ($value ?? '');
}

function pp_units_get_id(array $item, array $keys): string
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $item)) {

            $id = pp_units_id(
                $item[$key]
            );

            if ($id !== '') {
                return $id;
            }
        }
    }

    return '';
}

function pp_units_find_property(array $unit): array
{
    global $properties;

    $propertyId =
        pp_units_get_id(
            $unit,
            [
                'propertyId',
                'property',
                'property_id'
            ]
        );

    if ($propertyId === '') {
        return [];
    }

    foreach ($properties as $property) {

        if (!is_array($property)) {
            continue;
        }

        $candidateId =
            pp_units_get_id(
                $property,
                [
                    '_id',
                    'id',
                    'propertyId'
                ]
            );

        if (
            $candidateId !== '' &&
            $candidateId === $propertyId
        ) {
            return $property;
        }
    }

    return [];
}

function pp_units_find_tenant(array $unit): array
{
    global $tenants;

    $tenantId =
        pp_units_get_id(
            $unit,
            [
                'tenantId',
                'tenant_id'
            ]
        );

    if ($tenantId === '') {
        return [];
    }

    foreach ($tenants as $tenant) {

        if (!is_array($tenant)) {
            continue;
        }

        $candidateId =
            pp_units_get_id(
                $tenant,
                [
                    '_id',
                    'id',
                    'tenantId'
                ]
            );

        if (
            $candidateId !== '' &&
            $candidateId === $tenantId
        ) {
            return $tenant;
        }
    }

    return [];
}

function pp_units_tenant_name(array $unit): string
{
    if (
        isset($unit['tenantId']) &&
        is_array($unit['tenantId'])
    ) {

        $name =
            pp_units_value(
                $unit['tenantId'],
                [
                    'name',
                    'fullName',
                    'tenantName'
                ],
                ''
            );

        if ($name !== '') {
            return $name;
        }
    }

    if (
        isset($unit['tenant']) &&
        is_array($unit['tenant'])
    ) {

        $name =
            pp_units_value(
                $unit['tenant'],
                [
                    'name',
                    'fullName',
                    'tenantName'
                ],
                ''
            );

        if ($name !== '') {
            return $name;
        }
    }

    if (
        isset($unit['tenant']) &&
        is_string(
            $unit['tenant']
        )
    ) {
        return $unit['tenant'];
    }

    $tenant =
        pp_units_find_tenant(
            $unit
        );

    if (!empty($tenant)) {

        $name =
            pp_units_value(
                $tenant,
                [
                    'name',
                    'fullName',
                    'tenantName'
                ],
                ''
            );

        if ($name !== '') {
            return $name;
        }
    }

    return 'Vacant';
}

function pp_units_is_occupied(array $unit): bool
{
    $status = strtolower(
        trim(
            (string) (
                $unit['status']
                ?? $unit['unitStatus']
                ?? ''
            )
        )
    );

    if (
        in_array(
            $status,
            [
                'occupied',
                'rented',
                'leased'
            ],
            true
        )
    ) {
        return true;
    }

    if (
        in_array(
            $status,
            [
                'vacant',
                'available',
                'empty'
            ],
            true
        )
    ) {
        return false;
    }

    if (!empty($unit['tenantId'])) {
        return true;
    }

    if (!empty($unit['tenant'])) {
        return true;
    }

    return !empty(
        pp_units_find_tenant(
            $unit
        )
    );
}

/*
|--------------------------------------------------------------------------
| Load customer list for assignment form
|--------------------------------------------------------------------------
*/

$customers = [];

$customerResponse =
    api_get('/admin/customers');

if (
    is_array($customerResponse) &&
    !empty($customerResponse['success'])
) {
    $customers =
        $customerResponse['data'] ?? [];
}

/*
|--------------------------------------------------------------------------
| Handle Add Unit / Assign Customer
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    $action =
        trim(
            $_POST['action'] ?? ''
        );

    // ========================================================
    // ADD UNIT
    // ========================================================

    if (
        $action === 'create_unit'
    ) {

        $unitNumber =
            trim(
                $_POST['unitNumber'] ?? ''
            );

        $propertyId =
            trim(
                $_POST['propertyId'] ?? ''
            );

        $type =
            trim(
                $_POST['type'] ?? ''
            );

        $rent =
            trim(
                $_POST['rent'] ?? ''
            );

        if (
            $unitNumber === ''
        ) {
            $errorMessage =
                'Please enter a unit number.';

        } elseif (
            $propertyId === ''
        ) {
            $errorMessage =
                'Please select a property.';

        } elseif (
            $rent === '' ||
            !is_numeric($rent) ||
            (float) $rent < 0
        ) {
            $errorMessage =
                'Please enter a valid monthly rent.';

        } else {

            /*
             * The Unit model requires unitId.
             *
             * Generate it automatically so the administrator
             * only needs to enter business information.
             */
            $unitId =
                'UNIT-' .
                date('YmdHis') .
                '-' .
                random_int(100, 999);

            $payload = [
                'unitId' =>
                    $unitId,

                'propertyId' =>
                    $propertyId,

                'unitNumber' =>
                    $unitNumber,

                'type' =>
                    $type,

                'rent' =>
                    (float) $rent,

                'status' =>
                    'Vacant',

                'tenantId' =>
                    null,
            ];

            $result =
                api_post(
                    '/units',
                    $payload
                );

            if (
                is_array($result) &&
                !empty($result['success'])
            ) {

                header(
                    'Location: units.php?created=1'
                );
                exit;

            } else {

                $errorMessage =
                    $result['message']
                    ??
                    'Unable to create the unit.';
            }
        }
    }

    // ========================================================
    // ASSIGN CUSTOMER
    // ========================================================

    elseif (
        $action === 'assign_customer'
    ) {

        $userId =
            trim(
                $_POST['userId'] ?? ''
            );

        $propertyId =
            trim(
                $_POST['assignmentPropertyId']
                ?? ''
            );

        $unitId =
            trim(
                $_POST['assignmentUnitId']
                ?? ''
            );

        $leaseId =
            trim(
                $_POST['leaseId'] ?? ''
            );

        $startDate =
            trim(
                $_POST['startDate'] ?? ''
            );

        $endDate =
            trim(
                $_POST['endDate'] ?? ''
            );

        $deposit =
            trim(
                $_POST['deposit'] ?? '0'
            );

        if (
            $userId === ''
        ) {
            $errorMessage =
                'Please select a customer.';

        } elseif (
            $propertyId === ''
        ) {
            $errorMessage =
                'Please select a property.';

        } elseif (
            $unitId === ''
        ) {
            $errorMessage =
                'Please select a vacant unit.';

        } elseif (
            $startDate === ''
        ) {
            $errorMessage =
                'Please enter the lease start date.';

        } elseif (
            $endDate === ''
        ) {
            $errorMessage =
                'Please enter the lease end date.';

        } elseif (
            $endDate <= $startDate
        ) {
            $errorMessage =
                'Lease end date must be after the start date.';

        } elseif (
            $deposit !== '' &&
            (
                !is_numeric($deposit) ||
                (float) $deposit < 0
            )
        ) {
            $errorMessage =
                'Please enter a valid deposit amount.';

        } else {

            $payload = [
                'userId' =>
                    $userId,

                'propertyId' =>
                    $propertyId,

                'unitId' =>
                    $unitId,

                'leaseId' =>
                    ($leaseId !== ''
                        ? $leaseId
                        : null),

                'startDate' =>
                    $startDate,

                'endDate' =>
                    $endDate,

                'deposit' =>
                    (float) (
                        $deposit === ''
                            ? 0
                            : $deposit
                    ),
            ];

            $result =
                api_post(
                    '/leases/assign-customer',
                    $payload
                );

            if (
                is_array($result) &&
                !empty($result['success'])
            ) {

                header(
                    'Location: units.php?assigned=1'
                );
                exit;

            } else {

                $errorMessage =
                    $result['message']
                    ??
                    'Unable to assign the customer to the unit.';
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Flash messages
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['created']) &&
    $_GET['created'] === '1'
) {
    $successMessage =
        'Unit added successfully.';
}

if (
    isset($_GET['assigned']) &&
    $_GET['assigned'] === '1'
) {
    $successMessage =
        'Customer assigned to rental unit successfully.';
}

/*
|--------------------------------------------------------------------------
| Unit statistics
|--------------------------------------------------------------------------
*/

$totalUnits =
    count($units);

$occupiedCount = 0;
$vacantCount = 0;
$maintenanceCount = 0;

foreach ($units as $unit) {

    if (!is_array($unit)) {
        continue;
    }

    $status = strtolower(
        trim(
            (string) (
                $unit['status']
                ?? ''
            )
        )
    );

    if (
        $status === 'maintenance'
    ) {
        $maintenanceCount++;

    } elseif (
        pp_units_is_occupied(
            $unit
        )
    ) {
        $occupiedCount++;

    } else {
        $vacantCount++;
    }
}

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../includes/header.php';

require_once __DIR__ . '/../../includes/navbar.php';

require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="lg:ml-64 min-h-screen bg-slate-50">

    <div class="p-4 sm:p-6 lg:p-8">

        <!-- =====================================================
             PAGE HEADER
        ====================================================== -->

        <div class="flex flex-col gap-4 mb-8 lg:flex-row lg:items-center lg:justify-between">

            <div>

                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">
                    Units
                </h1>

                <p class="mt-1 text-sm text-slate-600">
                    Monitor unit occupancy and tenant assignments.
                </p>

            </div>

            <div class="flex flex-col sm:flex-row gap-3">

                <button
                    type="button"
                    onclick="openAddUnitModal()"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700"
                >
                    <span class="text-lg leading-none">+</span>
                    Add Unit
                </button>

                <button
                    type="button"
                    onclick="openAssignModal()"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100"
                >
                    Assign Customer
                </button>

            </div>

        </div>


        <!-- =====================================================
             FLASH MESSAGES
        ====================================================== -->

        <?php if ($successMessage): ?>

            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                <?= e($successMessage) ?>
            </div>

        <?php endif; ?>


        <?php if ($errorMessage): ?>

            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <?= e($errorMessage) ?>
            </div>

        <?php endif; ?>


        <!-- =====================================================
             SUMMARY
        ====================================================== -->

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm font-medium text-slate-500">
                    Total Units
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-900">
                    <?= $totalUnits ?>
                </p>

            </div>


            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm font-medium text-slate-500">
                    Occupied
                </p>

                <p class="mt-2 text-3xl font-bold text-indigo-600">
                    <?= $occupiedCount ?>
                </p>

            </div>


            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm font-medium text-slate-500">
                    Vacant
                </p>

                <p class="mt-2 text-3xl font-bold text-emerald-600">
                    <?= $vacantCount ?>
                </p>

            </div>


            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm font-medium text-slate-500">
                    Maintenance
                </p>

                <p class="mt-2 text-3xl font-bold text-amber-600">
                    <?= $maintenanceCount ?>
                </p>

            </div>

        </div>


        <!-- =====================================================
             UNITS TABLE
        ====================================================== -->

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-5 sm:px-6">

                <h2 class="text-lg font-semibold text-slate-900">
                    All Units
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    View and manage all units across your properties.
                </p>

            </div>


            <?php if (empty($units)): ?>

                <div class="px-6 py-12 text-center">

                    <div class="mb-3 text-4xl text-slate-400">
                        🚪
                    </div>

                    <h3 class="text-lg font-semibold text-slate-900">
                        No units found
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Add your first rental unit to get started.
                    </p>

                </div>

            <?php else: ?>

                <div class="overflow-x-auto">

                    <table class="w-full text-left">

                        <thead class="border-b border-slate-200 bg-slate-50">

                            <tr>

                                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Unit
                                </th>

                                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Property
                                </th>

                                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Tenant
                                </th>

                                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Rent
                                </th>

                                <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100">

                            <?php foreach ($units as $unit): ?>

                                <?php
                                if (!is_array($unit)) {
                                    continue;
                                }

                                $unitNumber =
                                    pp_units_value(
                                        $unit,
                                        [
                                            'unitNumber',
                                            'unit_name',
                                            'number',
                                            'code',
                                            'name',
                                            'unit'
                                        ],
                                        ''
                                    );

                                if (
                                    is_array(
                                        $unitNumber
                                    )
                                ) {
                                    $unitNumber =
                                        pp_units_id(
                                            $unitNumber
                                        );
                                }

                                if (
                                    (string) $unitNumber === ''
                                ) {
                                    $unitNumber =
                                        'Unit';
                                }

                                $property =
                                    pp_units_find_property(
                                        $unit
                                    );

                                $propertyName = '';

                                if (
                                    !empty($unit['propertyId']) &&
                                    is_array($unit['propertyId'])
                                ) {
                                    $propertyName =
                                        pp_units_value(
                                            $unit['propertyId'],
                                            [
                                                'name',
                                                'propertyName'
                                            ],
                                            ''
                                        );
                                }

                                if (
                                    $propertyName === '' &&
                                    !empty($property)
                                ) {
                                    $propertyName =
                                        pp_units_value(
                                            $property,
                                            [
                                                'name',
                                                'propertyName',
                                                'title'
                                            ],
                                            ''
                                        );
                                }

                                if (
                                    $propertyName === '' &&
                                    isset($unit['property']) &&
                                    is_string($unit['property'])
                                ) {
                                    $propertyName =
                                        $unit['property'];
                                }

                                if (
                                    $propertyName === ''
                                ) {
                                    $propertyName =
                                        'Unknown Property';
                                }

                                $tenantName =
                                    pp_units_tenant_name(
                                        $unit
                                    );

                                $rent =
                                    pp_units_value(
                                        $unit,
                                        [
                                            'rent',
                                            'monthlyRent',
                                            'rentAmount',
                                            'amount'
                                        ],
                                        0
                                    );

                                if (
                                    is_array($rent)
                                ) {
                                    $rent = 0;
                                }

                                $rent =
                                    (float) $rent;

                                $rawStatus =
                                    strtolower(
                                        trim(
                                            (string) (
                                                $unit['status']
                                                ?? ''
                                            )
                                        )
                                    );

                                if (
                                    $rawStatus === 'maintenance'
                                ) {

                                    $status =
                                        'Maintenance';

                                    $statusClass =
                                        'bg-amber-100 text-amber-700';

                                } elseif (
                                    pp_units_is_occupied($unit)
                                ) {

                                    $status =
                                        'Occupied';

                                    $statusClass =
                                        'bg-indigo-100 text-indigo-700';

                                } else {

                                    $status =
                                        'Vacant';

                                    $statusClass =
                                        'bg-emerald-100 text-emerald-700';
                                }
                                ?>

                                <tr class="transition hover:bg-slate-50">

                                    <td class="px-6 py-4">

                                        <div class="font-semibold text-slate-900">
                                            <?= e($unitNumber) ?>
                                        </div>

                                    </td>


                                    <td class="px-6 py-4">

                                        <div class="font-medium text-slate-700">
                                            <?= e($propertyName) ?>
                                        </div>

                                    </td>


                                    <td class="px-6 py-4">

                                        <div
                                            class="<?= $tenantName === 'Vacant'
                                                ? 'text-slate-400'
                                                : 'font-medium text-slate-700' ?>"
                                        >
                                            <?= e($tenantName) ?>
                                        </div>

                                    </td>


                                    <td class="px-6 py-4">

                                        <div class="font-semibold text-slate-900">
                                            KES <?= number_format($rent, 2) ?>
                                        </div>

                                    </td>


                                    <td class="px-6 py-4">

                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold <?= e($statusClass) ?>">
                                            <?= e($status) ?>
                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>

</main>


<!-- ============================================================
     ADD UNIT MODAL
============================================================ -->

<div
    id="addUnitModal"
    class="hidden fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="addUnitTitle"
>

    <div
        class="fixed inset-0 bg-slate-900/50"
        onclick="closeAddUnitModal()"
    ></div>

    <div class="relative flex min-h-screen items-center justify-center p-4">

        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl">

            <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">

                <div>

                    <h2
                        id="addUnitTitle"
                        class="text-xl font-bold text-slate-900"
                    >
                        Add Unit
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Add a new vacant rental unit.
                    </p>

                </div>

                <button
                    type="button"
                    onclick="closeAddUnitModal()"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                >
                    ✕
                </button>

            </div>


            <form
                method="POST"
                action="units.php"
                class="space-y-5 px-6 py-6"
            >

                <input
                    type="hidden"
                    name="action"
                    value="create_unit"
                >


                <div>

                    <label
                        for="unitNumber"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Unit Number
                    </label>

                    <input
                        type="text"
                        id="unitNumber"
                        name="unitNumber"
                        required
                        placeholder="e.g. A-103"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                </div>


                <div>

                    <label
                        for="propertyId"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Property
                    </label>

                    <select
                        id="propertyId"
                        name="propertyId"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                        <option value="">
                            Select property
                        </option>

                        <?php foreach ($properties as $property): ?>

                            <?php
                            if (!is_array($property)) {
                                continue;
                            }

                            $propertyId =
                                pp_units_get_id(
                                    $property,
                                    [
                                        '_id',
                                        'id',
                                        'propertyId'
                                    ]
                                );

                            $propertyName =
                                pp_units_value(
                                    $property,
                                    [
                                        'name',
                                        'propertyName',
                                        'title'
                                    ],
                                    'Unnamed Property'
                                );
                            ?>

                            <?php if ($propertyId !== ''): ?>

                                <option
                                    value="<?= e($propertyId) ?>"
                                >
                                    <?= e($propertyName) ?>
                                </option>

                            <?php endif; ?>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div>

                    <label
                        for="type"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Unit Type
                    </label>

                    <input
                        type="text"
                        id="type"
                        name="type"
                        placeholder="e.g. Bedsitter, 1 Bedroom, 2 Bedroom"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                </div>


                <div>

                    <label
                        for="rent"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Monthly Rent (KES)
                    </label>

                    <input
                        type="number"
                        id="rent"
                        name="rent"
                        required
                        min="0"
                        step="0.01"
                        placeholder="25000"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                </div>


                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">

                    New units are created as
                    <strong>Vacant</strong>.
                    Assign a customer separately after the unit has been created.

                </div>


                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

                    <button
                        type="button"
                        onclick="closeAddUnitModal()"
                        class="w-full rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:w-auto"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="w-full rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 sm:w-auto"
                    >
                        Add Unit
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- ============================================================
     ASSIGN CUSTOMER MODAL
============================================================ -->

<div
    id="assignCustomerModal"
    class="hidden fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="assignCustomerTitle"
>

    <div
        class="fixed inset-0 bg-slate-900/50"
        onclick="closeAssignModal()"
    ></div>

    <div class="relative flex min-h-screen items-center justify-center p-4">

        <div class="relative w-full max-w-xl rounded-2xl bg-white shadow-2xl">

            <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">

                <div>

                    <h2
                        id="assignCustomerTitle"
                        class="text-xl font-bold text-slate-900"
                    >
                        Assign Customer
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Assign a new customer to a vacant rental unit.
                    </p>

                </div>

                <button
                    type="button"
                    onclick="closeAssignModal()"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                >
                    ✕
                </button>

            </div>


            <form
                method="POST"
                action="units.php"
                class="space-y-5 px-6 py-6"
            >

                <input
                    type="hidden"
                    name="action"
                    value="assign_customer"
                >


                <!-- Customer -->
                <div>

                    <label
                        for="userId"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Customer
                    </label>

                    <select
                        id="userId"
                        name="userId"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                        <option value="">
                            Select customer
                        </option>

                        <?php foreach ($customers as $customer): ?>

                            <?php
                            if (!is_array($customer)) {
                                continue;
                            }

                            $customerId =
                                pp_units_id(
                                    $customer['id']
                                    ?? $customer['_id']
                                    ?? ''
                                );

                            $customerName =
                                $customer['name']
                                ?? 'Unnamed Customer';

                            $customerEmail =
                                $customer['email']
                                ?? '';

                            $customerStatus =
                                $customer['status']
                                ?? 'Active';

                            $hasTenant =
                                !empty(
                                    $customer['hasTenant']
                                );

                            $hasActiveLease =
                                !empty(
                                    $customer['hasActiveLease']
                                );
                            ?>

                            <?php
                            if (
                                $customerId !== '' &&
                                $customerStatus === 'Active' &&
                                !$hasTenant &&
                                !$hasActiveLease
                            ):
                            ?>

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

                    <p class="mt-1.5 text-xs text-slate-500">
                        Only active customers without an existing rental assignment are shown.
                    </p>

                </div>


                <!-- Property -->
                <div>

                    <label
                        for="assignmentPropertyId"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Property
                    </label>

                    <select
                        id="assignmentPropertyId"
                        name="assignmentPropertyId"
                        required
                        onchange="filterAssignmentUnits()"
                        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                        <option value="">
                            Select property
                        </option>

                        <?php foreach ($properties as $property): ?>

                            <?php
                            if (!is_array($property)) {
                                continue;
                            }

                            $propertyId =
                                pp_units_get_id(
                                    $property,
                                    [
                                        '_id',
                                        'id',
                                        'propertyId'
                                    ]
                                );

                            $propertyName =
                                pp_units_value(
                                    $property,
                                    [
                                        'name',
                                        'propertyName',
                                        'title'
                                    ],
                                    'Unnamed Property'
                                );

                            $propertyStatus =
                                pp_units_value(
                                    $property,
                                    ['status'],
                                    'Active'
                                );
                            ?>

                            <?php if (
                                $propertyId !== '' &&
                                $propertyStatus === 'Active'
                            ): ?>

                                <option
                                    value="<?= e($propertyId) ?>"
                                >
                                    <?= e($propertyName) ?>
                                </option>

                            <?php endif; ?>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Vacant Unit -->
                <div>

                    <label
                        for="assignmentUnitId"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Vacant Unit
                    </label>

                    <select
                        id="assignmentUnitId"
                        name="assignmentUnitId"
                        required
                        disabled
                        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none disabled:bg-slate-100 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                        <option value="">
                            Select a property first
                        </option>

                        <?php foreach ($units as $unit): ?>

                            <?php
                            if (!is_array($unit)) {
                                continue;
                            }

                            $unitId =
                                pp_units_get_id(
                                    $unit,
                                    [
                                        '_id',
                                        'id',
                                        'unitId'
                                    ]
                                );

                            $propertyId =
                                pp_units_get_id(
                                    $unit,
                                    [
                                        'propertyId',
                                        'property'
                                    ]
                                );

                            $unitNumber =
                                pp_units_value(
                                    $unit,
                                    [
                                        'unitNumber',
                                        'number',
                                        'code',
                                        'name'
                                    ],
                                    'Unit'
                                );

                            $unitStatus =
                                strtolower(
                                    trim(
                                        (string) (
                                            $unit['status']
                                            ?? ''
                                        )
                                    )
                                );

                            $unitRent =
                                (float) (
                                    $unit['rent']
                                    ?? 0
                                );
                            ?>

                            <?php if (
                                $unitId !== '' &&
                                $propertyId !== '' &&
                                $unitStatus === 'vacant'
                            ): ?>

                                <option
                                    value="<?= e($unitId) ?>"
                                    data-property="<?= e($propertyId) ?>"
                                >
                                    <?= e($unitNumber) ?>
                                    — KES <?= number_format($unitRent, 2) ?>
                                </option>

                            <?php endif; ?>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Lease ID -->
                <div>

                    <label
                        for="leaseId"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Lease ID
                        <span class="font-normal text-slate-400">
                            (Optional)
                        </span>
                    </label>

                    <input
                        type="text"
                        id="leaseId"
                        name="leaseId"
                        placeholder="Leave blank for automatic ID"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                </div>


                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                    <!-- Start Date -->
                    <div>

                        <label
                            for="startDate"
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Lease Start
                        </label>

                        <input
                            type="date"
                            id="startDate"
                            name="startDate"
                            required
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >

                    </div>


                    <!-- End Date -->
                    <div>

                        <label
                            for="endDate"
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Lease End
                        </label>

                        <input
                            type="date"
                            id="endDate"
                            name="endDate"
                            required
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >

                    </div>

                </div>


                <!-- Deposit -->
                <div>

                    <label
                        for="deposit"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Security Deposit (KES)
                    </label>

                    <input
                        type="number"
                        id="deposit"
                        name="deposit"
                        min="0"
                        step="0.01"
                        value="0"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                </div>


                <div class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-700">

                    The unit's existing monthly rent will automatically become the customer's lease rent.

                </div>


                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

                    <button
                        type="button"
                        onclick="closeAssignModal()"
                        class="w-full rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:w-auto"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="w-full rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 sm:w-auto"
                    >
                        Assign Customer
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<script>

/*
|--------------------------------------------------------------------------
| ADD UNIT MODAL
|--------------------------------------------------------------------------
*/

function openAddUnitModal() {

    const modal =
        document.getElementById(
            'addUnitModal'
        );

    if (!modal) {
        return;
    }

    closeAssignModal();

    modal.classList.remove(
        'hidden'
    );

    document.body.classList.add(
        'overflow-hidden'
    );

    const input =
        document.getElementById(
            'unitNumber'
        );

    if (input) {

        setTimeout(
            function () {
                input.focus();
            },
            100
        );
    }
}

function closeAddUnitModal() {

    const modal =
        document.getElementById(
            'addUnitModal'
        );

    if (!modal) {
        return;
    }

    modal.classList.add(
        'hidden'
    );

    document.body.classList.remove(
        'overflow-hidden'
    );
}


/*
|--------------------------------------------------------------------------
| ASSIGN CUSTOMER MODAL
|--------------------------------------------------------------------------
*/

function openAssignModal() {

    const modal =
        document.getElementById(
            'assignCustomerModal'
        );

    if (!modal) {
        return;
    }

    closeAddUnitModal();

    modal.classList.remove(
        'hidden'
    );

    document.body.classList.add(
        'overflow-hidden'
    );
}

function closeAssignModal() {

    const modal =
        document.getElementById(
            'assignCustomerModal'
        );

    if (!modal) {
        return;
    }

    modal.classList.add(
        'hidden'
    );

    document.body.classList.remove(
        'overflow-hidden'
    );
}


/*
|--------------------------------------------------------------------------
| FILTER VACANT UNITS BY PROPERTY
|--------------------------------------------------------------------------
*/

function filterAssignmentUnits() {

    const propertySelect =
        document.getElementById(
            'assignmentPropertyId'
        );

    const unitSelect =
        document.getElementById(
            'assignmentUnitId'
        );

    if (
        !propertySelect ||
        !unitSelect
    ) {
        return;
    }

    const propertyId =
        propertySelect.value;

    unitSelect.value = '';

    const options =
        Array.from(
            unitSelect.options
        );

    let visibleUnits = 0;

    options.forEach(
        function (option, index) {

            if (index === 0) {
                option.hidden = false;
                return;
            }

            const optionProperty =
                option.dataset.property;

            const matches =
                propertyId !== '' &&
                optionProperty ===
                    propertyId;

            option.hidden =
                !matches;

            if (matches) {
                visibleUnits++;
            }
        }
    );

    unitSelect.disabled =
        propertyId === '' ||
        visibleUnits === 0;

    if (propertyId === '') {

        unitSelect.options[0].text =
            'Select a property first';

    } else if (
        visibleUnits === 0
    ) {

        unitSelect.options[0].text =
            'No vacant units available';

    } else {

        unitSelect.options[0].text =
            'Select vacant unit';
    }
}


/*
|--------------------------------------------------------------------------
| ESCAPE KEY
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'keydown',
    function (event) {

        if (
            event.key === 'Escape'
        ) {
            closeAddUnitModal();
            closeAssignModal();
        }

    }
);


/*
|--------------------------------------------------------------------------
| OPEN ASSIGN MODAL AFTER SERVER ERROR
|--------------------------------------------------------------------------
*/

<?php if (
    $errorMessage &&
    ($_POST['action'] ?? '') ===
        'assign_customer'
): ?>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        openAssignModal();

        filterAssignmentUnits();
    }
);

<?php endif; ?>


/*
|--------------------------------------------------------------------------
| OPEN ADD UNIT MODAL AFTER SERVER ERROR
|--------------------------------------------------------------------------
*/

<?php if (
    $errorMessage &&
    ($_POST['action'] ?? '') ===
        'create_unit'
): ?>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        openAddUnitModal();
    }
);

<?php endif; ?>

</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>