<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Unit.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/functions.php';

final class UnitController
{
    public static function index(): never
    {
        try {
            success_response(
                Unit::all()
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

        $unit = Unit::findById($id);

        if (!$unit) {
            error_response(
                'Unit not found.',
                404
            );
        }

        success_response($unit);
    }

    public static function byProperty(
        string $propertyId
    ): never {

        success_response(
            Unit::findByProperty(
                $propertyId
            )
        );
    }

    public static function store(): never
    {
        try {
            $data = request_data();

            if (
                empty($data['propertyId']) ||
                empty($data['unitNumber'])
            ) {
                error_response(
                    'Property and unit number are required.',
                    422
                );
            }

            $id = generate_id('UNIT');

            Unit::create([
                'id' => $id,
                'propertyId' =>
                    $data['propertyId'],
                'unitNumber' =>
                    $data['unitNumber'],
                'type' =>
                    $data['type'] ?? 'Standard',
                'rent' =>
                    (float) (
                        $data['rent'] ?? 0
                    ),
                'status' =>
                    $data['status'] ?? 'Vacant',
                'tenantId' =>
                    $data['tenantId'] ?? null,
            ]);

            success_response(
                ['id' => $id],
                'Unit created successfully.'
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

        try {
            if (!Unit::findById($id)) {
                error_response(
                    'Unit not found.',
                    404
                );
            }

            $data = request_data();

            unset(
                $data['id'],
                $data['_id'],
                $data['createdAt']
            );

            Unit::update(
                $id,
                $data
            );

            success_response(
                null,
                'Unit updated successfully.'
            );

        } catch (Throwable $e) {
            error_response(
                $e->getMessage(),
                422
            );
        }
    }

    public static function destroy(
        string $id
    ): never {

        if (!Unit::delete($id)) {
            error_response(
                'Unit not found.',
                404
            );
        }

        success_response(
            null,
            'Unit deleted successfully.'
        );
    }
}