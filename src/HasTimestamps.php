<?php

declare(strict_types=1);

/**
 * Catatan waktu dibuat dan terakhir diubah.
 * Class pemakai memanggil initializeTimestamps() di constructor
 * dan touch() setiap kali datanya berubah.
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

    protected function initializeTimestamps(): void
    {
        $now = new DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    protected function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
