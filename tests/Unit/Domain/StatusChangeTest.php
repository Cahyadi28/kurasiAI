<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Domain;

use DateTimeImmutable;
use Error;
use KurasiAI\Domain\DraftStatus;
use KurasiAI\Domain\StatusChange;
use PHPUnit\Framework\TestCase;

class StatusChangeTest extends TestCase
{
    public function testNoteIsOptional(): void
    {
        $change = new StatusChange(DraftStatus::Draft, DraftStatus::InReview, 1, new DateTimeImmutable('2026-10-05 09:15'));

        $this->assertNull($change->note);
        $this->assertSame(DraftStatus::InReview, $change->to);
    }

    public function testHistoryCannotBeRewritten(): void
    {
        $change = new StatusChange(DraftStatus::Draft, DraftStatus::InReview, 1, new DateTimeImmutable('2026-10-05 09:15'));

        $this->expectException(Error::class);

        /** @phpstan-ignore property.readOnlyAssignOutOfClass */
        $change->actorId = 99;
    }
}
