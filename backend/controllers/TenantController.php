<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Tenant.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/functions.php';

final class TenantController
{
    public static function index(): never
    {
        try {
            success_response(
                Tenant::all()
            );
        } catch (Throwable $e) {
            error_response(
                $e->getMessage(),
                500
            );
        }
    }

    public static function show(
        string $id
    ): never {

        $tenant = Tenant::findById($id);

        if (!$tenant) {
            error_response(
                'Tenant not found.',
                404
            );
        }

        success_response($tenant);
    }

    public static function byUser(
        string $userId
    ): never {

        $tenant =
            Tenant::findByUserId($userId);

        if (!$tenant) {
            error_response(
                'Tenant not found.',
                404
            );
        }

        success_response($tenant);
    }

    public static function store(): never
    {
        try {
            $data = request_data();

            if (empty($data['name'])) {
                error_response(
                    'Tenant name is required.',
                    422
                );
            }

            $id = generate_id('TEN');

            Tenant::create([
                'id' => $id,
                'userId' =>
                    $data['userId'] ?? null,
                'name' =>
                    $data['name'],
                'email' =>
                    $data['email'] ?? '',
                'phone' =>
                    $data['phone'] ?? '',
                'propertyId' =>
                    $data['propertyId'] ?? null,
                'unitId' =>
                    $data['unitId'] ?? null,
                'status' =>
                    $data['status'] ?? 'Active',
            ]);

            success_response(
                ['id' => $id],
                'Tenant created successfully.'
            );

        } catch (Throwable $e) {
            error_response(
                $e->getMessage(),
                422
            );
        }
    }

    public static function update(
        string $id
    ): never {

        if (!Tenant::findById($id)) {
            error_response(
                'Tenant not found.',
                404
            );
        }

        $data = request_data();

        unset(
            $data['id'],
            $data['_id'],
            $data['createdAt']
        );

        Tenant::update(
            $id,
            $data
        );

        success_response(
            null,
            'Tenant updated successfully.'
        );
    }
}