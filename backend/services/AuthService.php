<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/functions.php';

final class AuthService
{
    public static function login(
        string $email,
        string $password
    ): ?array {

        $user = User::findByEmail($email);

        if (!$user) {
            return null;
        }

        if (
            !User::verifyPassword(
                $user,
                $password
            )
        ) {
            return null;
        }

        if (
            isset($user['status']) &&
            $user['status'] !== 'Active'
        ) {
            return null;
        }

        unset($user['password']);

        return $user;
    }

    public static function register(
        string $name,
        string $email,
        string $password,
        string $phone = ''
    ): string {

        $existing = User::findByEmail($email);

        if ($existing) {
            throw new RuntimeException(
                'An account with this email already exists.'
            );
        }

        $userId = generate_id('CUS');

        return User::create([
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'phone' => $phone,
            'role' => 'Customer',
            'status' => 'Active',
        ]);
    }
}