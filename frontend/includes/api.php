<?php

/**
 * ============================================================
 * PropertyPro API Connection
 * ============================================================
 *
 * LOCAL DEVELOPMENT:
 *   http://localhost:5000/api
 *
 * PRODUCTION / RENDER:
 *   Set the Render environment variable:
 *
 *   PROPERTYPRO_API_URL=https://rental-system-hvnn.onrender.com/api
 *
 * The same PHP code therefore works both locally and on Render.
 * ============================================================
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| API Base URL
|--------------------------------------------------------------------------
|
| Render will use the PROPERTYPRO_API_URL environment variable.
|
| When developing locally, if the variable is not set, the application
| automatically uses the local Node.js backend.
|
*/

$propertyProApiUrl = getenv('PROPERTYPRO_API_URL');

if ($propertyProApiUrl === false || trim($propertyProApiUrl) === '') {
    $propertyProApiUrl = 'http://localhost:5000/api';
}

define(
    'PROPERTYPRO_API_URL',
    rtrim(trim($propertyProApiUrl), '/')
);


/**
 * ============================================================
 * API REQUEST
 * ============================================================
 *
 * Sends a request from the PHP frontend to the Node.js backend.
 *
 * @param string      $method
 * @param string      $endpoint
 * @param array|null  $body
 * @param string|null $token
 *
 * @return array
 */
function api_request(
    string $method,
    string $endpoint,
    ?array $body = null,
    ?string $token = null
): array {

    /*
    |--------------------------------------------------------------------------
    | Build URL
    |--------------------------------------------------------------------------
    */

    $url =
        PROPERTYPRO_API_URL .
        '/' .
        ltrim($endpoint, '/');


    /*
    |--------------------------------------------------------------------------
    | Initialize cURL
    |--------------------------------------------------------------------------
    */

    $ch = curl_init($url);

    if ($ch === false) {
        return [
            'success' => false,
            'message' => 'Unable to initialize the API connection.',
            'http_code' => 0,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Request Headers
    |--------------------------------------------------------------------------
    */

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
    ];


    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    |
    | If the user is logged in, send the JWT token to the backend.
    |
    */

    if (!empty($token)) {
        $headers[] =
            'Authorization: Bearer ' . $token;
    }


    /*
    |--------------------------------------------------------------------------
    | cURL Options
    |--------------------------------------------------------------------------
    */

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_CUSTOMREQUEST =>
            strtoupper($method),

        CURLOPT_HTTPHEADER =>
            $headers,

        /*
        | Allow enough time for Render's free service to wake up.
        */
        CURLOPT_CONNECTTIMEOUT => 15,

        CURLOPT_TIMEOUT => 60,

        CURLOPT_FOLLOWLOCATION => true,

        /*
        | HTTPS certificate verification.
        */
        CURLOPT_SSL_VERIFYPEER => true,

        CURLOPT_SSL_VERIFYHOST => 2,
    ]);


    /*
    |--------------------------------------------------------------------------
    | Request Body
    |--------------------------------------------------------------------------
    */

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

        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            $jsonBody
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Execute Request
    |--------------------------------------------------------------------------
    */

    $response = curl_exec($ch);

    $httpCode = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    $curlError = curl_error($ch);

    curl_close($ch);


    /*
    |--------------------------------------------------------------------------
    | Connection Error
    |--------------------------------------------------------------------------
    */

    if ($response === false || $curlError !== '') {

        return [
            'success' => false,

            'message' =>
                'Unable to connect to PropertyPro API.',

            'http_code' =>
                $httpCode ?: 0,

            /*
            | Useful while debugging.
            | This does not expose passwords or JWT tokens.
            */
            'error' =>
                $curlError !== ''
                    ? $curlError
                    : 'Unknown cURL error.',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Decode JSON Response
    |--------------------------------------------------------------------------
    */

    $decoded = json_decode(
        $response,
        true
    );


    /*
    |--------------------------------------------------------------------------
    | Invalid JSON Response
    |--------------------------------------------------------------------------
    */

    if (!is_array($decoded)) {

        return [
            'success' => false,

            'message' =>
                'Invalid response received from PropertyPro API.',

            'http_code' =>
                $httpCode,

            'raw_response' =>
                $response,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Add HTTP Status Code
    |--------------------------------------------------------------------------
    */

    $decoded['http_code'] =
        $httpCode;


    /*
    |--------------------------------------------------------------------------
    | Return API Response
    |--------------------------------------------------------------------------
    */

    return $decoded;
}


/**
 * ============================================================
 * AUTHENTICATION HELPERS
 * ============================================================
 */


/**
 * Get the currently stored JWT token.
 */
function api_token(): string
{
    return
        $_SESSION['propertypro_token']
        ?? '';
}


/**
 * Get the currently logged-in user.
 */
function api_user(): array
{
    return
        $_SESSION['user']
        ?? [];
}


/**
 * Check whether a JWT token exists.
 */
function api_authenticated(): bool
{
    return api_token() !== '';
}


/**
 * ============================================================
 * GET REQUEST
 * ============================================================
 */

function api_get(
    string $endpoint
): array {

    return api_request(
        'GET',
        $endpoint,
        null,
        api_token()
    );
}


/**
 * ============================================================
 * POST REQUEST
 * ============================================================
 */

function api_post(
    string $endpoint,
    array $body
): array {

    return api_request(
        'POST',
        $endpoint,
        $body,
        api_token()
    );
}


/**
 * ============================================================
 * PUT REQUEST
 * ============================================================
 */

function api_put(
    string $endpoint,
    array $body
): array {

    return api_request(
        'PUT',
        $endpoint,
        $body,
        api_token()
    );
}


/**
 * ============================================================
 * PATCH REQUEST
 * ============================================================
 */

function api_patch(
    string $endpoint,
    array $body = []
): array {

    return api_request(
        'PATCH',
        $endpoint,
        $body,
        api_token()
    );
}


/**
 * ============================================================
 * DELETE REQUEST
 * ============================================================
 */

function api_delete(
    string $endpoint
): array {

    return api_request(
        'DELETE',
        $endpoint,
        null,
        api_token()
    );
}