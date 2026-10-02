<?php
// frontend/api/index.php — Vercel's only PHP entry point
declare(strict_types=1);

$root = dirname(__DIR__);
set_include_path(
    $root . PATH_SEPARATOR .
    $root . '/includes' . PATH_SEPARATOR .
    $root . '/components' . PATH_SEPARATOR .
    get_include_path()
);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = '/' . ltrim($uri, '/');

$publicDir  = $root . '/public';
$targetFile = $publicDir . $uri;

if (is_dir($targetFile)) {
    $targetFile = rtrim($targetFile, '/') . '/index.php';
}
if ($uri === '/' || $uri === '') {
    $targetFile = $publicDir . '/index.php';
}

// Static files
if (is_file($targetFile) && !str_ends_with($targetFile, '.php')) {
    $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $mimes = [
        'css'=>'text/css','js'=>'application/javascript','json'=>'application/json',
        'png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif',
        'svg'=>'image/svg+xml','ico'=>'image/x-icon','webp'=>'image/webp',
        'woff'=>'font/woff','woff2'=>'font/woff2','ttf'=>'font/ttf',
    ];
    header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
    readfile($targetFile);
    exit;
}

// PHP files
if (is_file($targetFile) && str_ends_with($targetFile, '.php')) {
    chdir(dirname($targetFile));
    require $targetFile;
    exit;
}

http_response_code(404);
header('Content-Type: text/html');
echo '<h1>404 — Not Found</h1><p>' . htmlspecialchars($uri) . '</p>';