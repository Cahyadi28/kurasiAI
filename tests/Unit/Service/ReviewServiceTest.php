<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Service;

use DateInterval;
use KurasiAI\Domain\Content\Caption;
use KurasiAI\Domain\Draft;
use KurasiAI\Domain\DraftStatus;
use KurasiAI\Domain\Prompt;
use KurasiAI\Domain\Role;
use KurasiAI\Domain\User;
use KurasiAI\Exception\AccessDeniedException;
use KurasiAI\Exception\InvalidStateException;
use KurasiAI\Persistence\InMemoryDraftRepository;
use KurasiAI\Service\ReviewService;
use KurasiAI\Support\FixedClock;
use PHPUnit\Framework\TestCase;

class ReviewServiceTest extends TestCase
{
    private FixedClock $clock;
    private InMemoryDraftRepository $drafts;
    private ReviewService $service;
    private User $rina;
    private User $sari;
    private User $budi;

    protected function setUp(): void
    {
        $this->clock = FixedClock::at('2026-10-05 09:00');
        $this->drafts = new InMemoryDraftRepository();
        $this->service = new ReviewService($this->clock, $this->drafts);
        $this->rina = new User(1, 'Rina', 'rina@contoh.id', Role::Writer);
        $this->sari = new User(2, 'Sari', 'sari@contoh.id', Role::Writer);
        $this->budi = new User(3, 'Budi', 'budi@contoh.id', Role::Editor);
    }

    public function testEveryActionUsesTheTimeFromTheClock(): void
    {
        $draft = $this->makeDraft(1, $this->rina);

        $this->clock->advance(new DateInterval('PT15M'));
        $this->service->submit($draft, $this->rina);

        $this->clock->advance(new DateInterval('PT1H25M'));
        $this->service->reject($draft, $this->budi, 'Tambahkan satu fakta.');

        $this->clock->advance(new DateInterval('PT20M'));
        $this->service->revise($draft, $this->rina, new Caption('Hemat air', 'Keran bocor membuang 20 liter sehari. #hematair'));

        $this->assertSame(
            ['09:15', '10:40'],
            array_map(fn ($change): string => $change->at->format('H:i'), $draft->history()),
        );
        $this->assertSame('11:00', $draft->updatedAt()->format('H:i'));
    }

    public function testFullFlowRecordsPublishTime(): void
    {
        $draft = $this->makeDraft(1, $this->rina);

        $this->service->submit($draft, $this->rina);
        $this->clock->advance(new DateInterval('P1D'));
        $this->service->approve($draft, $this->budi);
        $this->clock->advance(new DateInterval('PT2H'));
        $this->service->publish($draft, $this->rina);

        $this->assertSame(DraftStatus::Published, $draft->status());
        $this->assertSame('2026-10-06 11:00', $draft->publishedAt()?->format('Y-m-d H:i'));
    }

    public function testRulesAreStillGuardedByTheDraft(): void
    {
        $draft = $this->makeDraft(1, $this->rina);
        $this->service->submit($draft, $this->rina);

        $this->expectException(AccessDeniedException::class);

        $this->service->approve($draft, $this->sari);
    }

    public function testQueueShowsTheLongestWaitingFirst(): void
    {
        $first = $this->makeDraft(1, $this->rina);
        $second = $this->makeDraft(2, $this->sari);
        $notSubmitted = $this->makeDraft(3, $this->rina);
        $ownDraft = $this->makeDraft(4, $this->budi);

        $this->service->submit($second, $this->sari);
        $this->clock->advance(new DateInterval('PT30M'));
        $this->service->submit($first, $this->rina);
        $this->service->submit($ownDraft, $this->budi);

        $queue = $this->service->reviewQueue($this->budi);

        // Draf #3 belum dikirim dan draf #4 milik Budi sendiri, jadi keduanya tidak masuk antrean.
        $this->assertSame([2, 1], array_map(fn (Draft $draft): int => $draft->id, $queue));
        $this->assertSame(DraftStatus::Draft, $notSubmitted->status());
    }

    public function testOnlyEditorsHaveAReviewQueue(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->service->reviewQueue($this->rina);
    }

    public function testWaitingTimeCountsFromTheLatestSubmission(): void
    {
        $draft = $this->makeDraft(1, $this->rina);

        $this->service->submit($draft, $this->rina);
        $this->clock->advance(new DateInterval('PT1H'));
        $this->service->reject($draft, $this->budi, 'Perbaiki judul.');
        $this->service->submit($draft, $this->rina);
        $this->clock->advance(new DateInterval('PT45M'));

        $waiting = $this->service->waitingTime($draft);

        $this->assertSame('0:45', $waiting->format('%h:%I'));
    }

    public function testWaitingTimeOnlyForDraftsInReview(): void
    {
        $draft = $this->makeDraft(1, $this->rina);

        $this->expectException(InvalidStateException::class);

        $this->service->waitingTime($draft);
    }

    public function testEveryActionIsSaved(): void
    {
        $draft = $this->makeDraft(1, $this->rina);

        $this->service->submit($draft, $this->rina);
        $this->service->reject($draft, $this->budi, 'Perbaiki judul.');

        $stored = $this->drafts->get($draft->id);

        $this->assertSame(DraftStatus::Rejected, $stored->status());
        $this->assertCount(2, $stored->history());
    }

    /**
     * Draf baru disimpan lewat repository; nomornya harus sesuai urutan pembuatan di tes.
     */
    private function makeDraft(int $expectedId, User $author): Draft
    {
        $draft = $this->drafts->add(
            authorId: $author->id,
            content: new Caption('Hemat air', 'Matikan keran saat menggosok gigi. #hematair'),
            prompt: new Prompt('Buat caption tentang hemat air.'),
            createdAt: $this->clock->now(),
        );
        $this->assertSame($expectedId, $draft->id);

        return $draft;
    }
}
