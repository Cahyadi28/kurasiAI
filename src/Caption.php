<?php

declare(strict_types=1);

final class Caption extends Content
{
    public function __construct(string $title, string $body)
    {
        parent::__construct($title, $body);

        if (mb_strlen($this->body) > 2200) {
            throw new InvalidArgumentException('Caption maksimal 2200 karakter.');
        }

        if (count($this->hashtags()) > 30) {
            throw new InvalidArgumentException('Caption maksimal 30 tagar.');
        }
    }

    public function type(): ContentType
    {
        return ContentType::Caption;
    }

    /**
     * Semua kata yang diawali tanda #.
     *
     * @return list<string>
     */
    public function hashtags(): array
    {
        return array_values(array_filter(
            $this->words(),
            fn (string $word): bool => str_starts_with($word, '#'),
        ));
    }

    public function metaLabel(): string
    {
        return count($this->hashtags()) . ' tagar';
    }
}
