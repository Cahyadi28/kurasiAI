<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

// 1. Draf yang sah
$draft = new Draft(
    id: 1,
    title: 'Energi surya',
    body: 'Energi surya adalah sumber energi yang bersih dan bisa dipakai di rumah.',
    type: 'article',
    prompt: new Prompt('Tulis artikel singkat tentang energi surya untuk pelajar SMA.'),
    usage: new TokenUsage(input: 14, output: 42),
);

echo "#{$draft->id} {$draft->title()} ({$draft->statusLabel()})" . PHP_EOL;
echo "   Prompt: {$draft->prompt->text}" . PHP_EOL;
echo "   Token : {$draft->usage?->total()}" . PHP_EOL . PHP_EOL;

// 2. Revisi lewat method, bukan mengubah property langsung
$draft->revise(
    title: 'Energi surya untuk rumah',
    body: 'Panel surya di atap bisa mengurangi tagihan listrik bulanan.',
);

echo "Setelah revisi: {$draft->title()}" . PHP_EOL;
echo "   {$draft->body()}" . PHP_EOL . PHP_EOL;

// 3. Data yang salah ditolak sejak awal
echo 'Mencoba data yang salah:' . PHP_EOL;

try {
    new Draft(id: 2, title: '   ', body: 'Isi.', type: 'article', prompt: new Prompt('Tulis sesuatu.'));
} catch (InvalidArgumentException $e) {
    echo '   Ditolak: ' . $e->getMessage() . PHP_EOL;
}

try {
    new Prompt('');
} catch (InvalidArgumentException $e) {
    echo '   Ditolak: ' . $e->getMessage() . PHP_EOL;
}

try {
    new TokenUsage(input: -5, output: 10);
} catch (InvalidArgumentException $e) {
    echo '   Ditolak: ' . $e->getMessage() . PHP_EOL;
}

try {
    $draft->revise(title: 'Judul baru', body: '');
} catch (InvalidArgumentException $e) {
    echo '   Ditolak: ' . $e->getMessage() . PHP_EOL;
    echo "   Judul tetap: {$draft->title()}" . PHP_EOL;
}
