<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Service;

use KurasiAI\Ai\FakeAiProvider;
use KurasiAI\Domain\Content\Caption;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\DraftStatus;
use KurasiAI\Domain\PromptTemplate;
use KurasiAI\Domain\Role;
use KurasiAI\Domain\User;
use KurasiAI\Exception\ValidationException;
use KurasiAI\Persistence\InMemoryDraftRepository;
use KurasiAI\Service\DraftGenerator;
use KurasiAI\Support\FixedClock;
use PHPUnit\Framework\TestCase;

class DraftGeneratorTest extends TestCase
{
    private FixedClock $clock;
    private InMemoryDraftRepository $drafts;
    private DraftGenerator $generator;
    private User $author;
    private PromptTemplate $template;

    protected function setUp(): void
    {
        $this->clock = FixedClock::at('2026-10-05 09:00');
        $this->drafts = new InMemoryDraftRepository();
        $this->generator = new DraftGenerator(new FakeAiProvider(), $this->drafts, $this->clock);
        $this->author = new User(7, 'Rina', 'rina@contoh.id', Role::Writer);
        $this->template = new PromptTemplate(1, 7, 'Caption', 'Buat caption tentang {{topik}}.', ContentType::Caption, $this->clock->now());
    }

    public function testCreatesAndStoresADraftFromATemplate(): void
    {
        $draft = $this->generator->generate($this->author, $this->template, ['topik' => 'hemat air']);

        $this->assertSame(1, $draft->id);
        $this->assertSame(7, $draft->authorId);
        $this->assertSame('Buat caption tentang hemat air.', $draft->prompt->text);
        $this->assertInstanceOf(Caption::class, $draft->content());
        $this->assertNotNull($draft->usage);
        $this->assertSame(DraftStatus::Draft, $draft->status());
        $this->assertEquals($this->clock->now(), $draft->createdAt());
        $this->assertSame($draft->title(), $this->drafts->get($draft->id)->title(), 'Draf baru langsung tersimpan.');
    }

    public function testNumbersComeFromTheRepository(): void
    {
        $first = $this->generator->generate($this->author, $this->template, ['topik' => 'hemat air']);
        $second = $this->generator->generate($this->author, $this->template, ['topik' => 'hemat listrik']);

        $this->assertSame([1, 2], [$first->id, $second->id]);
    }

    public function testFailedGenerationStoresNothing(): void
    {
        try {
            $this->generator->generate($this->author, $this->template, []);
            $this->fail('Template tanpa nilai variabel seharusnya ditolak.');
        } catch (ValidationException) {
            // memang diharapkan
        }

        $this->assertSame([], $this->drafts->listByAuthor($this->author->id));

        $draft = $this->generator->generate($this->author, $this->template, ['topik' => 'hemat air']);

        $this->assertSame(1, $draft->id);
    }
}
