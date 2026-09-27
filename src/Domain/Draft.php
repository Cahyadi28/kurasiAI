<?php

declare(strict_types=1);

namespace KurasiAI\Domain;

use KurasiAI\Domain\Content\Content;
use KurasiAI\Exception\AccessDeniedException;
use KurasiAI\Exception\InvalidStateException;
use KurasiAI\Exception\InvalidTransitionException;
use KurasiAI\Exception\ValidationException;
use KurasiAI\Support\HasTimestamps;

/**
 * Satu draf konten buatan AI. Draf MEMILIKI sebuah Content; Draf bukan Content.
 */
class Draft
{
    use HasTimestamps;

    private ?string $rejectionNote = null;

    public function __construct(
        public readonly int $id,
        public readonly int $authorId,
        private Content $content,
        public readonly Prompt $prompt,
        private DraftStatus $status = DraftStatus::Draft,
        public readonly ?TokenUsage $usage = null,
    ) {
        $this->initializeTimestamps();
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

    public function isOwnedBy(User $user): bool
    {
        return $user->id === $this->authorId;
    }

    /**
     * Mengganti konten dengan versi baru yang JENISNYA SAMA.
     * Hanya pemilik, dan hanya saat status Draf atau Ditolak.
     */
    public function revise(User $actor, Content $newContent): void
    {
        $this->ensureOwner($actor, 'merevisi draf');

        if (!$this->status->isEditable()) {
            throw new InvalidStateException("Draf berstatus {$this->status->label()} tidak bisa direvisi.");
        }

        if ($newContent->type() !== $this->content->type()) {
            throw new ValidationException(
                "Revisi harus tetap berupa {$this->content->type()->label()}, "
                . "bukan {$newContent->type()->label()}."
            );
        }

        $this->content = $newContent;
        $this->touch();
    }

    public function submit(User $actor): void
    {
        $this->ensureOwner($actor, 'mengirim draf ke review');
        $this->transitionTo(DraftStatus::InReview);
    }

    public function approve(User $actor): void
    {
        $this->ensureReviewer($actor);
        $this->transitionTo(DraftStatus::Approved);
    }

    public function reject(User $actor, string $note): void
    {
        $this->ensureReviewer($actor);

        $note = trim($note);

        if ($note === '') {
            throw new ValidationException('Catatan penolakan wajib diisi.');
        }

        $this->transitionTo(DraftStatus::Rejected);
        $this->rejectionNote = $note;
    }

    public function publish(User $actor): void
    {
        $this->ensureOwner($actor, 'menerbitkan draf');
        $this->transitionTo(DraftStatus::Published);
    }

    private function ensureOwner(User $actor, string $action): void
    {
        if (!$this->isOwnedBy($actor)) {
            $this->deny("Hanya penulis draf ini yang boleh $action.");
        }
    }

    private function ensureReviewer(User $actor): void
    {
        if (!$actor->role->canReview()) {
            $this->deny('Hanya Editor yang boleh mereview draf.');
        }

        if ($this->isOwnedBy($actor)) {
            $this->deny('Editor tidak boleh mereview draf miliknya sendiri.');
        }
    }

    /**
     * Menolak tindakan karena pelaku tidak berhak. Method ini tidak pernah kembali.
     */
    private function deny(string $reason): never
    {
        throw new AccessDeniedException($reason);
    }

    /**
     * Satu-satunya tempat status diganti. Aturannya ditanyakan ke enum DraftStatus.
     */
    private function transitionTo(DraftStatus $target): void
    {
        if (!$this->status->canTransitionTo($target)) {
            throw new InvalidTransitionException(from: $this->status, to: $target);
        }

        $this->status = $target;
        $this->touch();
    }
}
