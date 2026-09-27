<?php

declare(strict_types=1);

namespace KurasiAI\Support;

use DateTimeImmutable;

/**
 * Catatan waktu dibuat dan terakhir diubah.
 * Class pemakai memanggil initializeTimestamps() di constructor
 * dan touch() setiap kali datanya berubah. Waktunya selalu dikirim dari luar.
 */
trait HasTimestamps
{
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    protected function initializeTimestamps(DateTimeImmutable $now): void
    {
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    protected function touch(DateTimeImmutable $now): void
    {
        $this->updatedAt = $now;
    }
}
