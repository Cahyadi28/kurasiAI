<?php

declare(strict_types=1);

// Satu momen yang sama: draf terbit pukul 09.00 WIB.
$publishedAt = new DateTimeImmutable('2026-10-05 09:00', new DateTimeZone('Asia/Jakarta'));

foreach (['UTC', 'Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura'] as $zone) {
    $local = $publishedAt->setTimezone(new DateTimeZone($zone));

    echo str_pad($zone, 14) . str_pad($local->format('H:i T'), 11) . $local->format(DATE_ATOM) . PHP_EOL;
}

// Beda tampilan, tetapi momennya sama.
$inUtc = $publishedAt->setTimezone(new DateTimeZone('UTC'));
var_dump($inUtc == $publishedAt);

// Tanpa zona waktu, PHP memakai zona bawaan server (date.timezone di php.ini).
echo 'Zona bawaan server: ' . date_default_timezone_get() . PHP_EOL;
