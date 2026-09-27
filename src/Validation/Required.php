<?php

declare(strict_types=1);

namespace KurasiAI\Validation;

use Attribute;

/**
 * Kolom wajib diisi: tidak boleh kosong atau hanya berisi spasi.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Required implements Rule
{
    public function __construct(public readonly string $message = 'Wajib diisi.')
    {
    }

    public function check(mixed $value): ?string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return $this->message;
        }

        return null;
    }
}
