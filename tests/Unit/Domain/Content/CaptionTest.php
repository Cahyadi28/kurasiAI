<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Domain\Content;

use KurasiAI\Domain\Content\Caption;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Exception\ValidationException;
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
        $caption = new Caption('Panjang', str_repeat('a', Caption::MAX_BODY_LENGTH));

        $this->assertSame(Caption::MAX_BODY_LENGTH, mb_strlen($caption->body));
    }

    public function testRejectsMoreThan2200Characters(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Caption maksimal 2200 karakter.');

        new Caption('Terlalu panjang', str_repeat('a', Caption::MAX_BODY_LENGTH + 1));
    }

    public function testRejectsMoreThan30Hashtags(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Caption maksimal 30 tagar.');

        new Caption('Terlalu banyak tagar', trim(str_repeat('#tagar ', Caption::MAX_HASHTAGS + 1)));
    }
}
