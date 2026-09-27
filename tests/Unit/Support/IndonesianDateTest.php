<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Support;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use KurasiAI\Support\IndonesianDate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IndonesianDateTest extends TestCase
{
    public function testFormatsInIndonesian(): void
    {
        $at = new DateTimeImmutable('2026-10-05 09:05', new DateTimeZone('Asia/Jakarta'));

        $this->assertSame('5 Okt 2026, 09.05 WIB', IndonesianDate::format($at));
    }

    public function testTimezoneAbbreviationFollowsTheObject(): void
    {
        $jakarta = new DateTimeImmutable('2026-10-05 09:00', new DateTimeZone('Asia/Jakarta'));

        $this->assertSame('5 Okt 2026, 10.00 WITA', IndonesianDate::format($jakarta->setTimezone(new DateTimeZone('Asia/Makassar'))));
        $this->assertSame('5 Okt 2026, 11.00 WIT', IndonesianDate::format($jakarta->setTimezone(new DateTimeZone('Asia/Jayapura'))));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function relativeCases(): array
    {
        return [
            'kurang dari semenit' => ['2026-10-05 08:59:30', 'baru saja'],
            'menit' => ['2026-10-05 08:45', '15 menit yang lalu'],
            'jam' => ['2026-10-05 06:30', '2 jam yang lalu'],
            'hari' => ['2026-10-02 09:00', '3 hari yang lalu'],
            'lebih dari seminggu' => ['2026-09-20 14:00', '20 Sep 2026, 14.00 WIB'],
            'masa depan' => ['2026-10-06 09:00', '6 Okt 2026, 09.00 WIB'],
        ];
    }

    #[DataProvider('relativeCases')]
    public function testRelativeTime(string $at, string $expected): void
    {
        $zone = new DateTimeZone('Asia/Jakarta');
        $now = new DateTimeImmutable('2026-10-05 09:00', $zone);

        $this->assertSame($expected, IndonesianDate::relative(new DateTimeImmutable($at, $zone), $now));
    }

    public function testDurationFromDiff(): void
    {
        $zone = new DateTimeZone('Asia/Jakarta');
        $from = new DateTimeImmutable('2026-10-05 09:15', $zone);
        $to = new DateTimeImmutable('2026-10-06 11:20:45', $zone);

        $this->assertSame('1 hari 2 jam 5 menit', IndonesianDate::duration($from->diff($to)));
    }

    public function testDurationSkipsZeroParts(): void
    {
        $this->assertSame('1 jam 25 menit', IndonesianDate::duration(new DateInterval('PT1H25M')));
        $this->assertSame('2 hari', IndonesianDate::duration(new DateInterval('P2D')));
        $this->assertSame('kurang dari 1 menit', IndonesianDate::duration(new DateInterval('PT40S')));
    }
}
