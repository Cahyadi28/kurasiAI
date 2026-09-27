<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class DraftTest extends TestCase
{
    public function testNewDraftHasDraftStatus(): void
    {
        $draft = $this->makeDraft();

        $this->assertSame(DraftStatus::Draft, $draft->status());
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

        $draft->revise($newContent);

        $this->assertSame($newContent, $draft->content());
        $this->assertSame('Energi surya di rumah', $draft->title());
    }

    public function testReviseMustKeepTheSameContentType(): void
    {
        $draft = $this->makeDraft();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Revisi harus tetap berupa Artikel, bukan Caption.');

        $draft->revise(new Caption('Energi surya', 'Hemat listrik dengan panel surya. #energi'));
    }

    public function testFollowsTheFullReviewFlow(): void
    {
        $draft = $this->makeDraft();

        $draft->submit();
        $this->assertSame(DraftStatus::InReview, $draft->status());

        $draft->approve();
        $this->assertSame(DraftStatus::Approved, $draft->status());

        $draft->publish();
        $this->assertSame(DraftStatus::Published, $draft->status());
    }

    public function testCannotSkipReview(): void
    {
        $draft = $this->makeDraft();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Draf berstatus Draf tidak bisa diubah menjadi Terbit.');

        $draft->publish();
    }

    public function testRejectedTransitionKeepsTheOldStatus(): void
    {
        $draft = $this->makeDraft();

        try {
            $draft->approve();
            $this->fail('Draf yang belum direview seharusnya tidak bisa disetujui.');
        } catch (DomainException) {
            // memang diharapkan
        }

        $this->assertSame(DraftStatus::Draft, $draft->status());
    }

    public function testRejectRequiresANote(): void
    {
        $draft = $this->makeDraft();
        $draft->submit();

        $this->expectException(InvalidArgumentException::class);

        $draft->reject('   ');
    }

    public function testRejectedDraftCanBeRevisedAndResubmitted(): void
    {
        $draft = $this->makeDraft();
        $draft->submit();
        $draft->reject('Tambahkan contoh nyata.');

        $this->assertSame(DraftStatus::Rejected, $draft->status());
        $this->assertSame('Tambahkan contoh nyata.', $draft->rejectionNote());

        $draft->revise(new Article('Energi surya', str_repeat('Isi baru dengan contoh nyata. ', 12)));
        $draft->submit();

        $this->assertSame(DraftStatus::InReview, $draft->status());
    }

    public function testCannotReviseWhileInReview(): void
    {
        $draft = $this->makeDraft();
        $draft->submit();

        $this->expectException(DomainException::class);

        $draft->revise(new Article('Judul baru', str_repeat('Isi baru. ', 40)));
    }

    public function testRecordsWhenItWasCreatedAndUpdated(): void
    {
        $draft = $this->makeDraft();

        $this->assertEquals($draft->createdAt(), $draft->updatedAt());

        $draft->submit();

        $this->assertGreaterThanOrEqual($draft->createdAt(), $draft->updatedAt());
    }

    private function makeDraft(): Draft
    {
        return new Draft(
            id: 1,
            content: new Article('Energi surya', str_repeat('Energi surya itu bersih. ', 15)),
            prompt: new Prompt('Tulis artikel tentang energi surya.'),
        );
    }
}
