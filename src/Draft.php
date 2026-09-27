<?php

declare(strict_types=1);

/**
 * Satu draf konten buatan AI. Draf MEMILIKI sebuah Content; Draf bukan Content.
 */
class Draft
{
    private ?string $rejectionNote = null;

    public function __construct(
        public readonly int $id,
        private Content $content,
        public readonly Prompt $prompt,
        private DraftStatus $status = DraftStatus::Draft,
        public readonly ?TokenUsage $usage = null,
    ) {
    }

    public function content(): Content
    {
        return $this->content;
    }

    public function title(): string
    {
        return $this->content->title;
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
     * Mengganti konten dengan versi baru yang JENISNYA SAMA.
     * Hanya boleh saat status Draf atau Ditolak.
     */
    public function revise(Content $newContent): void
    {
        if (!$this->status->isEditable()) {
            throw new DomainException("Draf berstatus {$this->status->label()} tidak bisa direvisi.");
        }

        if ($newContent->type() !== $this->content->type()) {
            throw new InvalidArgumentException(
                "Revisi harus tetap berupa {$this->content->type()->label()}, "
                . "bukan {$newContent->type()->label()}."
            );
        }

        $this->content = $newContent;
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
}
