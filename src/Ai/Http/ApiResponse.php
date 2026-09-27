<?php

declare(strict_types=1);

namespace KurasiAI\Ai\Http;

/**
 * Jawaban mentah dari sebuah API: kode status, isi, dan header (nama header huruf kecil).
 */
final class ApiResponse
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly array $headers = [],
    ) {
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
