<?php

declare(strict_types=1);

namespace KurasiAI\Http;

use KurasiAI\Exception\KurasiException;
use RuntimeException;

/**
 * Permintaan tidak bisa dilayani pada tingkat HTTP, misalnya alamat tidak dikenal (404)
 * atau metode yang salah (405).
 */
final class HttpException extends RuntimeException implements KurasiException
{
    /**
     * @param array<string, string> $headers header tambahan untuk jawabannya, misalnya Allow
     */
    public function __construct(
        public readonly int $status,
        string $message,
        public readonly array $headers = [],
    ) {
        parent::__construct($message);
    }
}
