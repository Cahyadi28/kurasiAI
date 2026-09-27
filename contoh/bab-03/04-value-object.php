<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

$firstRequest = new TokenUsage(input: 14, output: 42);
$secondRequest = new TokenUsage(input: 10, output: 30);

$todayTotal = $firstRequest->add($secondRequest);

echo $todayTotal->total() . PHP_EOL;    // 96
echo $firstRequest->total() . PHP_EOL;  // 56, tidak berubah

// Dua object berbeda dengan isi yang sama:
$a = new TokenUsage(input: 14, output: 42);
$b = new TokenUsage(input: 14, output: 42);

var_dump($a === $b);      // false: bukan object yang sama
var_dump($a->equals($b)); // true: isinya sama
