<?php

declare(strict_types=1);

/**
 * Membuat draf dari template: render prompt, minta AI, lalu bungkus menjadi Draft.
 * Semua kebutuhannya (AI dan penomoran) dikirim dari luar lewat constructor.
 */
final class DraftGenerator
{
    public function __construct(
        private readonly AiProvider $ai,
        private readonly IdSequence $ids,
    ) {
    }

    /**
     * @param array<string, string> $values nilai untuk variabel template
     */
    public function generate(User $author, PromptTemplate $template, array $values): Draft
    {
        $prompt = $template->render($values);
        $type = $template->targetType;

        $result = $this->ai->generate($prompt, $type);
        $content = $type->createContent($result->title, $result->body);

        // Nomor baru diambil setelah semua langkah di atas berhasil,
        // supaya kegagalan tidak "menghabiskan" nomor.
        return new Draft(
            id: $this->ids->next(),
            authorId: $author->id,
            content: $content,
            prompt: $prompt,
            usage: $result->usage,
        );
    }
}
