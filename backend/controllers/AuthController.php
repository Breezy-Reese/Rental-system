<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/functions.php';

final class AuthController
{
    public static function login(): never
    {
        try {
            $data = request_data();

            $email = required(
                $data['email'] ?? null,
                'Email'
            );

            $password = required(
                $data['password'] ?? null,
                'Password'
            );

            valid_email($email);

            $user = AuthService::login(
                $email,
                $password
            );

            if (!$user) {
                error_response(
                    'Invalid email or password.',
                    401
                );
            }

            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            session_regenerate_id(true);

            $_SESSION['user'] = $user;

            success_response(
                [
                    'user' => $user,
                    'redirect' =>
                        $user['role'] === 'Administrator'
                            ? '/admin/dashboard.php'
                            : '/customer/dashboard.php',
                ],
                'Login successful.'
            );

        } catch (Throwable $e) {

            error_response(
                $e->getMessage(),
                400
            );
        }
    }

    public static function register(): never
    {
        try {
            $data = request_data();

            $name = required(
                $data['name'] ?? null,
                'Name'
            );

            $email = required(
                $data['email'] ?? null,
                'Email'
            );

            $password = required(
                $data['password'] ?? null,
                'Password'
            );

            $phone = trim(
                (string) (
                    $data['phone'] ?? ''
                )
            );

            valid_email($email);

            if (strlen($password) < 6) {
                error_response(
                    'Password must contain at least 6 characters.',
                    422
                );
            }

            $userId = AuthService::register(
                $name,
                $email,
                $password,
                $phone
            );

            success_response(
                [
                    'userId' => $userId,
                ],
                'Registration successful.'
            );

        } catch (Throwable $e) {

            error_response(
                $e->getMessage(),
                400
            );
        }
    }

    public static function logout(): never
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {

            $params =
                session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        success_response(
            null,
            'Logout successful.'
        );
    }

    public static function me(): never
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (
            empty($_SESSION['user'])
        ) {
            error_response(
                'Not authenticated.',
                401
            );
        }

        success_response(
            $_SESSION['user']
        );
    }
}