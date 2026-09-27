<?php

declare(strict_types=1);

namespace KurasiAI\Domain\Content;

use InvalidArgumentException;

final class VideoScript extends Content
{
    public const MIN_SCENES = 2;
    public const WORDS_PER_MINUTE = 150;

    public function __construct(string $title, string $body)
    {
        parent::__construct($title, $body);

        if (count($this->scenes()) < self::MIN_SCENES) {
            throw new InvalidArgumentException(
                'Naskah video minimal ' . self::MIN_SCENES . ' adegan (satu adegan per baris).'
            );
        }
    }

    public function type(): ContentType
    {
        return ContentType::VideoScript;
    }

    /**
     * Setiap baris yang tidak kosong dihitung sebagai satu adegan.
     *
     * @return list<string>
     */
    public function scenes(): array
    {
        $lines = array_map('trim', explode("\n", $this->body));

        return array_values(array_filter($lines, fn (string $line): bool => $line !== ''));
    }

    /**
     * Perkiraan durasi berdasarkan kecepatan bicara pembawa acara.
     */
    public function estimatedSeconds(): int
    {
        return (int) ceil($this->wordCount() / self::WORDS_PER_MINUTE * 60);
    }

    public function metaLabel(): string
    {
        return count($this->scenes()) . " adegan, ±{$this->estimatedSeconds()} detik";
    }
}
