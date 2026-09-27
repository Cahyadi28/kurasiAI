<?php

declare(strict_types=1);

namespace KurasiAI\Validation;

use Attribute;
use BackedEnum;

/**
 * Nilai harus salah satu nilai backed enum, misalnya #[OneOf(ContentType::class)].
 * Nilai kosong dibiarkan lolos; pakai #[Required] bila kolom wajib diisi.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class OneOf implements Rule
{
    /**
     * @param class-string<BackedEnum> $enum
     */
    public function __construct(public readonly string $enum)
    {
    }

    public function check(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ((!is_string($value) && !is_int($value)) || $this->enum::tryFrom($value) === null) {
            return 'Pilihan tidak dikenal.';
        }

        return null;
    }
}
