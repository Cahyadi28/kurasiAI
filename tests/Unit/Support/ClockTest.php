<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Support;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use KurasiAI\Support\FixedClock;
use KurasiAI\Support\SystemClock;
use PHPUnit\Framework\TestCase;

class ClockTest extends TestCase
{
    public function testSystemClockUsesJakartaTimeByDefault(): void
    {
        $now = (new SystemClock())->now();

        $this->assertSame('Asia/Jakarta', $now->getTimezone()->getName());
    }

    public function testSystemClockCanUseAnotherTimezone(): void
    {
        $now = (new SystemClock(new DateTimeZone('Asia/Makassar')))->now();

        $this->assertSame('Asia/Makassar', $now->getTimezone()->getName());
    }

    public function testSystemClockFollowsTheRealTime(): void
    {
        $before = new DateTimeImmutable();
        $now = (new SystemClock())->now();
        $after = new DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $now);
        $this->assertLessThanOrEqual($after, $now);
    }

    public function testFixedClockStandsStill(): void
    {
        $clock = FixedClock::at('2026-10-05 09:00');

        $this->assertEquals($clock->now(), $clock->now());
        $this->assertSame('2026-10-05T09:00:00+07:00', $clock->now()->format(DATE_ATOM));
    }

    public function testFixedClockMovesOnlyWhenAdvanced(): void
    {
        $clock = FixedClock::at('2026-10-05 09:00');
        $start = $clock->now();

        $clock->advance(new DateInterval('PT1H25M'));

        $this->assertSame('2026-10-05 10:25', $clock->now()->format('Y-m-d H:i'));
        $this->assertSame('2026-10-05 09:00', $start->format('Y-m-d H:i'), 'Waktu yang sudah diambil tidak ikut bergeser.');
    }
}
