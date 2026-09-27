<?php

declare(strict_types=1);

/**
 * Satu draf konten buatan AI.
 */
class Draft
{
    private string $title;
    private string $body;
    private string $status;

    public function __construct(
        public readonly int $id,
        string $title,
        string $body,
        public readonly string $type,
        public readonly Prompt $prompt,
        string $status = 'draft',
        public readonly ?TokenUsage $usage = null,
    ) {
        $this->title = $this->validTitle($title);
        $this->body = $this->validBody($body);
        $this->status = $status;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function status(): string
    {
        return $this->status;
    }

    /**
     * Mengganti judul dan isi. Keduanya diperiksa dulu, baru disimpan,
     * sehingga draf tidak pernah setengah berubah.
     */
    public function revise(string $title, string $body): void
    {
        $newTitle = $this->validTitle($title);
        $newBody = $this->validBody($body);

        $this->title = $newTitle;
        $this->body = $newBody;
    }

    /**
     * Status belum diperiksa sama sekali. Aturan status dibangun di Bab 4.
     */
    public function changeStatus(string $newStatus): void
    {
        $this->status = $newStatus;
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

    private function validTitle(string $title): string
    {
        $title = trim($title);

        if ($title === '') {
            throw new InvalidArgumentException('Judul draf tidak boleh kosong.');
        }

        if (mb_strlen($title) > 150) {
            throw new InvalidArgumentException('Judul draf maksimal 150 karakter.');
        }

        return $title;
    }

    private function validBody(string $body): string
    {
        $body = trim($body);

        if ($body === '') {
            throw new InvalidArgumentException('Isi draf tidak boleh kosong.');
        }

        return $body;
    }
}
