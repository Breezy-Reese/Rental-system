<?php

declare(strict_types=1);

function json_response(
    mixed $data = null,
    int $statusCode = 200
): never {

    http_response_code($statusCode);

    header('Content-Type: application/json');

    echo json_encode(
        [
            'success' => $statusCode >= 200 && $statusCode < 300,
            'data' => $data,
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

function success_response(
    mixed $data = null,
    string $message = 'Success'
): never {

    json_response(
        [
            'message' => $message,
            'data' => $data,
        ],
        200
    );
}

function error_response(
    string $message,
    int $statusCode = 400,
    mixed $errors = null
): never {

    http_response_code($statusCode);

    header('Content-Type: application/json');

    echo json_encode(
        [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}