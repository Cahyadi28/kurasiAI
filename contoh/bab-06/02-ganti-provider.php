<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

/**
 * Provider kedua yang sangat sederhana: hanya mengulang prompt.
 * Cukup memenuhi kontrak AiProvider, maka ia bisa dipakai di mana pun.
 */
final class EchoAiProvider implements AiProvider
{
    public function name(): string
    {
        return 'echo';
    }

    public function generate(Prompt $prompt, ContentType $type): AiResult
    {
        $body = match ($type) {
            ContentType::VideoScript => "Adegan 1: {$prompt->text}\nAdegan 2: {$prompt->text}",
            default => str_repeat($prompt->text . ' ', 8),
        };

        return new AiResult(
            title: 'Gema: ' . mb_substr($prompt->text, 0, 30),
            body: $body,
            usage: new TokenUsage(input: 0, output: 0),
            providerName: $this->name(),
        );
    }
}

// Fungsi ini hanya mengenal interface AiProvider, bukan class tertentu.
function describe(AiProvider $ai): void
{
    $prompt = new Prompt('Tulis artikel tentang energi surya di rumah.');
    $result = $ai->generate($prompt, ContentType::Article);
    $content = ContentType::Article->createContent($result->title, $result->body);

    echo "[{$ai->name()}] {$content->title} ({$content->metaLabel()})" . PHP_EOL;
}

describe(new FakeAiProvider());
describe(new EchoAiProvider());
