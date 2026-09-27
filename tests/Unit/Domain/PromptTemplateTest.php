<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Domain;

use DateTimeImmutable;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\PromptTemplate;
use KurasiAI\Domain\Role;
use KurasiAI\Domain\User;
use KurasiAI\Exception\AccessDeniedException;
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

        new PromptTemplate(1, 1, '  ', 'Tulis tentang {{topik}}.', ContentType::Article, new DateTimeImmutable());
    }

    public function testUpdateChangesTextAndTouchesTimestamp(): void
    {
        $template = $this->makeTemplate('Tulis tentang {{topik}}.');

        $later = new DateTimeImmutable('2026-10-05 10:30');

        $template->update($this->creator(), 'Artikel singkat', 'Tulis artikel singkat tentang {{topik}}.', $later);

        $this->assertSame('Artikel singkat', $template->name());
        $this->assertSame(['topik'], $template->variables());
        $this->assertEquals(new DateTimeImmutable('2026-10-05 09:00'), $template->createdAt());
        $this->assertEquals($later, $template->updatedAt());
    }

    public function testOnlyTheCreatorMayUpdate(): void
    {
        $template = $this->makeTemplate('Tulis tentang {{topik}}.');
        $other = new User(8, 'Sari', 'sari@contoh.id', Role::Writer);

        $this->assertTrue($template->isCreatedBy($this->creator()));
        $this->assertFalse($template->isCreatedBy($other));

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Hanya pembuat template yang boleh mengubahnya.');

        $template->update($other, 'Diubah orang lain', 'Tulis tentang {{topik}}.', new DateTimeImmutable());
    }

    public function testRestoreKeepsBothTimestamps(): void
    {
        $template = PromptTemplate::restore(
            id: 4,
            createdBy: 7,
            name: 'Caption',
            text: 'Buat caption tentang {{topik}}.',
            targetType: ContentType::Caption,
            createdAt: new DateTimeImmutable('2026-10-05 09:00'),
            updatedAt: new DateTimeImmutable('2026-10-06 13:00'),
        );

        $this->assertSame(4, $template->id);
        $this->assertEquals(new DateTimeImmutable('2026-10-06 13:00'), $template->updatedAt());
    }

    private function makeTemplate(string $text): PromptTemplate
    {
        return new PromptTemplate(1, 7, 'Artikel edukasi', $text, ContentType::Article, new DateTimeImmutable('2026-10-05 09:00'));
    }

    private function creator(): User
    {
        return new User(7, 'Rina', 'rina@contoh.id', Role::Writer);
    }
}
