<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

// Dari luar class: NamaClass::NAMA_KONSTANTA
echo 'Artikel minimal ' . Article::MIN_BODY_LENGTH . ' karakter' . PHP_EOL;
echo 'Caption maksimal ' . Caption::MAX_HASHTAGS . ' tagar' . PHP_EOL;
echo 'Kecepatan baca: ' . Article::WORDS_PER_MINUTE . ' kata per menit' . PHP_EOL;
echo 'Kecepatan bicara: ' . VideoScript::WORDS_PER_MINUTE . ' kata per menit' . PHP_EOL;
