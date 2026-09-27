<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Integration;

use DateTimeImmutable;
use DateTimeZone;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\Role;
use KurasiAI\Domain\User;
use KurasiAI\Exception\NotFoundException;
use KurasiAI\Exception\ValidationException;
use KurasiAI\Persistence\PromptTemplateRepository;

class PromptTemplateRepositoryTest extends DatabaseTestCase
{
    private PromptTemplateRepository $templates;
    private User $rina;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templates = new PromptTemplateRepository($this->db);
        $this->rina = new User($this->insertUser('rina@kurasiai.test'), 'Rina', 'rina@kurasiai.test', Role::Writer);
        $this->now = new DateTimeImmutable('2026-10-05 09:00', new DateTimeZone('Asia/Jakarta'));
    }

    public function testAddAndLoadAgain(): void
    {
        $added = $this->templates->add($this->rina, '  Caption kampanye ', 'Buat caption tentang {{topik}}.', ContentType::Caption, $this->now);

        $loaded = $this->templates->get($added->id);

        $this->assertSame('Caption kampanye', $loaded->name());
        $this->assertSame(['topik'], $loaded->variables());
        $this->assertSame(ContentType::Caption, $loaded->targetType);
        $this->assertTrue($loaded->isCreatedBy($this->rina));
        $this->assertEquals($this->now, $loaded->createdAt());
    }

    public function testInvalidTemplateLeavesNoRow(): void
    {
        try {
            $this->templates->add($this->rina, '', 'Buat caption.', ContentType::Caption, $this->now);
            $this->fail('Nama kosong seharusnya ditolak.');
        } catch (ValidationException) {
            // memang diharapkan
        }

        $this->assertSame(0, $this->rowCount('prompt_templates'));
    }

    public function testSaveKeepsTheUpdate(): void
    {
        $template = $this->templates->add($this->rina, 'Caption', 'Buat caption tentang {{topik}}.', ContentType::Caption, $this->now);
        $later = $this->now->modify('+1 hour');

        $template->update($this->rina, 'Caption singkat', 'Buat caption singkat tentang {{topik}}.', $later);
        $this->templates->save($template);

        $loaded = $this->templates->get($template->id);

        $this->assertSame('Caption singkat', $loaded->name());
        $this->assertEquals($later, $loaded->updatedAt());
        $this->assertEquals($this->now, $loaded->createdAt());
    }

    public function testAllIsSortedByName(): void
    {
        $this->templates->add($this->rina, 'Caption', 'Buat caption tentang {{topik}}.', ContentType::Caption, $this->now);
        $this->templates->add($this->rina, 'Artikel', 'Tulis artikel tentang {{topik}}.', ContentType::Article, $this->now);

        $this->assertSame(['Artikel', 'Caption'], array_map(fn ($t): string => $t->name(), $this->templates->all()));
    }

    public function testNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Template #5 tidak ditemukan.');

        $this->templates->get(5);
    }
}
