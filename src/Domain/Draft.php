<?php

declare(strict_types=1);

namespace KurasiAI\Domain;

use DateTimeImmutable;
use KurasiAI\Domain\Content\Content;
use KurasiAI\Exception\AccessDeniedException;
use KurasiAI\Exception\InvalidStateException;
use KurasiAI\Exception\InvalidTransitionException;
use KurasiAI\Exception\ValidationException;
use KurasiAI\Support\HasTimestamps;

/**
 * Satu draf konten buatan AI. Draf MEMILIKI sebuah Content; Draf bukan Content.
 * Setiap perubahan status dicatat sebagai StatusChange. Waktunya selalu dikirim dari luar.
 */
class Draft
{
    use HasTimestamps;

    /** @var list<StatusChange> */
    private array $history = [];

    public function __construct(
        public readonly int $id,
        public readonly int $authorId,
        private Content $content,
        public readonly Prompt $prompt,
        DateTimeImmutable $createdAt,
        private DraftStatus $status = DraftStatus::Draft,
        public readonly ?TokenUsage $usage = null,
    ) {
        $this->initializeTimestamps($createdAt);
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

    /**
     * Semua perubahan status, dari yang paling lama.
     *
     * @return list<StatusChange>
     */
    public function history(): array
    {
        return $this->history;
    }

    /** Kapan terakhir kali dikirim ke review, atau null kalau belum pernah. */
    public function submittedAt(): ?DateTimeImmutable
    {
        return $this->lastChangeTo(DraftStatus::InReview)?->at;
    }

    public function publishedAt(): ?DateTimeImmutable
    {
        return $this->lastChangeTo(DraftStatus::Published)?->at;
    }

    /** Catatan dari penolakan terakhir, atau null kalau belum pernah ditolak. */
    public function rejectionNote(): ?string
    {
        return $this->lastChangeTo(DraftStatus::Rejected)?->note;
    }

    public function isOwnedBy(User $user): bool
    {
        return $user->id === $this->authorId;
    }

    /**
     * Mengganti konten dengan versi baru yang JENISNYA SAMA.
     * Hanya pemilik, dan hanya saat status Draf atau Ditolak.
     */
    public function revise(User $actor, Content $newContent, DateTimeImmutable $at): void
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
        $this->touch($at);
    }

    public function submit(User $actor, DateTimeImmutable $at): void
    {
        $this->ensureOwner($actor, 'mengirim draf ke review');
        $this->transitionTo(DraftStatus::InReview, $actor, $at);
    }

    public function approve(User $actor, DateTimeImmutable $at): void
    {
        $this->ensureReviewer($actor);
        $this->transitionTo(DraftStatus::Approved, $actor, $at);
    }

    public function reject(User $actor, string $note, DateTimeImmutable $at): void
    {
        $this->ensureReviewer($actor);

        $note = trim($note);

        if ($note === '') {
            throw new ValidationException('Catatan penolakan wajib diisi.');
        }

        $this->transitionTo(DraftStatus::Rejected, $actor, $at, $note);
    }

    public function publish(User $actor, DateTimeImmutable $at): void
    {
        $this->ensureOwner($actor, 'menerbitkan draf');
        $this->transitionTo(DraftStatus::Published, $actor, $at);
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
     * Satu-satunya tempat status diganti. Aturannya ditanyakan ke enum DraftStatus,
     * lalu perubahannya dicatat di riwayat.
     */
    private function transitionTo(
        DraftStatus $target,
        User $actor,
        DateTimeImmutable $at,
        ?string $note = null,
    ): void {
        if (!$this->status->canTransitionTo($target)) {
            throw new InvalidTransitionException(from: $this->status, to: $target);
        }

        $this->history[] = new StatusChange(
            from: $this->status,
            to: $target,
            actorId: $actor->id,
            at: $at,
            note: $note,
        );
        $this->status = $target;
        $this->touch($at);
    }

    private function lastChangeTo(DraftStatus $status): ?StatusChange
    {
        foreach (array_reverse($this->history) as $change) {
            if ($change->to === $status) {
                return $change;
            }
        }

        return null;
    }
}
