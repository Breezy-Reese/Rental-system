<?php

$apiUrl = getenv('PROPERTYPRO_API_URL');

if ($apiUrl === false || trim($apiUrl) === '') {
    $apiUrl = 'http://localhost:5000/api';
}

$apiUrl = rtrim(trim($apiUrl), '/') . '/health';

echo '<h2>PropertyPro API Connection Test</h2>';
echo '<p><strong>API URL:</strong> ' . htmlspecialchars($apiUrl) . '</p>';

$ch = curl_init($apiUrl);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_CONNECTTIMEOUT => 20,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo '<p><strong>HTTP Code:</strong> ' . htmlspecialchars((string)$httpCode) . '</p>';

if ($response === false || $error !== '') {
    echo '<h3 style="color:red;">CONNECTION FAILED</h3>';
    echo '<pre>' . htmlspecialchars($error) . '</pre>';
    exit;
}

echo '<h3 style="color:green;">CONNECTION SUCCESSFUL</h3>';
echo '<pre>' . htmlspecialchars($response) . '</pre>';