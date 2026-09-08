<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/functions.php';

final class UserController
{
    public static function show(
        string $id
    ): never {

        $user = User::findById($id);

        if (!$user) {
            error_response(
                'User not found.',
                404
            );
        }

        unset($user['password']);

        success_response($user);
    }

    public static function update(
        string $id
    ): never {

        try {
            $user = User::findById($id);

            if (!$user) {
                error_response(
                    'User not found.',
                    404
                );
            }

            $data = request_data();

            unset(
                $data['id'],
                $data['_id'],
                $data['password'],
                $data['role'],
                $data['createdAt']
            );

            User::update($id, $data);

            $updated =
                User::findById($id);

            if ($updated) {
                unset($updated['password']);
            }

            success_response(
                $updated,
                'Profile updated successfully.'
            );
        } catch (Throwable $e) {
            error_response(
                $e->getMessage(),
                422
            );
        }
    }
}