<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Validation;

use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Validation\MaxLength;
use KurasiAI\Validation\OneOf;
use KurasiAI\Validation\Required;
use KurasiAI\Validation\Rule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RulesTest extends TestCase
{
    /**
     * @return array<string, array{Rule, mixed, ?string}>
     */
    public static function cases(): array
    {
        return [
            'wajib: kosong' => [new Required(), '', 'Wajib diisi.'],
            'wajib: hanya spasi' => [new Required(), '   ', 'Wajib diisi.'],
            'wajib: null' => [new Required(), null, 'Wajib diisi.'],
            'wajib: pesan sendiri' => [new Required(message: 'Judul wajib diisi.'), '', 'Judul wajib diisi.'],
            'wajib: terisi' => [new Required(), 'Rina', null],
            'wajib: angka nol tetap terisi' => [new Required(), '0', null],
            'maks: pas' => [new MaxLength(5), 'abcde', null],
            'maks: lebih' => [new MaxLength(5), 'abcdef', 'Maksimal 5 karakter.'],
            'maks: huruf beraksen dihitung satu' => [new MaxLength(4), 'café', null],
            'pilihan: sah' => [new OneOf(ContentType::class), 'caption', null],
            'pilihan: tidak dikenal' => [new OneOf(ContentType::class), 'podcast', 'Pilihan tidak dikenal.'],
            'pilihan: kosong diserahkan ke Required' => [new OneOf(ContentType::class), '', null],
            'pilihan: bukan teks' => [new OneOf(ContentType::class), ['caption'], 'Pilihan tidak dikenal.'],
        ];
    }

    #[DataProvider('cases')]
    public function testRule(Rule $rule, mixed $value, ?string $expected): void
    {
        $this->assertSame($expected, $rule->check($value));
    }
}
