<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

$caption = new Caption('Hemat air', 'Matikan keran. #hematair');
echo $caption->metaLabel() . PHP_EOL;    // 1 tagar

var_dump($caption instanceof Caption);   // bool(true)
var_dump($caption instanceof Content);   // bool(true): Caption ADALAH SEBUAH Content

$content = new Content('Judul', 'Isi'); // Error: abstract class tidak bisa dibuat langsung
