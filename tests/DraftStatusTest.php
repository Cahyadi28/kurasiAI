<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DraftStatusTest extends TestCase
{
    /**
     * Semua 25 pasangan status asal -> tujuan, beserta hasil yang diharapkan.
     * Tabel ini disalin dari diagram alur status (Bab 4), bukan dari kode.
     *
     * @return array<string, array{DraftStatus, DraftStatus, bool}>
     */
    public static function allTransitions(): array
    {
        $allowed = [
            'draft' => ['in_review'],
            'in_review' => ['approved', 'rejected'],
            'approved' => ['published'],
            'rejected' => ['in_review'],
            'published' => [],
        ];

        $cases = [];

        foreach (DraftStatus::cases() as $from) {
            foreach (DraftStatus::cases() as $to) {
                $name = "{$from->value} -> {$to->value}";
                $cases[$name] = [$from, $to, in_array($to->value, $allowed[$from->value], true)];
            }
        }

        return $cases;
    }

    #[DataProvider('allTransitions')]
    public function testTransitionRules(DraftStatus $from, DraftStatus $to, bool $expected): void
    {
        $this->assertSame($expected, $from->canTransitionTo($to));
    }

    public function testOnlyDraftAndRejectedAreEditable(): void
    {
        $editable = array_filter(
            DraftStatus::cases(),
            fn (DraftStatus $status): bool => $status->isEditable(),
        );

        $this->assertSame([DraftStatus::Draft, DraftStatus::Rejected], array_values($editable));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unknownValues(): array
    {
        return [
            'salah ketik' => ['publised'],
            'huruf besar' => ['PUBLISHED'],
            'kosong' => [''],
        ];
    }

    #[DataProvider('unknownValues')]
    public function testTryFromReturnsNullForUnknownValue(string $value): void
    {
        $this->assertNull(DraftStatus::tryFrom($value));
    }

    public function testFromRejectsUnknownValue(): void
    {
        $this->expectException(ValueError::class);

        DraftStatus::from('publised');
    }
}
