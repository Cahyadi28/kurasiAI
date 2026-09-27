<?php

declare(strict_types=1);

namespace KurasiAI\Domain;

/**
 * Peran pengguna KurasiAI.
 */
enum Role: string
{
    case Writer = 'writer';
    case Editor = 'editor';

    public function label(): string
    {
        return match ($this) {
            self::Writer => 'Penulis',
            self::Editor => 'Editor',
        };
    }

    /**
     * Apakah peran ini boleh menyetujui atau menolak draf?
     */
    public function canReview(): bool
    {
        return $this === self::Editor;
    }
}
