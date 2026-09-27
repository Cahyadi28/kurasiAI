<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TokenUsageTest extends TestCase
{
    public function testCalculatesTotal(): void
    {
        $usage = new TokenUsage(input: 14, output: 42);

        $this->assertSame(56, $usage->total());
    }

    public function testAddReturnsNewObjectWithoutChangingTheOriginal(): void
    {
        $first = new TokenUsage(input: 14, output: 42);
        $second = new TokenUsage(input: 10, output: 30);

        $sum = $first->add($second);

        $this->assertSame(96, $sum->total());
        $this->assertSame(56, $first->total());
        $this->assertNotSame($first, $sum);
    }

    public function testZeroHasNoTokens(): void
    {
        $this->assertSame(0, TokenUsage::zero()->total());
    }

    public function testSumAddsAnyNumberOfUsages(): void
    {
        $sum = TokenUsage::sum(
            new TokenUsage(input: 14, output: 42),
            new TokenUsage(input: 10, output: 30),
            new TokenUsage(input: 1, output: 3),
        );

        $this->assertTrue($sum->equals(new TokenUsage(input: 25, output: 75)));
        $this->assertSame(0, TokenUsage::sum()->total());
    }

    public function testRejectsNegativeTokens(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TokenUsage(input: -1, output: 10);
    }

    public function testObjectsWithSameValuesAreEqual(): void
    {
        $a = new TokenUsage(input: 14, output: 42);
        $b = new TokenUsage(input: 14, output: 42);

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals(new TokenUsage(input: 14, output: 43)));
    }
}
