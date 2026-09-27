<?php

declare(strict_types=1);

namespace KurasiAI\Support;

use DateInterval;
use DateTimeInterface;

/**
 * Menampilkan waktu dalam bahasa Indonesia tanpa ekstensi intl.
 * Semua method-nya static karena hanya mengubah nilai menjadi teks:
 * tidak menyimpan data dan tidak butuh dependensi.
 */
final class IndonesianDate
{
    private const MONTHS = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
    ];

    private function __construct()
    {
    }

    /**
     * Contoh: "5 Okt 2026, 09.15 WIB". Singkatan zona (WIB/WITA/WIT) diambil dari zona waktu objeknya.
     */
    public static function format(DateTimeInterface $at): string
    {
        $month = self::MONTHS[(int) $at->format('n')];

        return $at->format('j') . " $month " . $at->format('Y, H.i T');
    }

    /**
     * Contoh: "baru saja", "15 menit yang lalu", "3 hari yang lalu".
     * Lebih dari seminggu, atau waktu di masa depan, ditampilkan sebagai tanggal lengkap.
     */
    public static function relative(DateTimeInterface $at, DateTimeInterface $now): string
    {
        $diff = $at->diff($now);
        $days = (int) $diff->days;

        return match (true) {
            $diff->invert === 1, $days > 7 => self::format($at),
            $days >= 1 => "$days hari yang lalu",
            $diff->h >= 1 => "{$diff->h} jam yang lalu",
            $diff->i >= 1 => "{$diff->i} menit yang lalu",
            default => 'baru saja',
        };
    }

    /**
     * Contoh: "1 hari 2 jam 5 menit". Detik diabaikan.
     */
    public static function duration(DateInterval $interval): string
    {
        // Selisih hasil diff() punya jumlah hari total di $days; interval buatan sendiri tidak.
        $days = $interval->days !== false ? $interval->days : $interval->d;

        $parts = array_filter([
            'hari' => $days,
            'jam' => $interval->h,
            'menit' => $interval->i,
        ]);

        if ($parts === []) {
            return 'kurang dari 1 menit';
        }

        $texts = [];

        foreach ($parts as $unit => $value) {
            $texts[] = "$value $unit";
        }

        return implode(' ', $texts);
    }
}
