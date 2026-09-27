<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

// Semua status yang ada:
foreach (DraftStatus::cases() as $status) {
    echo "{$status->value} = {$status->label()}" . PHP_EOL;
}

// Dari teks ke enum:
var_dump(DraftStatus::from('in_review') === DraftStatus::InReview); // bool(true)
var_dump(DraftStatus::tryFrom('publised'));                         // NULL

DraftStatus::from('publised');                                      // ValueError
