<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class CaptionTest extends TestCase
{
    public function testCollectsHashtags(): void
    {
        $caption = new Caption('Hemat air', 'Matikan keran saat menyikat gigi. #hematair #bumi');

        $this->assertSame(['#hematair', '#bumi'], $caption->hashtags());
        $this->assertSame('2 tagar', $caption->metaLabel());
        $this->assertSame(ContentType::Caption, $caption->type());
    }

    public function testAcceptsExactly2200Characters(): void
    {
        $caption = new Caption('Panjang', str_repeat('a', 2200));

        $this->assertSame(2200, mb_strlen($caption->body));
    }

    public function testRejectsMoreThan2200Characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Caption maksimal 2200 karakter.');

        new Caption('Terlalu panjang', str_repeat('a', 2201));
    }

    public function testRejectsMoreThan30Hashtags(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Caption maksimal 30 tagar.');

        new Caption('Terlalu banyak tagar', trim(str_repeat('#tagar ', 31)));
    }
}
