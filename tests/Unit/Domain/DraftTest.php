<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Domain;

use DateTimeImmutable;
use DateTimeZone;
use KurasiAI\Domain\Content\Article;
use KurasiAI\Domain\Content\Caption;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\Draft;
use KurasiAI\Domain\DraftStatus;
use KurasiAI\Domain\Prompt;
use KurasiAI\Domain\Role;
use KurasiAI\Domain\StatusChange;
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

        $draft->revise($this->writer, $newContent, $this->at('09:30'));

        $this->assertSame($newContent, $draft->content());
    }

    public function testReviseMustKeepTheSameContentType(): void
    {
        $draft = $this->makeDraft();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Revisi harus tetap berupa Artikel, bukan Caption.');

        $draft->revise($this->writer, new Caption('Energi surya', 'Hemat listrik dengan panel surya. #energi'), $this->at('09:30'));
    }

    public function testFollowsTheFullReviewFlow(): void
    {
        $draft = $this->makeDraft();

        $draft->submit($this->writer, $this->at('09:15'));
        $draft->approve($this->editor, $this->at('10:00'));
        $draft->publish($this->writer, $this->at('13:00'));

        $this->assertSame(DraftStatus::Published, $draft->status());
        $this->assertEquals($this->at('13:00'), $draft->publishedAt());
    }

    public function testCannotSkipReview(): void
    {
        $draft = $this->makeDraft();

        try {
            $draft->publish($this->writer, $this->at('09:15'));
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
        $draft->submit($this->writer, $this->at('09:15'));

        $this->expectException(ValidationException::class);

        $draft->reject($this->editor, '   ', $this->at('10:40'));
    }

    public function testRejectedDraftCanBeRevisedAndResubmitted(): void
    {
        $draft = $this->makeDraft();
        $draft->submit($this->writer, $this->at('09:15'));
        $draft->reject($this->editor, 'Tambahkan contoh nyata.', $this->at('10:40'));

        $this->assertSame('Tambahkan contoh nyata.', $draft->rejectionNote());

        $draft->revise($this->writer, new Article('Energi surya', str_repeat('Isi dengan contoh nyata. ', 15)), $this->at('11:00'));
        $draft->submit($this->writer, $this->at('11:05'));

        $this->assertSame(DraftStatus::InReview, $draft->status());
        $this->assertEquals($this->at('11:05'), $draft->submittedAt());
    }

    public function testCannotReviseWhileInReview(): void
    {
        $draft = $this->makeDraft();
        $draft->submit($this->writer, $this->at('09:15'));

        $this->expectException(InvalidStateException::class);

        $draft->revise($this->writer, new Article('Judul baru', str_repeat('Isi baru. ', 40)), $this->at('09:30'));
    }

    public function testPermissionIsCheckedBeforeTheStatus(): void
    {
        $draft = $this->makeDraft();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Hanya Editor yang boleh mereview draf.');

        $draft->approve($this->writer, $this->at('09:15'));
    }

    public function testRecordsWhenItWasCreatedAndUpdated(): void
    {
        $draft = $this->makeDraft();

        $this->assertEquals($this->at('09:00'), $draft->createdAt());
        $this->assertEquals($this->at('09:00'), $draft->updatedAt());

        $draft->submit($this->writer, $this->at('09:15'));

        $this->assertEquals($this->at('09:00'), $draft->createdAt());
        $this->assertEquals($this->at('09:15'), $draft->updatedAt());
    }

    public function testNewDraftHasNoHistory(): void
    {
        $draft = $this->makeDraft();

        $this->assertSame([], $draft->history());
        $this->assertNull($draft->submittedAt());
        $this->assertNull($draft->publishedAt());
        $this->assertNull($draft->rejectionNote());
    }

    public function testEveryStatusChangeIsRecorded(): void
    {
        $draft = $this->makeDraft();

        $draft->submit($this->writer, $this->at('09:15'));
        $draft->reject($this->editor, 'Tambahkan contoh nyata.', $this->at('10:40'));

        $this->assertEquals(
            [
                new StatusChange(DraftStatus::Draft, DraftStatus::InReview, 1, $this->at('09:15')),
                new StatusChange(DraftStatus::InReview, DraftStatus::Rejected, 2, $this->at('10:40'), 'Tambahkan contoh nyata.'),
            ],
            $draft->history(),
        );
    }

    public function testRevisionDoesNotAddHistory(): void
    {
        $draft = $this->makeDraft();

        $draft->revise($this->writer, new Article('Energi surya', str_repeat('Isi baru yang lebih rapi. ', 15)), $this->at('09:30'));

        $this->assertSame([], $draft->history());
        $this->assertEquals($this->at('09:30'), $draft->updatedAt());
    }

    public function testFailedTransitionLeavesHistoryUntouched(): void
    {
        $draft = $this->makeDraft();

        try {
            $draft->publish($this->writer, $this->at('09:15'));
        } catch (InvalidTransitionException) {
            // memang diharapkan
        }

        $this->assertSame([], $draft->history());
        $this->assertEquals($this->at('09:00'), $draft->updatedAt());
    }

    public function testRejectionNoteComesFromTheLatestRejection(): void
    {
        $draft = $this->makeDraft();
        $revision = new Article('Energi surya', str_repeat('Isi dengan contoh nyata. ', 15));

        $draft->submit($this->writer, $this->at('09:15'));
        $draft->reject($this->editor, 'Tambahkan contoh nyata.', $this->at('10:40'));
        $draft->revise($this->writer, $revision, $this->at('11:00'));
        $draft->submit($this->writer, $this->at('11:05'));
        $draft->reject($this->editor, 'Perbaiki judulnya.', $this->at('13:20'));

        $this->assertSame('Perbaiki judulnya.', $draft->rejectionNote());
        $this->assertCount(4, $draft->history());
    }

    private function makeDraft(): Draft
    {
        return new Draft(
            id: 1,
            authorId: $this->writer->id,
            content: new Article('Energi surya', str_repeat('Energi surya itu bersih. ', 15)),
            prompt: new Prompt('Tulis artikel tentang energi surya.'),
            createdAt: $this->at('09:00'),
        );
    }

    /** Waktu pada 5 Oktober 2026, WIB. */
    private function at(string $time): DateTimeImmutable
    {
        return new DateTimeImmutable("2026-10-05 $time", new DateTimeZone('Asia/Jakarta'));
    }
}
