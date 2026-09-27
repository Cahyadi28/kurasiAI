<?php

declare(strict_types=1);

/**
 * Front controller: SATU-SATUNYA pintu masuk aplikasi. Semua alamat (/, /drafts/12, /login, ...)
 * diarahkan ke berkas ini, lalu router memilih controller yang tepat.
 *
 * Menjalankan di komputer sendiri:  php -S localhost:8000 -t public public/index.php
 */

// Server bawaan PHP (php -S): berkas yang benar-benar ada di public/, seperti CSS,
// dilayani langsung oleh server. Dicek dengan realpath() supaya "../" tidak bisa keluar dari public/.
if (PHP_SAPI === 'cli-server') {
    $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $file = is_string($path) ? realpath(__DIR__ . $path) : false;

    if ($file !== false && is_file($file) && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR)) {
        return false;
    }
}

require_once __DIR__ . '/../vendor/autoload.php';

KurasiAI\App::run(dirname(__DIR__));
