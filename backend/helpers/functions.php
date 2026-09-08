<?php

declare(strict_types=1);

function request_data(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (
        str_contains(
            strtolower($contentType),
            'application/json'
        )
    ) {
        $input = file_get_contents('php://input');

        if (!$input) {
            return [];
        }

        $data = json_decode(
            $input,
            true
        );

        return is_array($data) ? $data : [];
    }

    return $_POST;
}

function clean_string(
    mixed $value
): string {

    return trim(
        htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

function generate_id(
    string $prefix
): string {

    return $prefix . '-' .
        strtoupper(
            substr(
                bin2hex(random_bytes(6)),
                0,
                8
            )
        );
}

function money(float $amount): string
{
    return 'KSh ' .
        number_format(
            $amount,
            2
        );
}

function current_user_id(): ?string
{
    return $_SESSION['user']['id'] ?? null;
}

function current_user_name(): ?string
{
    return $_SESSION['user']['name'] ?? null;
}

function current_user_role(): ?string
{
    return $_SESSION['user']['role'] ?? null;
}