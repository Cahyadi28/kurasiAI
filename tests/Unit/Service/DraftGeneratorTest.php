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
use KurasiAI\Support\IdSequence;
use PHPUnit\Framework\TestCase;

class DraftGeneratorTest extends TestCase
{
    public function testCreatesADraftFromATemplate(): void
    {
        $generator = new DraftGenerator(new FakeAiProvider(), new IdSequence());
        $author = new User(7, 'Rina', 'rina@contoh.id', Role::Writer);
        $template = new PromptTemplate('Caption', 'Buat caption tentang {{topik}}.', ContentType::Caption);

        $draft = $generator->generate($author, $template, ['topik' => 'hemat air']);

        $this->assertSame(1, $draft->id);
        $this->assertSame(7, $draft->authorId);
        $this->assertSame('Buat caption tentang hemat air.', $draft->prompt->text);
        $this->assertInstanceOf(Caption::class, $draft->content());
        $this->assertNotNull($draft->usage);
        $this->assertSame(DraftStatus::Draft, $draft->status());
    }

    public function testNumbersDraftsInOrder(): void
    {
        $generator = new DraftGenerator(new FakeAiProvider(), new IdSequence(start: 10));
        $author = new User(1, 'Rina', 'rina@contoh.id', Role::Writer);
        $template = new PromptTemplate('Caption', 'Buat caption tentang {{topik}}.', ContentType::Caption);

        $first = $generator->generate($author, $template, ['topik' => 'hemat air']);
        $second = $generator->generate($author, $template, ['topik' => 'hemat listrik']);

        $this->assertSame([10, 11], [$first->id, $second->id]);
    }

    public function testFailedGenerationDoesNotUseUpANumber(): void
    {
        $generator = new DraftGenerator(new FakeAiProvider(), new IdSequence());
        $author = new User(1, 'Rina', 'rina@contoh.id', Role::Writer);
        $template = new PromptTemplate('Caption', 'Buat caption tentang {{topik}}.', ContentType::Caption);

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
