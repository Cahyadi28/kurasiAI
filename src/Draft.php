<?php

declare(strict_types=1);

/**
 * Satu draf konten buatan AI.
 */
class Draft
{
    public function __construct(
        public int $id,
        public string $title,
        public string $body,
        public string $type,
        public string $status = 'draft',
    ) {
    }

    public function statusLabel(): string
    {
        $labels = [
            'draft' => 'Draf',
            'in_review' => 'Sedang direview',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'published' => 'Terbit',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    public function typeLabel(): string
    {
        $labels = [
            'article' => 'Artikel',
            'video_script' => 'Naskah Video',
            'caption' => 'Caption',
        ];

        return $labels[$this->type] ?? $this->type;
    }

    public function wordCount(): int
    {
        return str_word_count($this->body);
    }

    public function summary(int $length = 50): string
    {
        if (mb_strlen($this->body) <= $length) {
            return $this->body;
        }

        return rtrim(mb_substr($this->body, 0, $length)) . '...';
    }
}
