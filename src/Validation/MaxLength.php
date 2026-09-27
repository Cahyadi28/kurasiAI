<?php

declare(strict_types=1);

namespace KurasiAI\Validation;

use Attribute;

/**
 * Teks paling banyak $max karakter (dihitung dengan mb_strlen, jadi huruf beraksen tetap satu karakter).
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class MaxLength implements Rule
{
    public function __construct(public readonly int $max)
    {
    }

    public function check(mixed $value): ?string
    {
        if (is_string($value) && mb_strlen($value) > $this->max) {
            return "Maksimal {$this->max} karakter.";
        }

        return null;
    }
}
