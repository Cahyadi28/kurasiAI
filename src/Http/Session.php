<?php

declare(strict_types=1);

namespace KurasiAI\Http;

/**
 * Pembungkus data session. Di aplikasi datanya adalah $_SESSION; di tes cukup array biasa.
 */
final class Session
{
    private const FLASH_KEY = '_flash';
    private const FLASH_ERROR_KEY = '_flash_error';

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
                'name' => 'kurasiai_session',
                'cookie_httponly' => true,       // cookie tidak bisa dibaca JavaScript
                'cookie_samesite' => 'Lax',      // tidak ikut terkirim pada POST dari situs lain
                'cookie_secure' => self::isHttps(), // lewat HTTPS, cookie hanya dikirim lewat HTTPS
                'use_strict_mode' => true,       // tolak nomor session yang tidak dibuat server
                'use_only_cookies' => true,      // nomor session tidak boleh lewat alamat URL
            ]);
        }

        return new self($_SESSION);
    }

    /**
     * Mengganti nomor session dan membuang nomor lama. Wajib dipanggil saat hak akses berubah
     * (login), supaya nomor yang mungkin sudah diketahui orang lain tidak lagi berlaku.
     */
    public function regenerate(): void
    {
        // Di tes (tanpa session PHP sungguhan) tidak ada nomor yang perlu diganti.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * Mengosongkan semua data lalu memakai nomor session baru. Dipakai saat logout.
     */
    public function reset(): void
    {
        foreach (array_keys($this->data) as $key) {
            unset($this->data[$key]);
        }

        $this->regenerate();
    }

    private static function isHttps(): bool
    {
        $https = $_SERVER['HTTPS'] ?? '';

        return $https !== '' && $https !== 'off';
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
     * Pesan kesalahan satu kali tampil, misalnya "Catatan penolakan wajib diisi." setelah redirect.
     */
    public function flashError(string $message): void
    {
        $messages = $this->take(self::FLASH_ERROR_KEY);
        $messages[] = $message;
        $this->data[self::FLASH_ERROR_KEY] = $messages;
    }

    /**
     * Mengambil semua pesan flash sekaligus menghapusnya.
     *
     * @return list<string>
     */
    public function takeFlashes(): array
    {
        return $this->take(self::FLASH_KEY);
    }

    /**
     * @return list<string>
     */
    public function takeErrors(): array
    {
        return $this->take(self::FLASH_ERROR_KEY);
    }

    /**
     * @return list<string>
     */
    private function take(string $key): array
    {
        $messages = $this->data[$key] ?? [];
        unset($this->data[$key]);

        return is_array($messages) ? array_values(array_filter($messages, 'is_string')) : [];
    }
}
