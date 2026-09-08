<?php

/**
 * ============================================================
 * PROPERTYPRO DEMO DATA
 * ============================================================
 *
 * This file currently contains temporary static data.
 *
 * Later, when we connect MongoDB, these arrays will be replaced
 * by database queries.
 */


/* ============================================================
   HELPER FUNCTIONS
   ============================================================ */

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

if (!function_exists('money')) {
    function money($amount): string
    {
        return 'KSh ' . number_format(
            (float) $amount,
            0
        );
    }
}


/* ============================================================
   PROPERTIES
   ============================================================ */

$properties = [

    'PROP-001' => [
        'id' => 'PROP-001',
        'name' => 'Greenview Apartments',
        'location' => 'Nairobi',
        'area' => 'Kilimani',
        'type' => 'Apartment',
        'units' => 48,
        'occupied' => 43,
        'vacant' => 5,
        'maintenance' => 2,
        'monthly_income' => 1075000,
    ],

    'PROP-002' => [
        'id' => 'PROP-002',
        'name' => 'Sunrise Estate',
        'location' => 'Mombasa',
        'area' => 'Nyali',
        'type' => 'Residential Estate',
        'units' => 72,
        'occupied' => 68,
        'vacant' => 4,
        'maintenance' => 3,
        'monthly_income' => 2380000,
    ],

    'PROP-003' => [
        'id' => 'PROP-003',
        'name' => 'Palm Heights',
        'location' => 'Kilifi',
        'area' => 'Kilifi Town',
        'type' => 'Apartment',
        'units' => 36,
        'occupied' => 29,
        'vacant' => 7,
        'maintenance' => 2,
        'monthly_income' => 725000,
    ],
];


/* ============================================================
   TENANTS
   ============================================================ */

$tenants = [

    'TEN-00124' => [
        'id' => 'TEN-00124',
        'name' => 'John Mwangi',
        'email' => 'john@example.com',
        'phone' => '+254 712 345 678',

        'property_id' => 'PROP-001',
        'property' => 'Greenview Apartments',

        'unit_id' => 'UNIT-00101',
        'unit' => 'A-101',
        'unit_type' => '2 Bedroom',

        'rent' => 25000,
        'status' => 'Active',

        'lease_id' => 'LS-00124',
        'lease_start' => 'Jan 1, 2026',
        'lease_end' => 'Dec 31, 2026',

        'balance' => 0,
        'total_paid' => 225000,
    ],

    'TEN-00125' => [
        'id' => 'TEN-00125',
        'name' => 'Mary Wanjiku',
        'email' => 'mary@example.com',
        'phone' => '+254 723 456 789',

        'property_id' => 'PROP-002',
        'property' => 'Sunrise Estate',

        'unit_id' => 'UNIT-00204',
        'unit' => 'B-204',
        'unit_type' => '3 Bedroom',

        'rent' => 35000,
        'status' => 'Active',

        'lease_id' => 'LS-00125',
        'lease_start' => 'Mar 1, 2026',
        'lease_end' => 'Feb 28, 2027',

        'balance' => 0,
        'total_paid' => 210000,
    ],

    'TEN-00126' => [
        'id' => 'TEN-00126',
        'name' => 'Peter Kamau',
        'email' => 'peter@example.com',
        'phone' => '+254 734 567 890',

        'property_id' => 'PROP-003',
        'property' => 'Palm Heights',

        'unit_id' => 'UNIT-00302',
        'unit' => 'C-302',
        'unit_type' => '2 Bedroom',

        'rent' => 30000,
        'status' => 'Active',

        'lease_id' => 'LS-00126',
        'lease_start' => 'Sep 1, 2026',
        'lease_end' => 'Aug 31, 2027',

        'balance' => 30000,
        'total_paid' => 0,
    ],
];


/* ============================================================
   UNITS
   ============================================================ */

$units = [

    'UNIT-00101' => [
        'id' => 'UNIT-00101',
        'number' => 'A-101',
        'property_id' => 'PROP-001',
        'property' => 'Greenview Apartments',
        'type' => '2 Bedroom',
        'rent' => 25000,
        'tenant_id' => 'TEN-00124',
        'tenant' => 'John Mwangi',
        'status' => 'Occupied',
    ],

    'UNIT-00102' => [
        'id' => 'UNIT-00102',
        'number' => 'A-102',
        'property_id' => 'PROP-001',
        'property' => 'Greenview Apartments',
        'type' => '1 Bedroom',
        'rent' => 18000,
        'tenant_id' => null,
        'tenant' => null,
        'status' => 'Vacant',
    ],

    'UNIT-00204' => [
        'id' => 'UNIT-00204',
        'number' => 'B-204',
        'property_id' => 'PROP-002',
        'property' => 'Sunrise Estate',
        'type' => '3 Bedroom',
        'rent' => 35000,
        'tenant_id' => 'TEN-00125',
        'tenant' => 'Mary Wanjiku',
        'status' => 'Occupied',
    ],

    'UNIT-00302' => [
        'id' => 'UNIT-00302',
        'number' => 'C-302',
        'property_id' => 'PROP-003',
        'property' => 'Palm Heights',
        'type' => '2 Bedroom',
        'rent' => 30000,
        'tenant_id' => 'TEN-00126',
        'tenant' => 'Peter Kamau',
        'status' => 'Occupied',
    ],
];


