<?php

declare(strict_types=1);

namespace KurasiAI\Service;

use DateInterval;
use KurasiAI\Domain\Content\Content;
use KurasiAI\Domain\Draft;
use KurasiAI\Domain\DraftStatus;
use KurasiAI\Domain\User;
use KurasiAI\Exception\AccessDeniedException;
use KurasiAI\Exception\InvalidStateException;
use KurasiAI\Persistence\DraftRepository;
use KurasiAI\Support\Clock;

/**
 * Pintu masuk semua tindakan yang mengubah draf.
 * Aturannya tetap dijaga Draft; service ini menyediakan waktu dari Clock
 * lalu menyimpan hasilnya lewat DraftRepository.
 */
final class ReviewService
{
    public function __construct(
        private readonly Clock $clock,
        private readonly DraftRepository $drafts,
    ) {
    }

    public function revise(Draft $draft, User $actor, Content $newContent): void
    {
        $draft->revise($actor, $newContent, $this->clock->now());
        $this->drafts->save($draft);
    }

    public function submit(Draft $draft, User $actor): void
    {
        $draft->submit($actor, $this->clock->now());
        $this->drafts->save($draft);
    }

    public function approve(Draft $draft, User $actor): void
    {
        $draft->approve($actor, $this->clock->now());
        $this->drafts->save($draft);
    }

    public function reject(Draft $draft, User $actor, string $note): void
    {
        $draft->reject($actor, $note, $this->clock->now());
        $this->drafts->save($draft);
    }

    public function publish(Draft $draft, User $actor): void
    {
        $draft->publish($actor, $this->clock->now());
        $this->drafts->save($draft);
    }

    /**
     * Draf yang menunggu review oleh Editor ini: bukan miliknya sendiri,
     * diurutkan dari yang paling lama menunggu.
     *
     * @return list<Draft>
     */
    public function reviewQueue(User $reviewer): array
    {
        if (!$reviewer->role->canReview()) {
            throw new AccessDeniedException('Hanya Editor yang boleh melihat antrean review.');
        }

        $queue = array_values(array_filter(
            $this->drafts->listByStatus(DraftStatus::InReview),
            fn (Draft $draft): bool => !$draft->isOwnedBy($reviewer),
        ));

        usort(
            $queue,
            fn (Draft $a, Draft $b): int => [$a->submittedAt(), $a->id] <=> [$b->submittedAt(), $b->id],
        );

        return $queue;
    }

    /**
     * Sudah berapa lama draf ini menunggu review.
     */
    public function waitingTime(Draft $draft): DateInterval
    {
        $submittedAt = $draft->submittedAt();

        if ($draft->status() !== DraftStatus::InReview || $submittedAt === null) {
            throw new InvalidStateException('Draf ini tidak sedang menunggu review.');
        }

        return $submittedAt->diff($this->clock->now());
    }
}
