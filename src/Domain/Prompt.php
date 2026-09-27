<?php

declare(strict_types=1);

namespace KurasiAI\Domain;

use InvalidArgumentException;

/**
 * Value object: teks perintah yang dikirim ke AI.
 */
class Prompt
{
    public const MAX_LENGTH = 2000;

    public readonly string $text;

    public function __construct(string $text)
    {
        $text = trim($text);

        if ($text === '') {
            throw new InvalidArgumentException('Prompt tidak boleh kosong.');
        }

        if (mb_strlen($text) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Prompt maksimal ' . self::MAX_LENGTH . ' karakter.');
        }

        $this->text = $text;
    }
}
