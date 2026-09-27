<?php

declare(strict_types=1);

namespace KurasiAI\Http;

/**
 * Jawaban untuk browser: kode status, header, dan isi.
 * Controller MENGEMBALIKAN Response; baru dikirim dengan send() di front controller.
 */
final class Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly string $body = '',
        public readonly int $status = 200,
        public readonly array $headers = ['Content-Type' => 'text/html; charset=utf-8'],
    ) {
    }

    /**
     * Pindah ke halaman lain. 303 (See Other) membuat browser melanjutkan dengan GET (pola PRG, Bab 14).
     */
    public static function redirect(string $location): self
    {
        return new self('', 303, ['Location' => $location]);
    }

    public function withHeader(string $name, string $value): self
    {
        return new self($this->body, $this->status, [...$this->headers, $name => $value]);
    }

    public function header(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Satu-satunya tempat header dan isi dikirim ke browser.
     */
    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        echo $this->body;
    }
}
