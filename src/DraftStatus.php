<?php

declare(strict_types=1);

/**
 * Status sebuah draf. Nilai string dipakai saat disimpan atau dikirim lewat form.
 */
enum DraftStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::InReview => 'Sedang direview',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Published => 'Terbit',
        };
    }

    /**
     * Aturan alur draf: dari status ini, boleh pindah ke status mana?
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft => $target === self::InReview,
            self::InReview => $target === self::Approved || $target === self::Rejected,
            self::Rejected => $target === self::InReview,
            self::Approved => $target === self::Published,
            self::Published => false,
        };
    }

    /**
     * Isi draf hanya boleh direvisi saat masih Draf atau setelah Ditolak.
     */
    public function isEditable(): bool
    {
        return $this === self::Draft || $this === self::Rejected;
    }
}
