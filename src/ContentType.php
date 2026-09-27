<?php

declare(strict_types=1);

/**
 * Jenis konten yang didukung KurasiAI.
 */
enum ContentType: string
{
    case Article = 'article';
    case VideoScript = 'video_script';
    case Caption = 'caption';

    public function label(): string
    {
        return match ($this) {
            self::Article => 'Artikel',
            self::VideoScript => 'Naskah Video',
            self::Caption => 'Caption',
        };
    }

    /**
     * Membuat object konten dari class yang sesuai dengan jenis ini.
     */
    public function createContent(string $title, string $body): Content
    {
        return match ($this) {
            self::Article => new Article($title, $body),
            self::VideoScript => new VideoScript($title, $body),
            self::Caption => new Caption($title, $body),
        };
    }
}
