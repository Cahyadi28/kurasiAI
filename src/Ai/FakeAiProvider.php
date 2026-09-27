<?php

declare(strict_types=1);

namespace KurasiAI\Ai;

use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\Prompt;
use KurasiAI\Domain\TokenUsage;

/**
 * AI tiruan. Tidak butuh internet atau API key, dan hasilnya selalu sama
 * untuk prompt dan jenis konten yang sama, sehingga mudah diuji.
 */
final class FakeAiProvider implements AiProvider
{
    public function name(): string
    {
        return 'fake';
    }

    public function generate(Prompt $prompt, ContentType $type): AiResult
    {
        $topic = $this->topicFrom($prompt);

        $body = match ($type) {
            ContentType::Article => $this->article($topic),
            ContentType::VideoScript => $this->videoScript($topic),
            ContentType::Caption => $this->caption($topic),
        };

        return new AiResult(
            title: ucfirst($topic),
            body: $body,
            usage: new TokenUsage(
                input: $this->estimateTokens($prompt->text),
                output: $this->estimateTokens($body),
            ),
            providerName: $this->name(),
        );
    }

    /**
     * Topik diambil dari teks setelah kata "tentang", misalnya
     * "Tulis artikel tentang energi surya." menjadi "energi surya".
     */
    private function topicFrom(Prompt $prompt): string
    {
        $text = $prompt->text;
        $position = mb_stripos($text, 'tentang ');

        if ($position !== false) {
            $text = mb_substr($text, $position + mb_strlen('tentang '));
        }

        $topic = rtrim(mb_substr($text, 0, 60), ' .,!?');

        return $topic === '' ? 'topik pilihan' : $topic;
    }

    private function article(string $topic): string
    {
        return ucfirst($topic) . ' menjadi topik yang semakin sering dibicarakan. '
            . "Artikel ini membahas apa itu $topic, mengapa hal ini penting dalam kehidupan sehari-hari, "
            . 'dan bagaimana cara memulainya dengan langkah kecil. '
            . 'Pertama, pahami dasarnya dari sumber yang tepercaya. '
            . 'Kedua, coba terapkan sedikit demi sedikit. '
            . 'Ketiga, bagikan pengalaman Anda supaya manfaatnya makin luas.';
    }

    private function videoScript(string $topic): string
    {
        return "Pembuka: sapa penonton dan tanyakan apakah mereka pernah mendengar tentang $topic.\n"
            . "Isi: jelaskan tiga hal penting tentang $topic dengan contoh sehari-hari.\n"
            . 'Penutup: ajak penonton mencoba dan menulis pendapatnya di kolom komentar.';
    }

    private function caption(string $topic): string
    {
        $tag = preg_replace('/[^a-z0-9]+/', '', strtolower($topic));

        return "Sudah tahu soal $topic? Simpan postingan ini dan bagikan ke temanmu! "
            . '#belajar #' . ($tag === '' || $tag === null ? 'kurasiai' : $tag);
    }

    /**
     * Perkiraan kasar: satu token kira-kira empat karakter.
     */
    private function estimateTokens(string $text): int
    {
        return (int) ceil(mb_strlen($text) / 4);
    }
}
