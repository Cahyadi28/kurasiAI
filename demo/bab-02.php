<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$drafts = [
    new Draft(
        id: 1,
        title: 'Energi surya',
        body: 'Energi surya adalah sumber energi yang bersih dan bisa dipakai di rumah.',
        type: 'article',
    ),
    new Draft(
        id: 2,
        title: 'Belajar PHP',
        body: 'Adegan pembuka: sapa penonton lalu jelaskan kenapa PHP masih banyak dipakai.',
        type: 'video_script',
        status: 'in_review',
    ),
    new Draft(
        id: 3,
        title: 'Hemat air',
        body: 'Matikan keran saat menyikat gigi. #hematair',
        type: 'caption',
        status: 'approved',
    ),
];

echo 'Daftar draf (' . count($drafts) . ')' . PHP_EOL . PHP_EOL;

foreach ($drafts as $draft) {
    echo "#{$draft->id} {$draft->title}" . PHP_EOL;
    echo "   {$draft->typeLabel()} | {$draft->statusLabel()} | {$draft->wordCount()} kata" . PHP_EOL;
    echo "   {$draft->summary()}" . PHP_EOL . PHP_EOL;
}

// Yang BELUM terselesaikan di bab ini: objek masih mau menerima data yang salah.
$invalid = new Draft(
    id: 4,
    title: '',
    body: 'Draf tanpa judul, jenis tidak dikenal, dan status salah ketik.',
    type: 'podcast',
    status: 'publised',
);

echo "#{$invalid->id} (judul: '{$invalid->title}') "
    . "{$invalid->typeLabel()} | {$invalid->statusLabel()}" . PHP_EOL;
