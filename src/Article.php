<?php

declare(strict_types=1);

final class Article extends Content
{
    public function __construct(string $title, string $body)
    {
        parent::__construct($title, $body);

        if (mb_strlen($this->body) < 300) {
            throw new InvalidArgumentException('Isi artikel minimal 300 karakter.');
        }
    }

    public function type(): ContentType
    {
        return ContentType::Article;
    }

    /**
     * Perkiraan lama membaca, dengan anggapan 200 kata per menit. Minimal 1 menit.
     */
    public function readingMinutes(): int
    {
        return max(1, (int) ceil($this->wordCount() / 200));
    }

    public function metaLabel(): string
    {
        return "±{$this->readingMinutes()} menit baca";
    }
}
