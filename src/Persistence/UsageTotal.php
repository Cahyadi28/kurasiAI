<?php

declare(strict_types=1);

namespace KurasiAI\Persistence;

use KurasiAI\Domain\TokenUsage;

/**
 * Ringkasan pemakaian AI satu pengguna: berapa kali generate dan total tokennya.
 */
final class UsageTotal
{
    public function __construct(
        public readonly int $userId,
        public readonly string $userName,
        public readonly int $generations,
        public readonly TokenUsage $usage,
    ) {
    }
}
