<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit;

use KurasiAI\Ai\AnthropicProvider;
use KurasiAI\Ai\FakeAiProvider;
use KurasiAI\App;
use KurasiAI\Exception\ConfigException;
use KurasiAI\Support\Env;
use PHPUnit\Framework\TestCase;

class AppProviderTest extends TestCase
{
    public function testFakeIsTheDefault(): void
    {
        $this->assertInstanceOf(FakeAiProvider::class, $this->app('')->aiProvider());
    }

    public function testAnthropicNeedsAnApiKey(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('AI_API_KEY di .env wajib diisi.');

        $this->app("AI_PROVIDER=anthropic\nAI_MODEL=model-tes\nAI_API_KEY=")->aiProvider();
    }

    public function testAnthropicWithKeyAndModel(): void
    {
        $provider = $this->app("AI_PROVIDER=anthropic\nAI_MODEL=model-tes\nAI_API_KEY=kunci-palsu")->aiProvider();

        $this->assertInstanceOf(AnthropicProvider::class, $provider);
    }

    public function testUnknownProviderIsAConfigurationError(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('AI_PROVIDER "gpt" tidak dikenal. Pilihan: fake, anthropic.');

        $this->app('AI_PROVIDER=gpt')->aiProvider();
    }

    public function testTimeoutMustBeAPositiveNumber(): void
    {
        $this->expectException(ConfigException::class);

        $this->app("AI_PROVIDER=anthropic\nAI_MODEL=model-tes\nAI_API_KEY=kunci-palsu\nAI_TIMEOUT=0")->aiProvider();
    }

    private function app(string $env): App
    {
        return App::fromEnv(Env::fromString($env), dirname(__DIR__, 2));
    }
}
