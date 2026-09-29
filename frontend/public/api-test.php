
<?php
require_once __DIR__ . '/../includes/api.php';

$result = api_get('health');

header('Content-Type: text/html; charset=UTF-8');

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PropertyPro API Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f8fafc;
            color: #0f172a;
        }
        .card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
        }
        .success { color: #15803d; }
        .error { color: #b91c1c; }
        pre {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            background: #f1f5f9;
            padding: 16px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>PropertyPro API Connection Test</h1>

        <p><strong>API URL:</strong>
            <?= h(PROPERTYPRO_API_URL . '/health') ?>
        </p>

        <p><strong>HTTP status:</strong>
            <?= h($result['http_code'] ?? 0) ?>
        </p>

        <?php if (($result['success'] ?? false) === true): ?>
            <h2 class="success">API responded successfully</h2>
        <?php else: ?>
            <h2 class="error">API request failed</h2>
            <p><?= h($result['message'] ?? 'Unknown error') ?></p>
            <?php if (!empty($result['error'])): ?>
                <p><?= h($result['error']) ?></p>
            <?php endif; ?>
        <?php endif; ?>

        <h3>Response</h3>
        <pre><?= h(json_encode(
            $result,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        )) ?></pre>
    </div>
</body>
</html>