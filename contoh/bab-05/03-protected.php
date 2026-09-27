<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

$caption = new Caption('Hemat air', 'Matikan keran. #hematair #bumi');

// hashtags() public, dan di dalamnya memakai words() milik Content (protected).
print_r($caption->hashtags());

// Dari luar keluarga class, words() tidak boleh dipanggil.
$caption->words(); // Error: method protected
