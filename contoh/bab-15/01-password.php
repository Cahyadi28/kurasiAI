<?php

declare(strict_types=1);

// Contoh password; di aplikasi, password datang dari form, tidak pernah ditulis di kode.
$password = 'contoh-password-latihan';

// 1. Hash selalu berbeda walaupun passwordnya sama, karena setiap hash memakai salt acak.
$first = password_hash($password, PASSWORD_DEFAULT);
$second = password_hash($password, PASSWORD_DEFAULT);

echo 'Hash 1   : ' . $first . PHP_EOL;
echo 'Hash 2   : ' . $second . PHP_EOL;
echo 'Sama?    : ' . var_export($first === $second, true) . PHP_EOL;

// 2. Memeriksa password: password_verify() membaca algoritma, cost, dan salt dari hash itu sendiri.
echo 'Benar    : ' . var_export(password_verify($password, $first), true) . PHP_EOL;
echo 'Salah    : ' . var_export(password_verify('tebakan', $first), true) . PHP_EOL;

// 3. Hash lama dengan cost rendah: perlu diperbarui saat pengguna login berikutnya.
$old = password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]);
echo 'Info lama: ' . json_encode(password_get_info($old)['options']) . PHP_EOL;
echo 'Perlu diperbarui? ' . var_export(password_needs_rehash($old, PASSWORD_DEFAULT), true) . PHP_EOL;
