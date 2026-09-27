<?php

declare(strict_types=1);

use KurasiAI\Domain\Content\Caption;
use KurasiAI\Exception\ValidationException;

require_once __DIR__ . '/../../vendor/autoload.php';

function describe(Throwable $e): void
{
    $kind = $e instanceof Error ? 'Error    ' : 'Exception';
    echo "$kind | " . $e::class . ' | ' . $e->getMessage() . PHP_EOL;
}

// 1. Exception: masalah yang kita antisipasi dan tangani.
try {
    new Caption('Hemat air', str_repeat('a', 2300));
} catch (ValidationException $e) {
    describe($e);
}

// 2. Error: kesalahan penulisan program, seharusnya diperbaiki di kodenya.
try {
    new Caption('Hemat air', 12345); // sengaja salah tipe
} catch (TypeError $e) {
    describe($e);
}

try {
    echo match ('podcast') {
        'article' => 'Artikel',
        'caption' => 'Caption',
    };
} catch (UnhandledMatchError $e) {
    describe($e);
}
