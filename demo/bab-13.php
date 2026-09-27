<?php

declare(strict_types=1);

use KurasiAI\Ai\FakeAiProvider;
use KurasiAI\Domain\Content\Caption;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\Draft;
use KurasiAI\Domain\PromptTemplate;
use KurasiAI\Exception\KurasiException;
use KurasiAI\Exception\NotFoundException;
use KurasiAI\Exception\ValidationException;
use KurasiAI\Persistence\Database;
use KurasiAI\Persistence\DraftSort;
use KurasiAI\Persistence\PromptTemplateRepository;
use KurasiAI\Persistence\SortDirection;
use KurasiAI\Persistence\SqliteDraftRepository;
use KurasiAI\Persistence\UsageLogRepository;
use KurasiAI\Persistence\UserRepository;
use KurasiAI\Service\DraftGenerator;
use KurasiAI\Service\ReviewService;
use KurasiAI\Support\Env;
use KurasiAI\Support\IndonesianDate;
use KurasiAI\Support\SystemClock;

require_once __DIR__ . '/../vendor/autoload.php';

// Jalankan dulu: php bin/setup-db.php --fresh && php bin/seed.php
$root = dirname(__DIR__);

try {
    $env = Env::fromFile($root . '/.env');
    $db = Database::fromEnv($env, $root);
} catch (KurasiException $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

// Merakit objek: semua kebutuhan dikirim lewat constructor (Bab 8).
$timezone = $env->timezone();
$clock = new SystemClock($timezone);
$users = new UserRepository($db);
$templates = new PromptTemplateRepository($db, $timezone);
$drafts = new SqliteDraftRepository($db, $timezone);
$generator = new DraftGenerator(new FakeAiProvider(), $drafts, $clock);
$review = new ReviewService($clock, $drafts);

$rina = $users->findByEmail('rina@kurasiai.test');
$sari = $users->findByEmail('sari@kurasiai.test');
$budi = $users->findByEmail('budi@kurasiai.test');
$caption = current(array_filter(
    $templates->all(),
    fn (PromptTemplate $template): bool => $template->targetType === ContentType::Caption,
));

if ($rina === null || $sari === null || $budi === null || $caption === false) {
    fwrite(STDERR, 'Data contoh belum ada. Jalankan dulu: php bin/seed.php' . PHP_EOL);
    exit(1);
}

// 1. Membuat draf dan menjalankan alur review. Setiap langkah langsung tersimpan.
$draft = $generator->generate($rina, $caption, ['topik' => 'hemat air']);
$review->submit($draft, $rina);
$review->reject($draft, $budi, 'Tambahkan satu fakta tentang air.');
$review->revise($draft, $rina, new Caption('Hemat air', 'Keran bocor bisa membuang 20 liter air sehari. #hematair'));
$review->submit($draft, $rina);
$review->approve($draft, $budi);

$generator->generate($rina, $caption, ['topik' => 'kompos rumah tangga']);
$other = $generator->generate($sari, $caption, ['topik' => 'hemat listrik']);
$review->submit($other, $sari);

// 2. Seperti permintaan berikutnya: objek repository baru, hanya database yang sama.
$loaded = (new SqliteDraftRepository($db, $timezone))->get($draft->id);

echo "Draf #{$loaded->id} \"{$loaded->title()}\" dimuat ulang dari database, status: {$loaded->status()->label()}" . PHP_EOL;

foreach ($loaded->history() as $change) {
    printf(
        '  %-5s %s  %s -> %s%s' . PHP_EOL,
        $users->get($change->actorId)->name,
        IndonesianDate::format($change->at),
        $change->from->label(),
        $change->to->label(),
        $change->note === null ? '' : "  (\"$change->note\")",
    );
}

// 3. Daftar draf dengan urutan dari "input pengguna", misalnya ?sort=title&dir=asc.
$describe = fn (Draft $d): string => "#{$d->id} {$d->title()} [{$d->status()->label()}]";

foreach ([['title', 'asc'], ['password_hash', 'asc']] as [$sortInput, $directionInput]) {
    try {
        $list = $drafts->listByAuthor($rina->id, DraftSort::fromInput($sortInput), SortDirection::fromInput($directionInput));
        echo PHP_EOL . "Draf Rina (sort=$sortInput): " . implode(', ', array_map($describe, $list)) . PHP_EOL;
    } catch (ValidationException $e) {
        echo PHP_EOL . "Urutan ditolak: {$e->getMessage()}" . PHP_EOL;
    }
}

// 4. Antrean review Budi, dibaca langsung dari database.
echo PHP_EOL . 'Antrean Budi: ' . implode(', ', array_map($describe, $review->reviewQueue($budi))) . PHP_EOL;

// 5. Log pemakaian AI per pengguna.
echo PHP_EOL . 'Pemakaian AI:' . PHP_EOL;

foreach ((new UsageLogRepository($db))->totalsPerUser() as $total) {
    printf('  %-5s %d kali generate, %d token' . PHP_EOL, $total->userName, $total->generations, $total->usage->total());
}

// 6. Mencari draf yang tidak ada.
try {
    $drafts->get(9999);
} catch (NotFoundException $e) {
    echo PHP_EOL . $e->getMessage() . PHP_EOL;
}
