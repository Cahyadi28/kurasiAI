<?php

declare(strict_types=1);

namespace KurasiAI\Persistence;

use KurasiAI\Exception\ValidationException;

/**
 * Pilihan urutan daftar draf. Enum ini sekaligus menjadi whitelist:
 * hanya nama kolom di column() yang bisa masuk ke ORDER BY.
 */
enum DraftSort: string
{
    case Title = 'title';
    case Created = 'created';
    case Updated = 'updated';
    case Status = 'status';

    /**
     * Membaca pilihan dari input pengguna, misalnya ?sort=title. Kosong berarti urutan bawaan.
     */
    public static function fromInput(?string $input): self
    {
        if ($input === null || $input === '') {
            return self::Updated;
        }

        return self::tryFrom($input) ?? throw new ValidationException(
            "Urutan \"$input\" tidak dikenal. Pilihan: " . implode(', ', array_column(self::cases(), 'value')) . '.'
        );
    }

    /** Nama kolom di tabel drafts. */
    public function column(): string
    {
        return match ($this) {
            self::Title => 'title',
            self::Created => 'created_at',
            self::Updated => 'updated_at',
            self::Status => 'status',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Title => 'Judul',
            self::Created => 'Waktu dibuat',
            self::Updated => 'Terakhir diubah',
            self::Status => 'Status',
        };
    }
}
