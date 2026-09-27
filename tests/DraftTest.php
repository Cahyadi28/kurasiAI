<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class DraftTest extends TestCase
{
    public function testNewDraftHasDraftStatus(): void
    {
        $draft = $this->makeDraft();

        $this->assertSame('draft', $draft->status());
    }

    public function testRejectsEmptyTitle(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Judul draf tidak boleh kosong.');

        $this->makeDraft(title: '   ');
    }

    public function testRejectsTitleLongerThan150Characters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeDraft(title: str_repeat('a', 151));
    }

    public function testRejectsEmptyBody(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Isi draf tidak boleh kosong.');

        $this->makeDraft(body: '');
    }

    public function testReviseReplacesTitleAndBody(): void
    {
        $draft = $this->makeDraft();

        $draft->revise(title: 'Judul baru', body: 'Isi baru.');

        $this->assertSame('Judul baru', $draft->title());
        $this->assertSame('Isi baru.', $draft->body());
    }

    public function testRejectedRevisionChangesNothing(): void
    {
        $draft = $this->makeDraft(title: 'Judul awal', body: 'Isi awal.');

        try {
            $draft->revise(title: 'Judul baru', body: '');
            $this->fail('Revisi dengan isi kosong seharusnya ditolak.');
        } catch (InvalidArgumentException) {
            // memang diharapkan
        }

        $this->assertSame('Judul awal', $draft->title());
        $this->assertSame('Isi awal.', $draft->body());
    }

    private function makeDraft(string $title = 'Energi surya', string $body = 'Isi draf.'): Draft
    {
        return new Draft(
            id: 1,
            title: $title,
            body: $body,
            type: 'article',
            prompt: new Prompt('Tulis artikel tentang energi surya.'),
        );
    }
}
