<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Contract;

use DateInterval;
use KurasiAI\Ai\AiResult;
use KurasiAI\Domain\Content\Caption;
use KurasiAI\Domain\Draft;
use KurasiAI\Domain\DraftStatus;
use KurasiAI\Domain\Prompt;
use KurasiAI\Domain\Role;
use KurasiAI\Domain\TokenUsage;
use KurasiAI\Domain\User;
use KurasiAI\Exception\NotFoundException;
use KurasiAI\Persistence\DraftRepository;
use KurasiAI\Persistence\DraftSort;
use KurasiAI\Persistence\SortDirection;
use KurasiAI\Support\FixedClock;
use PHPUnit\Framework\TestCase;

/**
 * Janji setiap DraftRepository. Tes yang sama dijalankan untuk versi in-memory (tes unit)
 * dan versi SQLite (tes integrasi), sehingga keduanya dijamin berperilaku sama.
 * Pengguna #1 (Rina, Penulis), #2 (Sari, Penulis), dan #3 (Budi, Editor) dianggap sudah ada.
 */
abstract class DraftRepositoryContract extends TestCase
{
    protected DraftRepository $repository;
    protected FixedClock $clock;
    protected User $rina;
    protected User $sari;
    protected User $budi;

    abstract protected function createRepository(): DraftRepository;

    protected function setUp(): void
    {
        $this->clock = FixedClock::at('2026-10-05 09:00');
        $this->rina = new User(1, 'Rina', 'rina@kurasiai.test', Role::Writer);
        $this->sari = new User(2, 'Sari', 'sari@kurasiai.test', Role::Writer);
        $this->budi = new User(3, 'Budi', 'budi@kurasiai.test', Role::Editor);
        $this->repository = $this->createRepository();
    }

    public function testAddGivesIncreasingNumbers(): void
    {
        $first = $this->addDraft($this->rina, 'Hemat air');
        $second = $this->addDraft($this->rina, 'Hemat listrik');

        $this->assertSame([1, 2], [$first->id, $second->id]);
        $this->assertSame(DraftStatus::Draft, $first->status());
        $this->assertSame([], $first->history());
    }

    public function testAddedDraftCanBeLoadedAgain(): void
    {
        $added = $this->addDraft($this->rina, 'Hemat air');

        $loaded = $this->repository->get($added->id);

        $this->assertSame('Hemat air', $loaded->title());
        $this->assertSame($added->content()->body, $loaded->content()->body);
        $this->assertInstanceOf(Caption::class, $loaded->content());
        $this->assertSame('Buat caption tentang Hemat air.', $loaded->prompt->text);
        $this->assertTrue($loaded->isOwnedBy($this->rina));
        $this->assertEquals(new TokenUsage(12, 30), $loaded->usage);
        $this->assertEquals($this->clock->now(), $loaded->createdAt());
    }

    public function testUnknownNumber(): void
    {
        $this->assertNull($this->repository->find(99));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Draf #99 tidak ditemukan.');

        $this->repository->get(99);
    }

    public function testChangesAreKeptOnlyAfterSave(): void
    {
        $draft = $this->addDraft($this->rina, 'Hemat air');

        $draft->submit($this->rina, $this->clock->now());

        $this->assertSame(DraftStatus::Draft, $this->repository->get($draft->id)->status());

        $this->repository->save($draft);

        $this->assertSame(DraftStatus::InReview, $this->repository->get($draft->id)->status());
    }

    public function testSaveKeepsRevisionStatusAndHistory(): void
    {
        $draft = $this->addDraft($this->rina, 'Hemat air');

        $this->clock->advance(new DateInterval('PT15M'));
        $draft->submit($this->rina, $this->clock->now());
        $this->repository->save($draft);

        $this->clock->advance(new DateInterval('PT1H'));
        $draft->reject($this->budi, 'Tambahkan satu fakta.', $this->clock->now());
        $this->repository->save($draft);

        $this->clock->advance(new DateInterval('PT20M'));
        $draft->revise($this->rina, new Caption('Hemat air', 'Keran bocor membuang 20 liter sehari. #hematair'), $this->clock->now());
        $this->repository->save($draft);

        $loaded = $this->repository->get($draft->id);

        $this->assertSame(DraftStatus::Rejected, $loaded->status());
        $this->assertSame('Tambahkan satu fakta.', $loaded->rejectionNote());
        $this->assertSame('Keran bocor membuang 20 liter sehari. #hematair', $loaded->content()->body);
        $this->assertCount(2, $loaded->history(), 'Setiap perubahan status tersimpan tepat satu kali.');
        $this->assertEquals($draft->history(), $loaded->history());
        $this->assertEquals($this->clock->now(), $loaded->updatedAt());
    }

    public function testCannotSaveADraftThatWasNeverAdded(): void
    {
        $stranger = new Draft(
            id: 42,
            authorId: $this->rina->id,
            content: new Caption('Hemat air', 'Matikan keran. #hematair'),
            prompt: new Prompt('Buat caption.'),
            createdAt: $this->clock->now(),
        );

        $this->expectException(NotFoundException::class);

        $this->repository->save($stranger);
    }

    public function testListByAuthorFiltersAndSorts(): void
    {
        $this->addDraft($this->rina, 'Kompos');
        $this->clock->advance(new DateInterval('PT1M'));
        $this->addDraft($this->sari, 'Bukan milik Rina');
        $this->clock->advance(new DateInterval('PT1M'));
        $this->addDraft($this->rina, 'Air bersih');
        $this->clock->advance(new DateInterval('PT1M'));
        $this->addDraft($this->rina, 'Listrik');

        $titles = fn (array $drafts): array => array_map(fn (Draft $d): string => $d->title(), $drafts);

        $this->assertSame(
            ['Listrik', 'Air bersih', 'Kompos'],
            $titles($this->repository->listByAuthor($this->rina->id)),
            'Bawaan: yang terakhir diubah lebih dulu.',
        );
        $this->assertSame(
            ['Air bersih', 'Kompos', 'Listrik'],
            $titles($this->repository->listByAuthor($this->rina->id, DraftSort::Title, SortDirection::Asc)),
        );
        $this->assertSame(
            ['Kompos', 'Air bersih', 'Listrik'],
            $titles($this->repository->listByAuthor($this->rina->id, DraftSort::Created, SortDirection::Asc)),
        );
    }

    public function testListByStatus(): void
    {
        $first = $this->addDraft($this->rina, 'Hemat air');
        $this->addDraft($this->rina, 'Belum dikirim');
        $third = $this->addDraft($this->sari, 'Hemat listrik');

        foreach ([$third, $first] as $draft) {
            $draft->submit($draft->isOwnedBy($this->rina) ? $this->rina : $this->sari, $this->clock->now());
            $this->repository->save($draft);
        }

        $inReview = $this->repository->listByStatus(DraftStatus::InReview);

        $this->assertSame([1, 3], array_map(fn (Draft $d): int => $d->id, $inReview));
    }

    protected function addDraft(User $author, string $topic): Draft
    {
        $body = "Mari mulai dari rumah: $topic. #hemat";

        return $this->repository->add(
            authorId: $author->id,
            content: new Caption($topic, $body),
            prompt: new Prompt("Buat caption tentang $topic."),
            createdAt: $this->clock->now(),
            source: new AiResult($topic, $body, new TokenUsage(12, 30), 'fake'),
        );
    }
}
