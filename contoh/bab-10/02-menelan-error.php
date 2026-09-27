<?php

declare(strict_types=1);

use KurasiAI\Domain\Content\Caption;
use KurasiAI\Exception\ValidationException;

require_once __DIR__ . '/../../vendor/autoload.php';

// BURUK: catch kosong "menelan" masalah. Program jalan terus seolah tidak terjadi apa-apa.
$saved = 0;
foreach (['Hemat air #air', str_repeat('a', 2300), 'Hemat listrik #listrik'] as $body) {
    try {
        new Caption('Kampanye', $body);
        $saved++;
    } catch (ValidationException) {
        // diam saja...
    }
}
echo "Versi buruk: $saved caption tersimpan, tanpa tahu satu gagal dan kenapa." . PHP_EOL;

// LEBIH BAIK: tangani dengan sadar, catat alasannya, beri tahu pengguna.
$saved = 0;
$problems = [];
foreach (['Hemat air #air', str_repeat('a', 2300), 'Hemat listrik #listrik'] as $index => $body) {
    try {
        new Caption('Kampanye', $body);
        $saved++;
    } catch (ValidationException $e) {
        $problems[] = 'Caption ke-' . ($index + 1) . ': ' . $e->getMessage();
    }
}
echo "Versi baik: $saved caption tersimpan." . PHP_EOL;
foreach ($problems as $problem) {
    echo "   - $problem" . PHP_EOL;
}
