<?php

declare(strict_types=1);

namespace KurasiAI\Domain\Content;

use KurasiAI\Exception\ValidationException;


final class Caption extends Content
{
    public const MAX_BODY_LENGTH = 2200;
    public const MAX_HASHTAGS = 30;

    public function __construct(string $title, string $body)
    {
        parent::__construct($title, $body);

        if (mb_strlen($this->body) > self::MAX_BODY_LENGTH) {
            throw new ValidationException('Caption maksimal ' . self::MAX_BODY_LENGTH . ' karakter.');
        }

        if (count($this->hashtags()) > self::MAX_HASHTAGS) {
            throw new ValidationException('Caption maksimal ' . self::MAX_HASHTAGS . ' tagar.');
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
