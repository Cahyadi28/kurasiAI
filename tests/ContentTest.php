<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Aturan yang berlaku untuk SEMUA jenis konten (ditulis di class Content).
 * Diuji lewat Caption karena Caption punya aturan tambahan paling longgar.
 */
class ContentTest extends TestCase
{
    public function testTrimsTitleAndBody(): void
    {
        $content = new Caption('  Hemat air  ', '  Matikan keran. #hematair  ');

        $this->assertSame('Hemat air', $content->title);
        $this->assertSame('Matikan keran. #hematair', $content->body);
    }

    public function testRejectsEmptyTitle(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Judul konten tidak boleh kosong.');

        new Caption('   ', 'Isi caption.');
    }

    public function testRejectsTitleLongerThan150Characters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Caption(str_repeat('a', 151), 'Isi caption.');
    }

    public function testRejectsEmptyBody(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Isi konten tidak boleh kosong.');

        new Caption('Hemat air', '');
    }

    public function testCountsWordsSeparatedBySpacesAndNewLines(): void
    {
        $content = new Caption('Hemat air', "Matikan keran\nsaat menyikat gigi.");

        $this->assertSame(5, $content->wordCount());
    }
}
