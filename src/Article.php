<?php

declare(strict_types=1);

final class Article extends Content
{
    public const MIN_BODY_LENGTH = 300;
    public const WORDS_PER_MINUTE = 200;

    public function __construct(string $title, string $body)
    {
        parent::__construct($title, $body);

        if (mb_strlen($this->body) < self::MIN_BODY_LENGTH) {
            throw new InvalidArgumentException('Isi artikel minimal ' . self::MIN_BODY_LENGTH . ' karakter.');
        }
    }

    public function type(): ContentType
    {
        return ContentType::Article;
    }

    /**
     * Perkiraan lama membaca. Minimal 1 menit.
     */
    public function readingMinutes(): int
    {
        return max(1, (int) ceil($this->wordCount() / self::WORDS_PER_MINUTE));
    }

    public function metaLabel(): string
    {
        return "±{$this->readingMinutes()} menit baca";
    }
}
