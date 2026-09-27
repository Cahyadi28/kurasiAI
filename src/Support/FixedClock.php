<?php

declare(strict_types=1);

namespace KurasiAI\Support;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Jam yang hanya bergerak kalau disuruh. Dipakai di tes dan demo
 * supaya hasilnya selalu sama, kapan pun dijalankan.
 */
final class FixedClock implements Clock
{
    public function __construct(private DateTimeImmutable $now)
    {
    }

    /**
     * Jalan pintas: FixedClock::at('2026-10-05 09:00').
     */
    public static function at(string $dateTime, string $timezone = SystemClock::DEFAULT_TIMEZONE): self
    {
        return new self(new DateTimeImmutable($dateTime, new DateTimeZone($timezone)));
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    /**
     * Memajukan jam, misalnya advance(new DateInterval('PT15M')) untuk 15 menit.
     */
    public function advance(DateInterval $interval): void
    {
        $this->now = $this->now->add($interval);
    }
}
