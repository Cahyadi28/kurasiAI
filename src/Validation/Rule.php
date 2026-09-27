<?php

declare(strict_types=1);

namespace KurasiAI\Validation;

/**
 * Satu aturan validasi. Setiap attribute validasi (#[Required], #[MaxLength], ...) menerapkannya.
 */
interface Rule
{
    /**
     * Pesan kesalahan untuk pengguna, atau null bila nilainya lolos.
     */
    public function check(mixed $value): ?string;
}
