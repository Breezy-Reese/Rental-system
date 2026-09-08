<?php

declare(strict_types=1);

require_once __DIR__ . '/AuthMiddleware.php';

final class AdminMiddleware
{
    public static function handle(): void
    {
        AuthMiddleware::handle();

        if (AuthMiddleware::role() !== 'Administrator') {
            error_response(
                'Administrator access required.',
                403
            );
        }
    }
}