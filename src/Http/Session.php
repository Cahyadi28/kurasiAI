<?php

declare(strict_types=1);

namespace KurasiAI\Http;

/**
 * Pembungkus data session. Di aplikasi datanya adalah $_SESSION; di tes cukup array biasa.
 * Login dan pergantian nomor session (session_regenerate_id) menyusul di Bab 15.
 */
final class Session
{
    private const FLASH_KEY = '_flash';

    /** @var array<mixed> */
    private array $data;

    /**
     * @param array<mixed> $data disimpan sebagai referensi: perubahan langsung masuk ke array aslinya
     */
    public function __construct(array &$data)
    {
        $this->data = &$data;
    }

    /**
     * Memulai session PHP dengan cookie yang lebih aman, lalu membungkus $_SESSION.
     */
    public static function start(): self
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_httponly' => true,   // cookie tidak bisa dibaca JavaScript
                'cookie_samesite' => 'Lax',  // tidak ikut terkirim pada POST dari situs lain
                'use_strict_mode' => true,   // tolak nomor session yang tidak dibuat server
                'use_only_cookies' => true,  // nomor session tidak boleh lewat alamat URL
            ]);
        }

        return new self($_SESSION);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    /**
     * Pesan satu kali tampil, misalnya "Template tersimpan." setelah redirect.
     */
    public function flash(string $message): void
    {
        $messages = $this->takeFlashes();
        $messages[] = $message;
        $this->data[self::FLASH_KEY] = $messages;
    }

    /**
     * Mengambil semua pesan flash sekaligus menghapusnya.
     *
     * @return list<string>
     */
    public function takeFlashes(): array
    {
        $messages = $this->data[self::FLASH_KEY] ?? [];
        unset($this->data[self::FLASH_KEY]);

        return is_array($messages) ? array_values(array_filter($messages, 'is_string')) : [];
    }
}
