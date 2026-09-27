<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

$draft = new Draft(
    id: 1,
    title: 'Energi surya',
    body: 'Isi draf.',
    type: 'article',
    prompt: new Prompt('Tulis artikel tentang energi surya.'),
);

echo $draft->title() . PHP_EOL; // boleh: membaca lewat method

$draft->title = '';             // Error: property private tidak bisa diakses dari luar
