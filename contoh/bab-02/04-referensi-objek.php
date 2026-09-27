<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

$original = new Draft(id: 1, title: 'Energi surya', body: 'Isi draf.', type: 'article');

$b = $original;          // BUKAN salinan: $b dan $original menunjuk objek yang sama
$b->title = 'Diubah lewat $b';
echo $original->title . PHP_EOL;  // Diubah lewat $b

$copy = clone $original; // salinan baru yang terpisah
$copy->title = 'Hanya salinan yang berubah';
echo $original->title . PHP_EOL;  // Diubah lewat $b
echo $copy->title . PHP_EOL;      // Hanya salinan yang berubah
