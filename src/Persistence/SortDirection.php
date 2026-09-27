<?php

declare(strict_types=1);

namespace KurasiAI\Persistence;

use KurasiAI\Exception\ValidationException;

/**
 * Arah urutan: naik (A–Z, lama ke baru) atau turun.
 */
enum SortDirection: string
{
    case Asc = 'asc';
    case Desc = 'desc';

    public static function fromInput(?string $input): self
    {
        if ($input === null || $input === '') {
            return self::Desc;
        }

        return self::tryFrom(strtolower($input))
            ?? throw new ValidationException("Arah urutan \"$input\" tidak dikenal. Pilihan: asc, desc.");
    }

    public function sql(): string
    {
        return match ($this) {
            self::Asc => 'ASC',
            self::Desc => 'DESC',
        };
    }
}
