<?php

declare(strict_types=1);

namespace KurasiAI\Support;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Jam sungguhan dari sistem operasi, dalam zona waktu aplikasi.
 */
final class SystemClock implements Clock
{
    public const DEFAULT_TIMEZONE = 'Asia/Jakarta';

    public function __construct(
        private readonly DateTimeZone $timezone = new DateTimeZone(self::DEFAULT_TIMEZONE),
    ) {
    }

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $this->timezone);
    }
}
