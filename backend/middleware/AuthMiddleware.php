<?php

declare(strict_types=1);

require_once __DIR__ . '/../helpers/response.php';

final class AuthMiddleware
{
    public static function handle(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['user'])) {
            error_response(
                'Authentication required.',
                401
            );
        }
    }

    public static function user(): array
    {
        self::handle();

        return $_SESSION['user'];
    }

    public static function userId(): string
    {
        $user = self::user();

        return (string) ($user['id'] ?? '');
    }

    public static function role(): string
    {
        $user = self::user();

        return (string) ($user['role'] ?? '');
    }
}