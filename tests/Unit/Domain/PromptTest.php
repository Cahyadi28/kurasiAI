<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Domain;

use InvalidArgumentException;
use KurasiAI\Domain\Prompt;
use PHPUnit\Framework\TestCase;

class PromptTest extends TestCase
{
    public function testTrimsSurroundingWhitespace(): void
    {
        $prompt = new Prompt('  Tulis artikel tentang energi surya.  ');

        $this->assertSame('Tulis artikel tentang energi surya.', $prompt->text);
    }

    public function testRejectsEmptyText(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Prompt('');
    }

    public function testRejectsWhitespaceOnlyText(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Prompt('     ');
    }

    public function testAcceptsExactly2000Characters(): void
    {
        $prompt = new Prompt(str_repeat('a', Prompt::MAX_LENGTH));

        $this->assertSame(Prompt::MAX_LENGTH, mb_strlen($prompt->text));
    }

    public function testRejectsMoreThan2000Characters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Prompt(str_repeat('a', Prompt::MAX_LENGTH + 1));
    }
}
