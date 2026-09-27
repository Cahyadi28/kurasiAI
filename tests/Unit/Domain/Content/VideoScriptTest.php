<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Domain\Content;

use InvalidArgumentException;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\Content\VideoScript;
use PHPUnit\Framework\TestCase;

class VideoScriptTest extends TestCase
{
    public function testEachNonEmptyLineIsAScene(): void
    {
        $script = new VideoScript('Belajar PHP', "Adegan pembuka.\n\n  Adegan isi.  \nAdegan penutup.");

        $this->assertSame(['Adegan pembuka.', 'Adegan isi.', 'Adegan penutup.'], $script->scenes());
        $this->assertSame(ContentType::VideoScript, $script->type());
    }

    public function testRejectsFewerThanTwoScenes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Naskah video minimal 2 adegan (satu adegan per baris).');

        new VideoScript('Belajar PHP', 'Hanya satu adegan panjang tanpa baris baru.');
    }

    public function testEstimatesDurationAt150WordsPerMinute(): void
    {
        $body = trim(str_repeat('kata ', 75)) . "\n" . trim(str_repeat('kata ', 75));
        $script = new VideoScript('Belajar PHP', $body);

        $this->assertSame(60, $script->estimatedSeconds());
        $this->assertSame('2 adegan, ±60 detik', $script->metaLabel());
    }
}
