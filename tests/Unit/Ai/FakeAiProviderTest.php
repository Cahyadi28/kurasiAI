<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Ai;

use KurasiAI\Ai\FakeAiProvider;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\Prompt;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FakeAiProviderTest extends TestCase
{
    public function testSameInputGivesTheSameResult(): void
    {
        $ai = new FakeAiProvider();
        $prompt = new Prompt('Tulis artikel tentang energi surya.');

        $first = $ai->generate($prompt, ContentType::Article);
        $second = $ai->generate($prompt, ContentType::Article);

        $this->assertEquals($first, $second);
    }

    public function testTakesTheTopicAfterTheWordTentang(): void
    {
        $result = (new FakeAiProvider())->generate(
            new Prompt('Tulis artikel singkat tentang energi surya untuk pelajar.'),
            ContentType::Article,
        );

        $this->assertSame('Energi surya untuk pelajar', $result->title);
        $this->assertSame('fake', $result->providerName);
    }

    public function testEstimatesTokensAsOnePerFourCharacters(): void
    {
        $prompt = new Prompt('Buat caption tentang air.'); // 25 karakter

        $result = (new FakeAiProvider())->generate($prompt, ContentType::Caption);

        $this->assertSame(7, $result->usage->input);
        $this->assertSame((int) ceil(mb_strlen($result->body) / 4), $result->usage->output);
    }

    /**
     * @return array<string, array{ContentType}>
     */
    public static function allContentTypes(): array
    {
        $cases = [];

        foreach (ContentType::cases() as $type) {
            $cases[$type->value] = [$type];
        }

        return $cases;
    }

    #[DataProvider('allContentTypes')]
    public function testResultPassesTheContentRules(ContentType $type): void
    {
        $result = (new FakeAiProvider())->generate(new Prompt('Tulis tentang hemat air.'), $type);

        $content = $type->createContent($result->title, $result->body);

        $this->assertSame($type, $content->type());
    }
}
