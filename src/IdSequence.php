<?php

declare(strict_types=1);

/**
 * Pemberi nomor urut sederhana. Object ini dikirim ke siapa pun yang membutuhkannya,
 * sebagai pengganti penghitung static. Mulai Bab 12, nomor diberikan oleh database.
 */
final class IdSequence
{
    private int $last;

    public function __construct(int $start = 1)
    {
        if ($start < 1) {
            throw new InvalidArgumentException('Nomor awal minimal 1.');
        }

        $this->last = $start - 1;
    }

    public function next(): int
    {
        $this->last++;

        return $this->last;
    }
}
