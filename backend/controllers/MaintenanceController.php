<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Maintenance.php';
require_once __DIR__ . '/../models/Tenant.php';
require_once __DIR__ . '/../services/NotificationService.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/functions.php';

final class MaintenanceController
{
    public static function index(): never
    {
        try {
            success_response(
                Maintenance::all()
            );
        } catch (Throwable $e) {
            error_response($e->getMessage(), 500);
        }
    }

    public static function show(string $id): never
    {
        $request = Maintenance::findById($id);

        if (!$request) {
            error_response(
                'Maintenance request not found.',
                404
            );
        }

        success_response($request);
    }

    public static function byTenant(
        string $tenantId
    ): never {

        try {
            success_response(
                Maintenance::findByTenant($tenantId)
            );
        } catch (Throwable $e) {
            error_response($e->getMessage(), 500);
        }
    }

    public static function store(): never
    {
        try {
            $data = request_data();

            if (empty($data['tenantId'])) {
                error_response(
                    'Tenant is required.',
                    422
                );
            }

            if (empty($data['issue'])) {
                error_response(
                    'Maintenance issue is required.',
                    422
                );
            }

            $tenant = Tenant::findById(
                $data['tenantId']
            );

            if (!$tenant) {
                error_response(
                    'Tenant not found.',
                    404
                );
            }

            $requestId = generate_id('MNT');

            $property =
                (string) (
                    $data['property']
                    ?? $data['propertyName']
                    ?? $data['propertyId']
                    ?? ''
                );

            $unit =
                (string) (
                    $data['unit']
                    ?? $data['unitNumber']
                    ?? $data['unitId']
                    ?? ''
                );

            $priority =
                $data['priority']
                ?? 'Normal';

            Maintenance::create([
                'id' => $requestId,
                'tenantId' =>
                    $data['tenantId'],
                'propertyId' =>
                    $data['propertyId'] ?? null,
                'unitId' =>
                    $data['unitId'] ?? null,
                'issue' =>
                    $data['issue'],
                'description' =>
                    $data['description'] ?? '',
                'priority' =>
                    $priority,
                'status' =>
                    $data['status'] ?? 'Pending',
            ]);

            /*
             * This creates the notification in MongoDB.
             * The administrator will see it even in
             * a different browser/session.
             */
            NotificationService::maintenanceCreated(
                (string) (
                    $tenant['userId']
                    ?? $tenant['id']
                ),
                (string) (
                    $tenant['name']
                    ?? 'Customer'
                ),
                $requestId,
                (string) $data['issue'],
                $property,
                $unit,
                (string) $priority
            );

            success_response(
                ['id' => $requestId],
                'Maintenance request submitted successfully.'
            );
        } catch (Throwable $e) {
            error_response($e->getMessage(), 422);
        }
    }

    public static function update(
        string $id
    ): never {

        try {
            $request =
                Maintenance::findById($id);

            if (!$request) {
                error_response(
                    'Maintenance request not found.',
                    404
                );
            }

            $data = request_data();

            unset(
                $data['id'],
                $data['_id'],
                $data['createdAt']
            );

            Maintenance::update(
                $id,
                $data
            );

            /*
             * Notify customer when administrator
             * changes the maintenance status.
             */
            if (
                isset($data['status']) &&
                !empty($request['tenantId'])
            ) {
                $tenant = Tenant::findById(
                    (string) $request['tenantId']
                );

                if (
                    $tenant &&
                    !empty($tenant['userId'])
                ) {
                    NotificationService::maintenanceStatusUpdated(
                        (string) $tenant['userId'],
                        $id,
                        (string) $data['status']
                    );
                }
            }

            success_response(
                null,
                'Maintenance request updated successfully.'
            );
        } catch (Throwable $e) {
            error_response($e->getMessage(), 422);
        }
    }

    public static function destroy(
        string $id
    ): never {

        if (!Maintenance::delete($id)) {
            error_response(
                'Maintenance request not found.',
                404
            );
        }

        success_response(
            null,
            'Maintenance request deleted successfully.'
        );
    }
}