<?php

declare(strict_types=1);

// Kenapa AuthService tetap menjalankan password_hash() saat email tidak terdaftar?
// Bandingkan lama dua cara menolak login.

$storedHash = password_hash('password-milik-rina', PASSWORD_DEFAULT);

function measure(callable $work): float
{
    $start = hrtime(true);
    $work();

    return (hrtime(true) - $start) / 1_000_000; // nanodetik -> milidetik
}

// Email terdaftar: password diperiksa dengan password_verify().
$registered = measure(fn () => password_verify('tebakan', $storedHash));

// Email tidak terdaftar, versi naif: langsung ditolak.
$naive = measure(fn () => null);

// Email tidak terdaftar, versi KurasiAI: tetap mengerjakan hash dengan biaya yang sama.
$kurasi = measure(fn () => password_hash('tebakan', PASSWORD_DEFAULT));

printf('Email terdaftar, password salah : %6.1f ms' . PHP_EOL, $registered);
printf('Tidak terdaftar, langsung tolak : %6.1f ms   <- terlalu cepat, ketahuan' . PHP_EOL, $naive);
printf('Tidak terdaftar, versi KurasiAI : %6.1f ms' . PHP_EOL, $kurasi);
