<?php

declare(strict_types=1);

final class VideoScript extends Content
{
    public function __construct(string $title, string $body)
    {
        parent::__construct($title, $body);

        if (count($this->scenes()) < 2) {
            throw new InvalidArgumentException('Naskah video minimal 2 adegan (satu adegan per baris).');
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
     * Perkiraan durasi, dengan anggapan pembawa acara berbicara 150 kata per menit.
     */
    public function estimatedSeconds(): int
    {
        return (int) ceil($this->wordCount() / 150 * 60);
    }

    public function metaLabel(): string
    {
        return count($this->scenes()) . " adegan, ±{$this->estimatedSeconds()} detik";
    }
}
