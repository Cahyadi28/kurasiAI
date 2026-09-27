<?php

declare(strict_types=1);

$zone = new DateTimeZone('Asia/Jakarta');
$submittedAt = new DateTimeImmutable('2026-10-05 09:15', $zone);
$reviewedAt = new DateTimeImmutable('2026-10-06 11:20', $zone);

// 1. Membandingkan: operator biasa bekerja pada objek tanggal.
var_dump($reviewedAt > $submittedAt);

// 2. Selisih: diff() menghasilkan DateInterval.
$waited = $submittedAt->diff($reviewedAt);
echo $waited->format('Menunggu %a hari %h jam %i menit') . PHP_EOL;

// 3. Menambah: add() menerima DateInterval berformat ISO 8601 (P = periode, T = bagian jam).
echo 'Tenggat: ' . $submittedAt->add(new DateInterval('P2DT3H'))->format('d-m-Y H:i') . PHP_EOL;

// 4. Membaca input pengguna dengan format yang pasti.
function parseDate(string $input): ?DateTimeImmutable
{
    // Tanda ! mengisi bagian yang tidak disebut (jam, menit) dengan nol.
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $input, new DateTimeZone('Asia/Jakarta'));
    $errors = DateTimeImmutable::getLastErrors();

    // Tanggal seperti 2026-02-30 tidak membuat hasilnya false: PHP menggesernya ke 2 Maret
    // dan hanya mencatat peringatan. Karena itu peringatan juga harus diperiksa.
    if ($date === false || ($errors !== false && $errors['warning_count'] > 0)) {
        return null;
    }

    return $date;
}

foreach (['2026-10-05', '05/10/2026', '2026-02-30'] as $input) {
    $date = parseDate($input);
    echo str_pad($input, 12) . '-> ' . ($date?->format('d M Y H:i') ?? 'tidak valid') . PHP_EOL;
}
