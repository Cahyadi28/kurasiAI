<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

// 1. Merakit object: semua kebutuhan dibuat di sini, lalu dikirim ke yang membutuhkan.
$generator = new DraftGenerator(new FakeAiProvider(), new IdSequence());

$rina = new User(1, 'Rina', 'rina@kurasiai.test', Role::Writer);
$sari = new User(2, 'Sari', 'sari@kurasiai.test', Role::Writer);
$budi = new User(3, 'Budi', 'budi@kurasiai.test', Role::Editor);

$articleTemplate = new PromptTemplate(
    'Artikel edukasi',
    'Tulis artikel singkat tentang {{topik}} untuk {{pembaca}}.',
    ContentType::Article,
);
$captionTemplate = new PromptTemplate(
    'Caption kampanye',
    'Buat caption Instagram tentang {{topik}}.',
    ContentType::Caption,
);

// 2. Dua penulis dan satu editor bekerja.
$rinaDraft = $generator->generate($rina, $articleTemplate, ['topik' => 'energi surya', 'pembaca' => 'pelajar SMA']);
$budiDraft = $generator->generate($budi, $captionTemplate, ['topik' => 'hemat air']);

echo "#{$rinaDraft->id} {$rinaDraft->title()} - milik Rina" . PHP_EOL;
echo "#{$budiDraft->id} {$budiDraft->title()} - milik Budi" . PHP_EOL . PHP_EOL;

$rinaDraft->submit($rina);
echo "Rina mengirim #{$rinaDraft->id}: {$rinaDraft->status()->label()}" . PHP_EOL;

$rinaDraft->approve($budi);
echo "Budi (Editor) menyetujui #{$rinaDraft->id}: {$rinaDraft->status()->label()}" . PHP_EOL;

$rinaDraft->publish($rina);
echo "Rina menerbitkan #{$rinaDraft->id}: {$rinaDraft->status()->label()}" . PHP_EOL . PHP_EOL;

// 3. Percobaan yang melanggar aturan.
echo 'Mencoba melanggar aturan:' . PHP_EOL;

$budiDraft->submit($budi);

$attempts = [
    'Sari merevisi draf Budi' => fn () => $budiDraft->revise($sari, new Caption('Hemat air', 'Ubah! #air')),
    'Sari menyetujui draf Budi' => fn () => $budiDraft->approve($sari),
    'Budi menyetujui drafnya sendiri' => fn () => $budiDraft->approve($budi),
];

foreach ($attempts as $description => $attempt) {
    try {
        $attempt();
        echo "   $description: berhasil (seharusnya tidak!)" . PHP_EOL;
    } catch (DomainException $e) {
        echo "   $description: ditolak. {$e->getMessage()}" . PHP_EOL;
    }
}
