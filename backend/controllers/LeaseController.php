<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Lease.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/functions.php';

final class LeaseController
{
    public static function index(): never
    {
        try {
            success_response(Lease::all());
        } catch (Throwable $e) {
            error_response($e->getMessage(), 500);
        }
    }

    public static function show(string $id): never
    {
        $lease = Lease::findById($id);

        if (!$lease) {
            error_response('Lease not found.', 404);
        }

        success_response($lease);
    }

    public static function byTenant(string $tenantId): never
    {
        try {
            success_response(
                Lease::findByTenant($tenantId)
            );
        } catch (Throwable $e) {
            error_response($e->getMessage(), 500);
        }
    }

    public static function store(): never
    {
        try {
            $data = request_data();

            $required = [
                'tenantId',
                'propertyId',
                'unitId',
                'startDate',
            ];

            foreach ($required as $field) {
                if (
                    !isset($data[$field]) ||
                    trim((string) $data[$field]) === ''
                ) {
                    error_response(
                        "$field is required.",
                        422
                    );
                }
            }

            $id = generate_id('LS');

            Lease::create([
                'id' => $id,
                'tenantId' => $data['tenantId'],
                'propertyId' => $data['propertyId'],
                'unitId' => $data['unitId'],
                'startDate' => $data['startDate'],
                'endDate' => $data['endDate'] ?? null,
                'rent' => (float) ($data['rent'] ?? 0),
                'deposit' => (float) ($data['deposit'] ?? 0),
                'status' => $data['status'] ?? 'Active',
            ]);

            success_response(
                ['id' => $id],
                'Lease created successfully.'
            );
        } catch (Throwable $e) {
            error_response($e->getMessage(), 422);
        }
    }

    public static function update(string $id): never
    {
        try {
            if (!Lease::findById($id)) {
                error_response('Lease not found.', 404);
            }

            $data = request_data();

            unset(
                $data['id'],
                $data['_id'],
                $data['createdAt']
            );

            Lease::update($id, $data);

            success_response(
                null,
                'Lease updated successfully.'
            );
        } catch (Throwable $e) {
            error_response($e->getMessage(), 422);
        }
    }
}