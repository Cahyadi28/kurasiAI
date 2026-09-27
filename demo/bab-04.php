<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$draft = new Draft(
    id: 1,
    title: 'Energi surya',
    body: 'Energi surya adalah sumber energi yang bersih.',
    type: 'article',
    prompt: new Prompt('Tulis artikel singkat tentang energi surya untuk pelajar SMA.'),
);

// Mencetak status saat ini dengan label yang ramah dibaca.
function show(string $step, Draft $draft): void
{
    echo str_pad($step, 28) . "-> {$draft->status()->label()}" . PHP_EOL;
}

echo 'Siklus hidup satu draf:' . PHP_EOL;
show('Dibuat', $draft);

$draft->submit();
show('Dikirim ke review', $draft);

$draft->reject('Tambahkan contoh nyata di rumah.');
show('Ditolak editor', $draft);
echo "   Catatan: {$draft->rejectionNote()}" . PHP_EOL;

$draft->revise(
    title: 'Energi surya di rumah',
    body: 'Panel surya di atap bisa mengurangi tagihan listrik bulanan.',
);
show('Direvisi penulis', $draft);

$draft->submit();
show('Dikirim ulang', $draft);

$draft->approve();
show('Disetujui editor', $draft);

$draft->publish();
show('Diterbitkan', $draft);

echo PHP_EOL . 'Mencoba melanggar aturan:' . PHP_EOL;

$second = new Draft(
    id: 2,
    title: 'Hemat air',
    body: 'Matikan keran saat menyikat gigi.',
    type: 'caption',
    prompt: new Prompt('Buat caption tentang hemat air.'),
);

try {
    $second->publish();
} catch (DomainException $e) {
    echo '   Ditolak: ' . $e->getMessage() . PHP_EOL;
}

try {
    $draft->revise(title: 'Ubah setelah terbit', body: 'Tidak boleh.');
} catch (DomainException $e) {
    echo '   Ditolak: ' . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . 'Membaca status dari teks (misalnya dari form):' . PHP_EOL;

foreach (['in_review', 'publised'] as $text) {
    $status = DraftStatus::tryFrom($text);
    echo "   '$text' -> " . ($status?->label() ?? 'tidak dikenal') . PHP_EOL;
}
