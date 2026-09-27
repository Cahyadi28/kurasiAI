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
}
