<?php

declare(strict_types=1);

use KurasiAI\Ai\FakeAiProvider;
use KurasiAI\Domain\Content\Caption;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\Draft;
use KurasiAI\Domain\PromptTemplate;
use KurasiAI\Domain\Role;
use KurasiAI\Domain\User;
use KurasiAI\Service\DraftGenerator;
use KurasiAI\Service\ReviewService;
use KurasiAI\Support\FixedClock;
use KurasiAI\Support\IdSequence;
use KurasiAI\Support\IndonesianDate;

require_once __DIR__ . '/../vendor/autoload.php';

// Jam buatan: mulai Senin, 5 Oktober 2026 pukul 09.00 WIB, dan hanya maju kalau disuruh.
$clock = FixedClock::at('2026-10-05 09:00');
$generator = new DraftGenerator(new FakeAiProvider(), new IdSequence(), $clock);
$review = new ReviewService($clock);

$rina = new User(1, 'Rina', 'rina@kurasiai.test', Role::Writer);
$sari = new User(2, 'Sari', 'sari@kurasiai.test', Role::Writer);
$budi = new User(3, 'Budi', 'budi@kurasiai.test', Role::Editor);
$names = [$rina->id => $rina->name, $sari->id => $sari->name, $budi->id => $budi->name];

$template = new PromptTemplate('Caption kampanye', 'Buat caption Instagram tentang {{topik}}.', ContentType::Caption, $clock->now());

// --- Linimasa satu draf ---
$draft = $generator->generate($rina, $template, ['topik' => 'hemat air']);

$clock->advance(new DateInterval('PT15M'));                 // 09.15
$review->submit($draft, $rina);

$clock->advance(new DateInterval('PT1H25M'));               // 10.40
echo 'Menunggu review: ' . IndonesianDate::duration($review->waitingTime($draft)) . PHP_EOL;
$review->reject($draft, $budi, 'Tambahkan satu fakta tentang air.');

$clock->advance(new DateInterval('PT20M'));                 // 11.00
$review->revise($draft, $rina, new Caption('Hemat air', 'Keran bocor bisa membuang 20 liter air sehari. #hematair'));

$clock->advance(new DateInterval('PT22H'));                 // besok 09.00
$review->submit($draft, $rina);

// Draf kedua milik Sari masuk antrean 30 menit kemudian.
$clock->advance(new DateInterval('PT30M'));                 // 09.30
$other = $generator->generate($sari, $template, ['topik' => 'hemat listrik']);
$review->submit($other, $sari);

echo 'Antrean Budi   : ' . implode(', ', array_map(
    fn (Draft $d): string => "#{$d->id} (menunggu " . IndonesianDate::duration($review->waitingTime($d)) . ')',
    $review->reviewQueue($budi, [$other, $draft]),
)) . PHP_EOL;

$clock->advance(new DateInterval('PT45M'));                 // 10.15
$review->approve($draft, $budi);

$clock->advance(new DateInterval('PT2H'));                  // 12.15
$review->publish($draft, $rina);

// --- Riwayat ---
echo PHP_EOL . "Riwayat draf #{$draft->id} \"{$draft->title()}\"" . PHP_EOL;
echo '  Dibuat   ' . IndonesianDate::format($draft->createdAt()) . PHP_EOL;

foreach ($draft->history() as $change) {
    $line = sprintf(
        '  %-8s %s  %s -> %s',
        $names[$change->actorId],
        IndonesianDate::format($change->at),
        $change->from->label(),
        $change->to->label(),
    );

    echo $line . ($change->note !== null ? "  (\"$change->note\")" : '') . PHP_EOL;
}

// Tiga jam setelah terbit.
$clock->advance(new DateInterval('PT3H'));
$publishedAt = $draft->publishedAt();

if ($publishedAt !== null) {
    echo PHP_EOL . 'Terbit ' . IndonesianDate::relative($publishedAt, $clock->now())
        . ', total dari dibuat sampai terbit: '
        . IndonesianDate::duration($draft->createdAt()->diff($publishedAt)) . PHP_EOL;
}
