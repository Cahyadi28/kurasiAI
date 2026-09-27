<?php

declare(strict_types=1);

namespace KurasiAI\Http;

/**
 * Satu permintaan dari browser, dibungkus menjadi objek yang tidak bisa diubah.
 * Kode lain membaca Request, bukan $_GET, $_POST, atau $_SERVER secara langsung.
 */
final class Request
{
    /**
     * @param array<mixed> $query isi alamat setelah tanda tanya ($_GET)
     * @param array<mixed> $post  isi form yang dikirim ($_POST)
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        private readonly array $query = [],
        private readonly array $post = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = is_string($uri) ? parse_url($uri, PHP_URL_PATH) : null;

        return new self(
            method: is_string($method) ? strtoupper($method) : 'GET',
            path: is_string($path) && $path !== '' ? $path : '/',
            query: $_GET,
            post: $_POST,
        );
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    /**
     * Nilai teks dari alamat, misalnya ?sort=title. Nilai yang bukan teks dianggap tidak ada.
     */
    public function query(string $key): ?string
    {
        $value = $this->query[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * Seluruh isi form. Dibaca lewat objek form (Bab 14), tidak langsung dipakai.
     *
     * @return array<mixed>
     */
    public function post(): array
    {
        return $this->post;
    }
}
