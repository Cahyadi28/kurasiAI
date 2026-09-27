<?php

declare(strict_types=1);

// Server kecil untuk CurlHttpClientTest, dijalankan dengan php -S. Mengembalikan apa yang diterimanya.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path === '/lambat') {
    sleep(3);
}

header('Content-Type: application/json');
header('request-id: req_lokal_123');
http_response_code($path === '/batas' ? 429 : 200);

echo json_encode([
    'method' => $_SERVER['REQUEST_METHOD'] ?? null,
    'authorization' => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? null,
    'body' => json_decode((string) file_get_contents('php://input'), true),
]);
