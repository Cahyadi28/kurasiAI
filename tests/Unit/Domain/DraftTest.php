<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Domain;

use KurasiAI\Domain\Content\Article;
use KurasiAI\Domain\Content\Caption;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\Draft;
use KurasiAI\Domain\DraftStatus;
use KurasiAI\Domain\Prompt;
use KurasiAI\Domain\Role;
use KurasiAI\Domain\User;
use KurasiAI\Exception\AccessDeniedException;
use KurasiAI\Exception\InvalidStateException;
use KurasiAI\Exception\InvalidTransitionException;
use KurasiAI\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class DraftTest extends TestCase
{
    private User $writer;
    private User $editor;

    protected function setUp(): void
    {
        $this->writer = new User(1, 'Rina', 'rina@contoh.id', Role::Writer);
        $this->editor = new User(2, 'Budi', 'budi@contoh.id', Role::Editor);
    }

    public function testNewDraftHasDraftStatus(): void
    {
        $draft = $this->makeDraft();

        $this->assertSame(DraftStatus::Draft, $draft->status());
        $this->assertTrue($draft->isOwnedBy($this->writer));
        $this->assertFalse($draft->isOwnedBy($this->editor));
    }

    public function testTitleComesFromTheContent(): void
    {
        $draft = $this->makeDraft();

        $this->assertSame('Energi surya', $draft->title());
        $this->assertSame(ContentType::Article, $draft->content()->type());
    }

    public function testReviseReplacesTheContent(): void
    {
        $draft = $this->makeDraft();
        $newContent = new Article('Energi surya di rumah', str_repeat('Isi baru. ', 40));

        $draft->revise($this->writer, $newContent);

        $this->assertSame($newContent, $draft->content());
    }

    public function testReviseMustKeepTheSameContentType(): void
    {
        $draft = $this->makeDraft();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Revisi harus tetap berupa Artikel, bukan Caption.');

        $draft->revise($this->writer, new Caption('Energi surya', 'Hemat listrik dengan panel surya. #energi'));
    }

    public function testFollowsTheFullReviewFlow(): void
    {
        $draft = $this->makeDraft();

        $draft->submit($this->writer);
        $draft->approve($this->editor);
        $draft->publish($this->writer);

        $this->assertSame(DraftStatus::Published, $draft->status());
    }

    public function testCannotSkipReview(): void
    {
        $draft = $this->makeDraft();

        try {
            $draft->publish($this->writer);
            $this->fail('Draf yang belum direview seharusnya tidak bisa diterbitkan.');
        } catch (InvalidTransitionException $e) {
            $this->assertSame('Draf berstatus Draf tidak bisa diubah menjadi Terbit.', $e->getMessage());
            $this->assertSame(DraftStatus::Draft, $e->from);
            $this->assertSame(DraftStatus::Published, $e->to);
        }
    }

    public function testRejectRequiresANote(): void
    {
        $draft = $this->makeDraft();
        $draft->submit($this->writer);

        $this->expectException(ValidationException::class);

        $draft->reject($this->editor, '   ');
    }

    public function testRejectedDraftCanBeRevisedAndResubmitted(): void
    {
        $draft = $this->makeDraft();
        $draft->submit($this->writer);
        $draft->reject($this->editor, 'Tambahkan contoh nyata.');

        $this->assertSame('Tambahkan contoh nyata.', $draft->rejectionNote());

        $draft->revise($this->writer, new Article('Energi surya', str_repeat('Isi dengan contoh nyata. ', 15)));
        $draft->submit($this->writer);

        $this->assertSame(DraftStatus::InReview, $draft->status());
    }

    public function testCannotReviseWhileInReview(): void
    {
        $draft = $this->makeDraft();
        $draft->submit($this->writer);

        $this->expectException(InvalidStateException::class);

        $draft->revise($this->writer, new Article('Judul baru', str_repeat('Isi baru. ', 40)));
    }

    public function testPermissionIsCheckedBeforeTheStatus(): void
    {
        $draft = $this->makeDraft();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Hanya Editor yang boleh mereview draf.');

        $draft->approve($this->writer);
    }

    public function testRecordsWhenItWasCreatedAndUpdated(): void
    {
        $draft = $this->makeDraft();

        $this->assertEquals($draft->createdAt(), $draft->updatedAt());

        $draft->submit($this->writer);

        $this->assertGreaterThanOrEqual($draft->createdAt(), $draft->updatedAt());
    }

    private function makeDraft(): Draft
    {
        return new Draft(
            id: 1,
            authorId: $this->writer->id,
            content: new Article('Energi surya', str_repeat('Energi surya itu bersih. ', 15)),
            prompt: new Prompt('Tulis artikel tentang energi surya.'),
        );
    }
}
