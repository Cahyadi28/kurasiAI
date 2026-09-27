<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$drafts = [
    new Draft(
        id: 1,
        content: new Article(
            'Energi surya di rumah',
            'Panel surya di atap rumah mengubah cahaya matahari menjadi listrik. '
            . 'Untuk rumah tangga, manfaat terbesarnya adalah tagihan listrik bulanan yang lebih kecil. '
            . 'Sebelum memasang, periksa arah atap, luas area yang terkena sinar matahari, '
            . 'dan kebutuhan listrik harian keluarga. Mulailah dari sistem kecil, lalu tambah bertahap.',
        ),
        prompt: new Prompt('Tulis artikel singkat tentang energi surya di rumah.'),
    ),
    new Draft(
        id: 2,
        content: new VideoScript(
            'Belajar PHP dalam 1 menit',
            "Pembuka: sapa penonton dan tanyakan apakah mereka pernah membuat website.\n"
            . "Isi: tunjukkan satu baris kode PHP yang mencetak tulisan di browser.\n"
            . 'Penutup: ajak penonton mencoba sendiri dan menulis komentar.',
        ),
        prompt: new Prompt('Buat naskah video pendek tentang PHP.'),
    ),
    new Draft(
        id: 3,
        content: new Caption(
            'Hemat air',
            'Matikan keran saat menyikat gigi. Kebiasaan kecil, dampak besar. #hematair #bumi',
        ),
        prompt: new Prompt('Buat caption tentang hemat air.'),
    ),
];

foreach ($drafts as $draft) {
    $content = $draft->content();

    // Satu baris yang sama untuk ketiga jenis konten.
    // Setiap object menjawab metaLabel() dengan caranya sendiri.
    echo "#{$draft->id} {$draft->title()}" . PHP_EOL;
    echo "   {$content->type()->label()} | {$content->wordCount()} kata | {$content->metaLabel()}" . PHP_EOL;
}

echo PHP_EOL . 'Mencoba data yang salah:' . PHP_EOL;

try {
    new Article('Terlalu pendek', 'Artikel ini hanya satu kalimat.');
} catch (InvalidArgumentException $e) {
    echo '   Ditolak: ' . $e->getMessage() . PHP_EOL;
}

try {
    new Caption('Terlalu panjang', str_repeat('Hemat air itu penting. ', 100));
} catch (InvalidArgumentException $e) {
    echo '   Ditolak: ' . $e->getMessage() . PHP_EOL;
}

try {
    $drafts[0]->revise(new Caption('Energi surya', 'Pasang panel surya! #energi'));
} catch (InvalidArgumentException $e) {
    echo '   Ditolak: ' . $e->getMessage() . PHP_EOL;
}
