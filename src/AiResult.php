<?php

declare(strict_types=1);

/**
 * Value object: hasil satu permintaan ke AI. Semua provider wajib mengembalikan bentuk ini.
 */
class AiResult
{
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly TokenUsage $usage,
        public readonly string $providerName,
    ) {
    }
}
