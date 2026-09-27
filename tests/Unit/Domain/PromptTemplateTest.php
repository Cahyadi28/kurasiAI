<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Domain;

use InvalidArgumentException;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\PromptTemplate;
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

        $this->expectException(InvalidArgumentException::class);
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
        $this->expectException(InvalidArgumentException::class);

        new PromptTemplate('  ', 'Tulis tentang {{topik}}.', ContentType::Article);
    }

    public function testUpdateChangesTextAndTouchesTimestamp(): void
    {
        $template = $this->makeTemplate('Tulis tentang {{topik}}.');

        $template->update('Artikel singkat', 'Tulis artikel singkat tentang {{topik}}.');

        $this->assertSame('Artikel singkat', $template->name());
        $this->assertSame(['topik'], $template->variables());
        $this->assertGreaterThanOrEqual($template->createdAt(), $template->updatedAt());
    }

    private function makeTemplate(string $text): PromptTemplate
    {
        return new PromptTemplate('Artikel edukasi', $text, ContentType::Article);
    }
}
