<?php

declare(strict_types=1);

namespace KurasiAI\Service;

use KurasiAI\Ai\AiProvider;
use KurasiAI\Domain\Draft;
use KurasiAI\Domain\PromptTemplate;
use KurasiAI\Domain\User;
use KurasiAI\Persistence\DraftRepository;
use KurasiAI\Support\Clock;

/**
 * Membuat draf dari template: render prompt, minta AI, lalu simpan sebagai Draft baru.
 * Semua kebutuhannya (AI, penyimpanan, dan jam) dikirim dari luar lewat constructor.
 */
final class DraftGenerator
{
    public function __construct(
        private readonly AiProvider $ai,
        private readonly DraftRepository $drafts,
        private readonly Clock $clock,
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

        // Draf baru disimpan setelah semua langkah di atas berhasil, sehingga kegagalan
        // tidak meninggalkan baris apa pun. Nomornya diberikan tempat penyimpanan.
        return $this->drafts->add(
            authorId: $author->id,
            content: $content,
            prompt: $prompt,
            createdAt: $this->clock->now(),
            source: $result,
        );
    }
}