/* ============================================================
   PAYMENTS
   ============================================================ */

$payments = [

    [
        'id' => 'PAY-10045',
        'tenant_id' => 'TEN-00124',
        'tenant' => 'John Mwangi',
        'property_id' => 'PROP-001',
        'property' => 'Greenview Apartments',
        'unit' => 'A-101',
        'amount' => 25000,
        'date' => 'Sep 7, 2026',
        'status' => 'Paid',
        'method' => 'M-Pesa',
    ],

    [
        'id' => 'PAY-10044',
        'tenant_id' => 'TEN-00125',
        'tenant' => 'Mary Wanjiku',
        'property_id' => 'PROP-002',
        'property' => 'Sunrise Estate',
        'unit' => 'B-204',
        'amount' => 35000,
        'date' => 'Sep 6, 2026',
        'status' => 'Paid',
        'method' => 'Bank Transfer',
    ],

    [
        'id' => 'PAY-10043',
        'tenant_id' => 'TEN-00126',
        'tenant' => 'Peter Kamau',
        'property_id' => 'PROP-003',
        'property' => 'Palm Heights',
        'unit' => 'C-302',
        'amount' => 30000,
        'date' => 'Sep 5, 2026',
        'status' => 'Pending',
        'method' => 'M-Pesa',
    ],
];


/* ============================================================
   LEASES
   ============================================================ */

$leases = [

    [
        'id' => 'LS-00124',
        'tenant_id' => 'TEN-00124',
        'tenant' => 'John Mwangi',
        'property_id' => 'PROP-001',
        'property' => 'Greenview Apartments',
        'unit_id' => 'UNIT-00101',
        'unit' => 'A-101',
        'start' => 'Jan 1, 2026',
        'end' => 'Dec 31, 2026',
        'rent' => 25000,
        'status' => 'Active',
    ],

    [
        'id' => 'LS-00125',
        'tenant_id' => 'TEN-00125',
        'tenant' => 'Mary Wanjiku',
        'property_id' => 'PROP-002',
        'property' => 'Sunrise Estate',
        'unit_id' => 'UNIT-00204',
        'unit' => 'B-204',
        'start' => 'Mar 1, 2026',
        'end' => 'Feb 28, 2027',
        'rent' => 35000,
        'status' => 'Active',
    ],

    [
        'id' => 'LS-00126',
        'tenant_id' => 'TEN-00126',
        'tenant' => 'Peter Kamau',
        'property_id' => 'PROP-003',
        'property' => 'Palm Heights',
        'unit_id' => 'UNIT-00302',
        'unit' => 'C-302',
        'start' => 'Sep 1, 2026',
        'end' => 'Aug 31, 2027',
        'rent' => 30000,
        'status' => 'Active',
    ],
];


/* ============================================================
   EXPENSES
   ============================================================ */

$expenses = [

    [
        'id' => 'EXP-001',
        'description' => 'Plumbing repairs',
        'property_id' => 'PROP-001',
        'property' => 'Greenview Apartments',
        'category' => 'Maintenance',
        'amount' => 35000,
        'date' => 'Sep 7, 2026',
    ],

    [
        'id' => 'EXP-002',
        'description' => 'Security services',
        'property_id' => 'PROP-002',
        'property' => 'Sunrise Estate',
        'category' => 'Security',
        'amount' => 50000,
        'date' => 'Sep 5, 2026',
    ],

    [
        'id' => 'EXP-003',
        'description' => 'Electrical repairs',
        'property_id' => 'PROP-003',
        'property' => 'Palm Heights',
        'category' => 'Maintenance',
        'amount' => 15000,
        'date' => 'Sep 4, 2026',
    ],
];


/* ============================================================
   MAINTENANCE REQUESTS
   ============================================================ */

$maintenanceRequests = [

    [
        'id' => 'MNT-001',
        'title' => 'Broken water pipe',
        'property_id' => 'PROP-001',
        'property' => 'Greenview Apartments',
        'unit' => 'A-204',
        'tenant_id' => 'TEN-00124',
        'tenant' => 'John Mwangi',
        'date' => 'Sep 7, 2026',
        'status' => 'Urgent',
    ],

    [
        'id' => 'MNT-002',
        'title' => 'Faulty electricity',
        'property_id' => 'PROP-002',
        'property' => 'Sunrise Estate',
        'unit' => 'B-112',
        'tenant_id' => 'TEN-00125',
        'tenant' => 'Mary Wanjiku',
        'date' => 'Sep 6, 2026',
        'status' => 'Pending',
    ],

    [
        'id' => 'MNT-003',
        'title' => 'Leaking shower',
        'property_id' => 'PROP-003',
        'property' => 'Palm Heights',
        'unit' => 'Unit 12',
        'tenant_id' => null,
        'tenant' => 'David Repairs',
        'date' => 'Sep 5, 2026',
        'status' => 'Assigned',
    ],
];