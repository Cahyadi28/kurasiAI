<?php

declare(strict_types=1);

namespace KurasiAI\Http\Form;

use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\Prompt;
use KurasiAI\Domain\PromptTemplate;
use KurasiAI\Validation\MaxLength;
use KurasiAI\Validation\OneOf;
use KurasiAI\Validation\Required;

/**
 * Isian form template prompt. Aturan bentuk input ditulis sebagai attribute;
 * batas panjangnya memakai konstanta yang sama dengan objek domain.
 */
final class TemplateForm
{
    public function __construct(
        #[Required, MaxLength(PromptTemplate::MAX_NAME_LENGTH)]
        public readonly string $name,
        #[Required, MaxLength(Prompt::MAX_LENGTH)]
        public readonly string $text,
        #[Required(message: 'Pilih jenis konten.'), OneOf(ContentType::class)]
        public readonly string $targetType,
    ) {
    }

    /**
     * Dari data kiriman form ($_POST). Nilai yang bukan teks (misalnya array) dianggap kosong.
     *
     * @param array<mixed> $input
     */
    public static function fromInput(array $input): self
    {
        return new self(
            name: FormInput::text($input, 'name'),
            text: FormInput::text($input, 'text'),
            targetType: FormInput::text($input, 'target_type'),
        );
    }

    public static function fromTemplate(PromptTemplate $template): self
    {
        return new self($template->name(), $template->text(), $template->targetType->value);
    }

    public static function empty(): self
    {
        return new self('', '', '');
    }

    /** Panggil hanya setelah lolos validasi. */
    public function contentType(): ContentType
    {
        return ContentType::from($this->targetType);
    }
}
