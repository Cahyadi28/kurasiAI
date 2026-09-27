<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

$prompt = new Prompt('Tulis artikel tentang energi surya.');

echo $prompt->text . PHP_EOL;   // boleh: membaca property readonly

$prompt->text = 'Prompt lain';  // Error: property readonly tidak bisa diubah
