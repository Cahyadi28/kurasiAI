<?php

declare(strict_types=1);

namespace KurasiAI\Http\Form;

use KurasiAI\Domain\Content\Content;
use KurasiAI\Domain\Draft;
use KurasiAI\Validation\MaxLength;
use KurasiAI\Validation\Required;

/**
 * Isian form edit draf. Aturan khas tiap jenis konten (misalnya artikel minimal 300 karakter)
 * tetap diperiksa objek Content, bukan di sini.
 */
final class DraftForm
{
    public function __construct(
        #[Required(message: 'Judul wajib diisi.'), MaxLength(Content::MAX_TITLE_LENGTH)]
        public readonly string $title,
        #[Required(message: 'Isi wajib diisi.')]
        public readonly string $body,
    ) {
    }

    /**
     * @param array<mixed> $input
     */
    public static function fromInput(array $input): self
    {
        return new self(FormInput::text($input, 'title'), FormInput::text($input, 'body'));
    }

    public static function fromDraft(Draft $draft): self
    {
        return new self($draft->content()->title, $draft->content()->body);
    }
}
