<?php

declare(strict_types=1);

use KurasiAI\Ai\AiProvider;
use KurasiAI\Ai\AiResult;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\Prompt;
use KurasiAI\Exception\AiProviderException;

require_once __DIR__ . '/../../vendor/autoload.php';

// Provider tiruan yang selalu gagal, seperti server AI yang tidak menjawab.
final class TimeoutAiProvider implements AiProvider
{
    public function name(): string
    {
        return 'timeout';
    }

    public function generate(Prompt $prompt, ContentType $type): AiResult
    {
        throw new RuntimeException('Connection timed out after 30 seconds');
    }
}

function generateSafely(AiProvider $ai, Prompt $prompt): AiResult
{
    try {
        return $ai->generate($prompt, ContentType::Caption);
    } catch (RuntimeException $e) {
        // Bungkus: pesan ramah untuk pengguna, penyebab teknis tetap disimpan.
        throw new AiProviderException('Mesin AI sedang tidak bisa dihubungi. Coba lagi nanti.', previous: $e);
    }
}

try {
    generateSafely(new TimeoutAiProvider(), new Prompt('Buat caption tentang hemat air.'));
} catch (AiProviderException $e) {
    echo 'Untuk pengguna  : ' . $e->getMessage() . PHP_EOL;
    echo 'Untuk pengembang: ' . $e->getPrevious()?->getMessage() . PHP_EOL;
} finally {
    echo 'finally: selalu dijalankan, berhasil atau gagal.' . PHP_EOL;
}
