<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ArticleTest extends TestCase
{
    public function testHasArticleType(): void
    {
        $article = new Article('Energi surya', str_repeat('kata ', 61));

        $this->assertSame(ContentType::Article, $article->type());
    }

    public function testRejectsBodyShorterThan300Characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Isi artikel minimal 300 karakter.');

        new Article('Energi surya', str_repeat('a', 299));
    }

    public function testAcceptsExactly300Characters(): void
    {
        $article = new Article('Energi surya', str_repeat('a', 300));

        $this->assertSame(300, mb_strlen($article->body));
    }

    public function testEstimatesReadingTimeAt200WordsPerMinute(): void
    {
        $short = new Article('Pendek', trim(str_repeat('kata ', 150)));
        $long = new Article('Panjang', trim(str_repeat('kata ', 401)));

        $this->assertSame(1, $short->readingMinutes());
        $this->assertSame(3, $long->readingMinutes());
        $this->assertSame('±3 menit baca', $long->metaLabel());
    }
}
