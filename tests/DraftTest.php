<?php

declare(strict_types=1);

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

        $this->expectException(InvalidArgumentException::class);
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

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Draf berstatus Draf tidak bisa diubah menjadi Terbit.');

        $draft->publish($this->writer);
    }

    public function testRejectRequiresANote(): void
    {
        $draft = $this->makeDraft();
        $draft->submit($this->writer);

        $this->expectException(InvalidArgumentException::class);

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

        $this->expectException(DomainException::class);

        $draft->revise($this->writer, new Article('Judul baru', str_repeat('Isi baru. ', 40)));
    }

    public function testPermissionIsCheckedBeforeTheStatus(): void
    {
        $draft = $this->makeDraft();

        $this->expectException(DomainException::class);
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
