
<?php
/**
 * PropertyPro API Connection
 *
 * Local:      http://localhost:5000/api
 * Production: Set PROPERTYPRO_API_URL on the frontend hosting service:
 *             https://rental-system-hvnn.onrender.com/api
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Configure the API base URL.
 */
$propertyProApiUrl = getenv('PROPERTYPRO_API_URL');

if ($propertyProApiUrl === false || trim($propertyProApiUrl) === '') {
    $propertyProApiUrl = 'http://localhost:5000/api';
}

if (!defined('PROPERTYPRO_API_URL')) {
    define('PROPERTYPRO_API_URL', rtrim(trim($propertyProApiUrl), '/'));
}

/**
 * Send an HTTP request to the PropertyPro backend.
 */
function api_request(
    string $method,
    string $endpoint,
    ?array $body = null,
    ?string $token = null
): array {
    $url = PROPERTYPRO_API_URL . '/' . ltrim($endpoint, '/');

    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'message' => 'PHP cURL is not enabled on the frontend server.',
            'http_code' => 0,
        ];
    }

    $ch = curl_init($url);

    if ($ch === false) {
        return [
            'success' => false,
            'message' => 'Unable to initialize the API connection.',
            'http_code' => 0,
        ];
    }

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
    ];

    if (!empty($token)) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ];

    if ($body !== null) {
        $jsonBody = json_encode(
            $body,
            JSON_UNESCAPED_SLASHES
        );

        if ($jsonBody === false) {
            curl_close($ch);

            return [
                'success' => false,
                'message' => 'Unable to encode the API request.',
                'http_code' => 0,
            ];
        }

        $options[CURLOPT_POSTFIELDS] = $jsonBody;
    }

    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false) {
        return [
            'success' => false,
            'message' => 'Unable to connect to PropertyPro API.',
            'http_code' => $httpCode,
            'error' => $curlError ?: 'Unknown cURL error.',
        ];
    }

    $decoded = json_decode($response, true);

    if (!is_array($decoded)) {
        return [
            'success' => false,
            'message' => 'The API returned a non-JSON response.',
            'http_code' => $httpCode,
            'error' => json_last_error_msg(),
            'raw_response' => $response,
        ];
    }

    $decoded['http_code'] = $httpCode;

    // Treat HTTP errors as unsuccessful even if the response
    // body does not contain a success field.
    if ($httpCode < 200 || $httpCode >= 300) {
        $decoded['success'] = false;

        if (empty($decoded['message'])) {
            $decoded['message'] = 'The API request failed.';
        }
    }

    return $decoded;
}

/**
 * Authentication helpers.
 */
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

/**
 * GET request.
 */
function api_get(string $endpoint): array
{
    return api_request('GET', $endpoint, null, api_token());
}

/**
 * POST request.
 */
function api_post(string $endpoint, array $body): array
{
    return api_request('POST', $endpoint, $body, api_token());
}

/**
 * PUT request.
 */
function api_put(string $endpoint, array $body): array
{
    return api_request('PUT', $endpoint, $body, api_token());
}

/**
 * PATCH request.
 */
function api_patch(string $endpoint, array $body = []): array
{
    return api_request('PATCH', $endpoint, $body, api_token());
}

/**
 * DELETE request.
 */
function api_delete(string $endpoint): array
{
    return api_request('DELETE', $endpoint, null, api_token());
}