<?php

declare(strict_types=1);

// Dengan strict_types: tipe yang salah langsung ditolak.

function total_tokens(int $input, int $output): int
{
    return $input + $output;
}

echo total_tokens(120, 80) . PHP_EOL;     // 200
echo total_tokens('120', 80) . PHP_EOL;   // TypeError: harus int, string diberikan
