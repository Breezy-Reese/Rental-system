<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Property.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/functions.php';

final class PropertyController
{
    public static function index(): never
    {
        try {
            success_response(
                Property::all()
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

        try {
            $property =
                Property::findById($id);

            if (!$property) {
                error_response(
                    'Property not found.',
                    404
                );
            }

            success_response($property);

        } catch (Throwable $e) {
            error_response(
                $e->getMessage(),
                500
            );
        }
    }

    public static function store(): never
    {
        try {
            $data = request_data();

            $name = required(
                $data['name'] ?? null,
                'Property name'
            );

            $id = generate_id('PROP');

            Property::create([
                'id' => $id,
                'name' => $name,
                'location' =>
                    $data['location'] ?? '',
                'address' =>
                    $data['address'] ?? '',
                'description' =>
                    $data['description'] ?? '',
                'totalUnits' =>
                    (int) (
                        $data['totalUnits'] ?? 0
                    ),
                'status' =>
                    $data['status'] ?? 'Active',
            ]);

            success_response(
                [
                    'id' => $id,
                ],
                'Property created successfully.'
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
            if (!Property::findById($id)) {
                error_response(
                    'Property not found.',
                    404
                );
            }

            $data = request_data();

            unset(
                $data['id'],
                $data['_id'],
                $data['createdAt']
            );

            Property::update(
                $id,
                $data
            );

            success_response(
                null,
                'Property updated successfully.'
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

        try {
            if (!Property::delete($id)) {
                error_response(
                    'Property not found.',
                    404
                );
            }

            success_response(
                null,
                'Property deleted successfully.'
            );

        } catch (Throwable $e) {
            error_response(
                $e->getMessage(),
                500
            );
        }
    }
}