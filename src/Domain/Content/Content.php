<?php

declare(strict_types=1);

namespace KurasiAI\Domain\Content;

use KurasiAI\Exception\ValidationException;


/**
 * Kerangka bersama semua jenis konten. Tidak bisa dibuat langsung dengan new;
 * yang dibuat adalah turunannya: Article, VideoScript, atau Caption.
 */
abstract class Content
{
    public const MAX_TITLE_LENGTH = 150;

    public readonly string $title;
    public readonly string $body;

    public function __construct(string $title, string $body)
    {
        $title = trim($title);
        $body = trim($body);

        if ($title === '') {
            throw new ValidationException('Judul konten tidak boleh kosong.');
        }

        if (mb_strlen($title) > self::MAX_TITLE_LENGTH) {
            throw new ValidationException('Judul konten maksimal ' . self::MAX_TITLE_LENGTH . ' karakter.');
        }

        if ($body === '') {
            throw new ValidationException('Isi konten tidak boleh kosong.');
        }

        $this->title = $title;
        $this->body = $body;
    }

    /**
     * Jenis konten ini. Wajib diisi oleh setiap turunan.
     */
    abstract public function type(): ContentType;

    /**
     * Keterangan singkat khas tiap jenis, misalnya "±2 menit baca". Wajib diisi setiap turunan.
     */
    abstract public function metaLabel(): string;

    public function wordCount(): int
    {
        return count($this->words());
    }

    public function summary(int $length = 50): string
    {
        if (mb_strlen($this->body) <= $length) {
            return $this->body;
        }

        return rtrim(mb_substr($this->body, 0, $length)) . '...';
    }

    /**
     * Isi konten dipecah per kata (dipisah spasi atau baris baru).
     * Protected: boleh dipakai turunan, tidak boleh dari luar.
     *
     * @return list<string>
     */
    protected function words(): array
    {
        return preg_split('/\s+/', $this->body, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
