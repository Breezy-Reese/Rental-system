<?php

/**
 * ============================================================
 * PropertyPro API Connection
 * ============================================================
 *
 * LOCAL:
 *   http://localhost:5000/api
 *
 * PRODUCTION:
 *   https://rental-system-hvnn.onrender.com/api
 *
 * The application automatically detects the environment.
 * ============================================================
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


/**
 * ------------------------------------------------------------
 * API BASE URL
 * ------------------------------------------------------------
 *
 * Priority:
 *
 * 1. PROPERTYPRO_API_URL environment variable
 * 2. Render production URL when running on Render
 * 3. localhost for local development
 *
 * This prevents production from accidentally trying to call
 * localhost:5000 inside the PHP container.
 */
function get_api_base_url(): string
{
    $configuredUrl = getenv('PROPERTYPRO_API_URL');

    if ($configuredUrl !== false && trim($configuredUrl) !== '') {
        return rtrim(trim($configuredUrl), '/');
    }

    // Render automatically provides RENDER=true.
    if (getenv('RENDER') === 'true' || getenv('RENDER_SERVICE_ID')) {
        return 'https://rental-system-hvnn.onrender.com/api';
    }

    return 'http://localhost:5000/api';
}


/**
 * ------------------------------------------------------------
 * API REQUEST
 * ------------------------------------------------------------
 */
function api_request(
    string $method,
    string $endpoint,
    ?array $data = null,
    array $extraHeaders = []
): array {
    $baseUrl = get_api_base_url();

    $endpoint = '/' . ltrim($endpoint, '/');

    $url = $baseUrl . $endpoint;

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
    ];

    /*
     * Add JWT token when available.
     */
    if (!empty($_SESSION['token'])) {
        $headers[] = 'Authorization: Bearer ' . $_SESSION['token'];
    }

    /*
     * Allow callers to provide additional headers.
     */
    foreach ($extraHeaders as $header) {
        $headers[] = $header;
    }

    $ch = curl_init();

    if ($ch === false) {
        return [
            'success' => false,
            'message' => 'Unable to initialize API connection.',
            'data' => null,
            'status' => 0,
            'url' => $url,
        ];
    }

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ];

    if ($data !== null && strtoupper($method) !== 'GET') {
        $jsonData = json_encode($data);

        if ($jsonData === false) {
            curl_close($ch);

            return [
                'success' => false,
                'message' => 'Failed to encode request data.',
                'data' => null,
                'status' => 0,
                'url' => $url,
            ];
        }

        $options[CURLOPT_POSTFIELDS] = $jsonData;
    }

    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);

    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);

    $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    /*
     * cURL/network error.
     */
    if ($response === false || $curlErrno !== 0) {
        error_log(
            'PropertyPro API CURL ERROR: ' .
            $curlError .
            ' | URL: ' .
            $url
        );

        return [
            'success' => false,
            'message' => 'Unable to connect to the PropertyPro API.',
            'error' => $curlError,
            'data' => null,
            'status' => $httpStatus,
            'url' => $url,
        ];
    }

    /*
     * Decode JSON response.
     */
    $decoded = json_decode($response, true);

    /*
     * Invalid/non-JSON response.
     */
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log(
            'PropertyPro API INVALID JSON: ' .
            json_last_error_msg() .
            ' | HTTP: ' .
            $httpStatus .
            ' | URL: ' .
            $url .
            ' | RESPONSE: ' .
            substr($response, 0, 1000)
        );

        return [
            'success' => false,
            'message' => 'The API returned an invalid response.',
            'error' => json_last_error_msg(),
            'data' => $response,
            'status' => $httpStatus,
            'url' => $url,
        ];
    }

    /*
     * Log failed API requests.
     */
    if ($httpStatus >= 400) {
        error_log(
            'PropertyPro API ERROR: ' .
            'HTTP ' . $httpStatus .
            ' | ' .
            strtoupper($method) .
            ' ' .
            $url .
            ' | RESPONSE: ' .
            $response
        );
    }

    /*
     * Normalize response.
     */
    if (!is_array($decoded)) {
        $decoded = [
            'success' => false,
            'message' => 'Unexpected API response.',
            'data' => $decoded,
        ];
    }

    $decoded['status'] = $httpStatus;
    $decoded['url'] = $url;

    return $decoded;
}


/**
 * ============================================================
 * CONVENIENCE FUNCTIONS
 * ============================================================
 */

function api_get(string $endpoint, array $query = []): array
{
    if (!empty($query)) {
        $endpoint .= '?' . http_build_query($query);
    }

    return api_request('GET', $endpoint);
}


function api_post(string $endpoint, array $data = []): array
{
    return api_request('POST', $endpoint, $data);
}


function api_put(string $endpoint, array $data = []): array
{
    return api_request('PUT', $endpoint, $data);
}


function api_patch(string $endpoint, array $data = []): array
{
    return api_request('PATCH', $endpoint, $data);
}


function api_delete(string $endpoint, array $data = []): array
{
    return api_request('DELETE', $endpoint, $data);
}


/**
 * ============================================================
 * API BASE URL HELPER
 * ============================================================
 */
function api_base_url(): string
{
    return get_api_base_url();
}


/**
 * ============================================================
 * DEBUG HELPER
 * ============================================================
 *
 * Useful during development.
 */
function api_connection_info(): array
{
    return [
        'base_url' => get_api_base_url(),
        'environment' => (
            getenv('RENDER') === 'true' ||
            getenv('RENDER_SERVICE_ID')
        )
            ? 'production'
            : 'local',
    ];
}

