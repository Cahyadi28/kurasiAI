<?php

declare(strict_types=1);

// Tenggat review = 2 hari setelah draf dibuat.

// Versi DateTime (mutable): modify() MENGUBAH objek aslinya.
$createdAt = new DateTime('2026-10-05 09:00');
$deadline = $createdAt->modify('+2 days');

echo 'DateTime          | dibuat: ' . $createdAt->format('d M H:i')
    . ' | tenggat: ' . $deadline->format('d M H:i') . PHP_EOL;

// Versi DateTimeImmutable: modify() MENGEMBALIKAN objek baru, aslinya tetap.
$createdAt = new DateTimeImmutable('2026-10-05 09:00');
$deadline = $createdAt->modify('+2 days');

echo 'DateTimeImmutable | dibuat: ' . $createdAt->format('d M H:i')
    . ' | tenggat: ' . $deadline->format('d M H:i') . PHP_EOL;
