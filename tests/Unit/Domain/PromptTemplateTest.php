<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Domain;

use DateTimeImmutable;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\PromptTemplate;
use KurasiAI\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class PromptTemplateTest extends TestCase
{
    public function testListsEachVariableOnceInOrder(): void
    {
        $template = $this->makeTemplate('Tulis tentang {{topik}} untuk {{pembaca}}. Ingat, {{ topik }}!');

        $this->assertSame(['topik', 'pembaca'], $template->variables());
    }

    public function testRendersAPromptWithAllVariablesFilled(): void
    {
        $template = $this->makeTemplate('Tulis artikel tentang {{topik}} untuk {{pembaca}}.');

        $prompt = $template->render(['topik' => 'energi surya', 'pembaca' => 'pelajar SMA']);

        $this->assertSame('Tulis artikel tentang energi surya untuk pelajar SMA.', $prompt->text);
    }

    public function testRejectsMissingOrEmptyVariables(): void
    {
        $template = $this->makeTemplate('Tulis artikel tentang {{topik}} untuk {{pembaca}}.');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Variabel belum diisi: topik, pembaca.');

        $template->render(['topik' => '   ']);
    }

    public function testTemplateWithoutVariablesRendersAsIs(): void
    {
        $template = $this->makeTemplate('Buat caption tentang hemat air.');

        $this->assertSame([], $template->variables());
        $this->assertSame('Buat caption tentang hemat air.', $template->render([])->text);
    }

    public function testRejectsEmptyName(): void
    {
        $this->expectException(ValidationException::class);

        new PromptTemplate('  ', 'Tulis tentang {{topik}}.', ContentType::Article, new DateTimeImmutable());
    }

    public function testUpdateChangesTextAndTouchesTimestamp(): void
    {
        $template = $this->makeTemplate('Tulis tentang {{topik}}.');

        $later = new DateTimeImmutable('2026-10-05 10:30');

        $template->update('Artikel singkat', 'Tulis artikel singkat tentang {{topik}}.', $later);

        $this->assertSame('Artikel singkat', $template->name());
        $this->assertSame(['topik'], $template->variables());
        $this->assertEquals(new DateTimeImmutable('2026-10-05 09:00'), $template->createdAt());
        $this->assertEquals($later, $template->updatedAt());
    }

    private function makeTemplate(string $text): PromptTemplate
    {
        return new PromptTemplate('Artikel edukasi', $text, ContentType::Article, new DateTimeImmutable('2026-10-05 09:00'));
    }
}
