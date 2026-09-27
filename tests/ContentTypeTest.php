<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ContentTypeTest extends TestCase
{
    /**
     * @return array<string, array{ContentType, class-string<Content>, string}>
     */
    public static function typesAndClasses(): array
    {
        return [
            'artikel' => [ContentType::Article, Article::class, str_repeat('Isi artikel. ', 30)],
            'naskah video' => [ContentType::VideoScript, VideoScript::class, "Adegan satu.\nAdegan dua."],
            'caption' => [ContentType::Caption, Caption::class, 'Isi caption. #tagar'],
        ];
    }

    /**
     * @param class-string<Content> $expectedClass
     */
    #[DataProvider('typesAndClasses')]
    public function testCreatesTheMatchingContentClass(ContentType $type, string $expectedClass, string $body): void
    {
        $content = $type->createContent('Judul', $body);

        $this->assertInstanceOf($expectedClass, $content);
    }
}
