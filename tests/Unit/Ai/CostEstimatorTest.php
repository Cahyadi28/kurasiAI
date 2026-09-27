<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Ai;

use KurasiAI\Ai\CostEstimator;
use KurasiAI\Domain\TokenUsage;
use KurasiAI\Exception\ConfigException;
use KurasiAI\Support\Env;
use PHPUnit\Framework\TestCase;

class CostEstimatorTest extends TestCase
{
    public function testEstimatesFromPricePerMillionTokens(): void
    {
        $costs = new CostEstimator(inputPerMillion: 1.0, outputPerMillion: 5.0);
        $usage = new TokenUsage(input: 2_000, output: 1_000);

        // 2.000 x $1/juta + 1.000 x $5/juta = $0,002 + $0,005 = $0,007
        $this->assertEqualsWithDelta(0.007, $costs->estimate($usage), 1e-12);
        $this->assertSame('$0.0070', $costs->format($usage));
    }

    public function testNoPricesMeansNoEstimate(): void
    {
        $this->assertNull(CostEstimator::fromEnv(Env::fromString("AI_PRICE_INPUT_PER_MTOK=\nAI_PRICE_OUTPUT_PER_MTOK=5")));
    }

    public function testReadsPricesFromEnv(): void
    {
        $costs = CostEstimator::fromEnv(Env::fromString("AI_PRICE_INPUT_PER_MTOK=0.25\nAI_PRICE_OUTPUT_PER_MTOK=1.25"));

        $this->assertNotNull($costs);
        $this->assertSame([0.25, 1.25], [$costs->inputPerMillion, $costs->outputPerMillion]);
    }

    public function testRejectsPricesThatAreNotNumbers(): void
    {
        $this->expectException(ConfigException::class);

        CostEstimator::fromEnv(Env::fromString("AI_PRICE_INPUT_PER_MTOK=satu\nAI_PRICE_OUTPUT_PER_MTOK=5"));
    }
}
