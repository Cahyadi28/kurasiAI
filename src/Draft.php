<?php

declare(strict_types=1);

/**
 * Satu draf konten buatan AI.
 */
class Draft
{
    private string $title;
    private string $body;
    private DraftStatus $status;
    private ?string $rejectionNote = null;

    public function __construct(
        public readonly int $id,
        string $title,
        string $body,
        public readonly string $type,
        public readonly Prompt $prompt,
        DraftStatus $status = DraftStatus::Draft,
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

    public function status(): DraftStatus
    {
        return $this->status;
    }

    public function rejectionNote(): ?string
    {
        return $this->rejectionNote;
    }

    /**
     * Mengganti judul dan isi. Hanya boleh saat status Draf atau Ditolak.
     * Keduanya diperiksa dulu, baru disimpan, sehingga draf tidak pernah setengah berubah.
     */
    public function revise(string $title, string $body): void
    {
        if (!$this->status->isEditable()) {
            throw new DomainException("Draf berstatus {$this->status->label()} tidak bisa direvisi.");
        }

        $newTitle = $this->validTitle($title);
        $newBody = $this->validBody($body);

        $this->title = $newTitle;
        $this->body = $newBody;
    }

    public function submit(): void
    {
        $this->transitionTo(DraftStatus::InReview);
    }

    public function approve(): void
    {
        $this->transitionTo(DraftStatus::Approved);
    }

    public function reject(string $note): void
    {
        $note = trim($note);

        if ($note === '') {
            throw new InvalidArgumentException('Catatan penolakan wajib diisi.');
        }

        $this->transitionTo(DraftStatus::Rejected);
        $this->rejectionNote = $note;
    }

    public function publish(): void
    {
        $this->transitionTo(DraftStatus::Published);
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

    /**
     * Satu-satunya tempat status diganti. Aturannya ditanyakan ke enum DraftStatus.
     */
    private function transitionTo(DraftStatus $target): void
    {
        if (!$this->status->canTransitionTo($target)) {
            throw new DomainException(
                "Draf berstatus {$this->status->label()} tidak bisa diubah menjadi {$target->label()}."
            );
        }

        $this->status = $target;
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
