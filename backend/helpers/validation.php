<?php

declare(strict_types=1);

function required(
    mixed $value,
    string $field
): string {

    if (
        $value === null ||
        trim((string) $value) === ''
    ) {
        throw new InvalidArgumentException(
            "$field is required."
        );
    }

    return trim((string) $value);
}

function optional_string(
    mixed $value
): ?string {

    if (
        $value === null ||
        trim((string) $value) === ''
    ) {
        return null;
    }

    return trim((string) $value);
}

function valid_email(string $email): string
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException(
            'Invalid email address.'
        );
    }

    return $email;
}

function positive_number(
    mixed $value,
    string $field
): float {

    if (!is_numeric($value) || (float) $value < 0) {
        throw new InvalidArgumentException(
            "$field must be a valid positive number."
        );
    }

    return (float) $value;
}