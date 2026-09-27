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
use KurasiAI\Service\DraftGenerator;
use KurasiAI\Support\FixedClock;
use KurasiAI\Support\IdSequence;
use PHPUnit\Framework\TestCase;

class DraftGeneratorTest extends TestCase
{
    private FixedClock $clock;

    protected function setUp(): void
    {
        $this->clock = FixedClock::at('2026-10-05 09:00');
    }

    public function testCreatesADraftFromATemplate(): void
    {
        $generator = new DraftGenerator(new FakeAiProvider(), new IdSequence(), $this->clock);
        $author = new User(7, 'Rina', 'rina@contoh.id', Role::Writer);
        $template = new PromptTemplate('Caption', 'Buat caption tentang {{topik}}.', ContentType::Caption, $this->clock->now());

        $draft = $generator->generate($author, $template, ['topik' => 'hemat air']);

        $this->assertSame(1, $draft->id);
        $this->assertSame(7, $draft->authorId);
        $this->assertSame('Buat caption tentang hemat air.', $draft->prompt->text);
        $this->assertInstanceOf(Caption::class, $draft->content());
        $this->assertNotNull($draft->usage);
        $this->assertSame(DraftStatus::Draft, $draft->status());
        $this->assertEquals($this->clock->now(), $draft->createdAt());
    }

    public function testNumbersDraftsInOrder(): void
    {
        $generator = new DraftGenerator(new FakeAiProvider(), new IdSequence(start: 10), $this->clock);
        $author = new User(1, 'Rina', 'rina@contoh.id', Role::Writer);
        $template = new PromptTemplate('Caption', 'Buat caption tentang {{topik}}.', ContentType::Caption, $this->clock->now());

        $first = $generator->generate($author, $template, ['topik' => 'hemat air']);
        $second = $generator->generate($author, $template, ['topik' => 'hemat listrik']);

        $this->assertSame([10, 11], [$first->id, $second->id]);
    }

    public function testFailedGenerationDoesNotUseUpANumber(): void
    {
        $generator = new DraftGenerator(new FakeAiProvider(), new IdSequence(), $this->clock);
        $author = new User(1, 'Rina', 'rina@contoh.id', Role::Writer);
        $template = new PromptTemplate('Caption', 'Buat caption tentang {{topik}}.', ContentType::Caption, $this->clock->now());

        try {
            $generator->generate($author, $template, []);
            $this->fail('Template tanpa nilai variabel seharusnya ditolak.');
        } catch (ValidationException) {
            // memang diharapkan
        }

        $draft = $generator->generate($author, $template, ['topik' => 'hemat air']);

        $this->assertSame(1, $draft->id);
    }
}
