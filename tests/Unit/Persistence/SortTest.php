<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Persistence;

use KurasiAI\Exception\ValidationException;
use KurasiAI\Persistence\DraftSort;
use KurasiAI\Persistence\SortDirection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SortTest extends TestCase
{
    public function testEmptyInputUsesTheDefault(): void
    {
        $this->assertSame(DraftSort::Updated, DraftSort::fromInput(null));
        $this->assertSame(DraftSort::Updated, DraftSort::fromInput(''));
        $this->assertSame(SortDirection::Desc, SortDirection::fromInput(null));
    }

    public function testKnownInput(): void
    {
        $this->assertSame(DraftSort::Title, DraftSort::fromInput('title'));
        $this->assertSame('title', DraftSort::Title->column());
        $this->assertSame('created_at', DraftSort::Created->column());
        $this->assertSame(SortDirection::Asc, SortDirection::fromInput('ASC'));
        $this->assertSame('DESC', SortDirection::Desc->sql());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function maliciousSorts(): array
    {
        return [
            'kolom rahasia' => ['password_hash'],
            'nama kolom asli' => ['created_at'],
            'sisipan SQL' => ['title; DROP TABLE drafts'],
            'huruf besar' => ['Title'],
        ];
    }

    #[DataProvider('maliciousSorts')]
    public function testUnknownSortIsRejected(string $input): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Pilihan: title, created, updated, status.');

        DraftSort::fromInput($input);
    }

    public function testUnknownDirectionIsRejected(): void
    {
        $this->expectException(ValidationException::class);

        SortDirection::fromInput('asc, (SELECT 1)');
    }
}
