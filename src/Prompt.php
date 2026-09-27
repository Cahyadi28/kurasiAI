<?php

declare(strict_types=1);

/**
 * Value object: teks perintah yang dikirim ke AI.
 */
class Prompt
{
    public readonly string $text;

    public function __construct(string $text)
    {
        $text = trim($text);

        if ($text === '') {
            throw new InvalidArgumentException('Prompt tidak boleh kosong.');
        }

        if (mb_strlen($text) > 2000) {
            throw new InvalidArgumentException('Prompt maksimal 2000 karakter.');
        }

        $this->text = $text;
    }
}
