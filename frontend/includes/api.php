<?php



if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

define(
    'PROPERTYPRO_API_URL',
    getenv('PROPERTYPRO_API_URL') ?: 'http://localhost:5000/api'
);

function api_request(
    string $method,
    string $endpoint,
    ?array $body = null,
    ?string $token = null
): array {
    $url = rtrim(PROPERTYPRO_API_URL, '/') . '/' . ltrim($endpoint, '/');

    $ch = curl_init($url);

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
    ];

    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);

    if ($body !== null) {
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            json_encode($body)
        );
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false || $curlError) {
        return [
            'success' => false,
            'message' => 'Unable to connect to PropertyPro API.',
            'http_code' => 0,
        ];
    }

    $decoded = json_decode($response, true);

    if (!is_array($decoded)) {
        return [
            'success' => false,
            'message' => 'Invalid response received from PropertyPro API.',
            'http_code' => $httpCode,
        ];
    }

    $decoded['http_code'] = $httpCode;

    return $decoded;
}

function api_token(): string
{
    return $_SESSION['propertypro_token'] ?? '';
}

function api_user(): array
{
    return $_SESSION['user'] ?? [];
}

function api_authenticated(): bool
{
    return api_token() !== '';
}

function api_get(string $endpoint): array
{
    return api_request(
        'GET',
        $endpoint,
        null,
        api_token()
    );
}

function api_post(string $endpoint, array $body): array
{
    return api_request(
        'POST',
        $endpoint,
        $body,
        api_token()
    );
}

function api_put(string $endpoint, array $body): array
{
    return api_request(
        'PUT',
        $endpoint,
        $body,
        api_token()
    );
}

function api_patch(string $endpoint, array $body = []): array
{
    return api_request(
        'PATCH',
        $endpoint,
        $body,
        api_token()
    );
}

function api_delete(string $endpoint): array
{
    return api_request(
        'DELETE',
        $endpoint,
        null,
        api_token()
    );
}
