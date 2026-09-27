<?php

// CONTOH SENGAJA SALAH: satu-satunya berkas tanpa declare(strict_types=1).
// Tujuannya memperlihatkan PHP diam-diam mengubah tipe data.

function total_tokens(int $input, int $output): int
{
    return $input + $output;
}

echo total_tokens(120, 80) . PHP_EOL;     // 200
echo total_tokens('120', 80) . PHP_EOL;   // 200, string '120' diubah jadi int
