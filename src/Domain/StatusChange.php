<?php

declare(strict_types=1);

namespace KurasiAI\Domain;

use DateTimeImmutable;

/**
 * Satu baris riwayat: siapa mengubah status draf, dari apa ke apa, dan kapan.
 * Riwayat tidak pernah diubah, jadi semua property-nya readonly.
 */
final class StatusChange
{
    public function __construct(
        public readonly DraftStatus $from,
        public readonly DraftStatus $to,
        public readonly int $actorId,
        public readonly DateTimeImmutable $at,
        public readonly ?string $note = null,
    ) {
    }
}
