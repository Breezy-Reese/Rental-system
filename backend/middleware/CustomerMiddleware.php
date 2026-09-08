<?php

declare(strict_types=1);

require_once __DIR__ . '/AuthMiddleware.php';

final class CustomerMiddleware
{
    public static function handle(): void
    {
        AuthMiddleware::handle();

        if (AuthMiddleware::role() !== 'Customer') {
            error_response(
                'Customer access required.',
                403
            );
        }
    }

    public static function userId(): string
    {
        self::handle();

        $userId = AuthMiddleware::userId();

        if ($userId === '') {
            error_response(
                'Customer account ID is missing.',
                401
            );
        }

        return $userId;
    }
}