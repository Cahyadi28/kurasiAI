<?php

declare(strict_types=1);

namespace KurasiAI\Exception;

use KurasiAI\Domain\DraftStatus;

/**
 * Perpindahan status yang tidak sah. Menyimpan status asal dan tujuannya,
 * sehingga kode yang menangkap bisa memakai informasi itu.
 */
final class InvalidTransitionException extends InvalidStateException
{
    public function __construct(
        public readonly DraftStatus $from,
        public readonly DraftStatus $to,
    ) {
        parent::__construct("Draf berstatus {$from->label()} tidak bisa diubah menjadi {$to->label()}.");
    }
}
