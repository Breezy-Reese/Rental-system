<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/helpers/response.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/routes/web.php';

try {
    dispatch_route();
} catch (Throwable $e) {

    error_log(
        '[' . date('Y-m-d H:i:s') . '] ' .
        $e->getMessage() .
        PHP_EOL,
        3,
        __DIR__ . '/storage/logs/app.log'
    );

    error_response(
        'Internal server error.',
        500
    );
}