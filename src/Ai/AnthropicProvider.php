<?php

declare(strict_types=1);

namespace KurasiAI\Ai;

use KurasiAI\Ai\Http\ApiResponse;
use KurasiAI\Ai\Http\HttpClient;
use KurasiAI\Ai\Http\NetworkException;
use KurasiAI\Domain\Content\Article;
use KurasiAI\Domain\Content\Caption;
use KurasiAI\Domain\Content\Content;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\Content\VideoScript;
use KurasiAI\Domain\Prompt;
use KurasiAI\Domain\TokenUsage;
use KurasiAI\Exception\AiProviderException;
use RuntimeException;

/**
 * Provider AI sungguhan: Claude API dari Anthropic (Messages API).
 * Semua kegagalan menjadi AiProviderException dengan pesan ramah untuk pengguna;
 * detail teknisnya ada di exception "previous" untuk dicatat di log.
 */
final class AnthropicProvider implements AiProvider
{
    public const API_URL = 'https://api.anthropic.com/v1/messages';
    public const API_VERSION = '2023-06-01';
    private const MAX_TOKENS = 1500;

    public function __construct(
        private readonly HttpClient $http,
        private readonly string $apiKey,
        private readonly string $model,
        private readonly int $timeoutSeconds = 30,
    ) {
    }

    public function name(): string
    {
        return 'anthropic';
    }

    public function generate(Prompt $prompt, ContentType $type): AiResult
    {
        try {
            $response = $this->http->postJson(
                self::API_URL,
                [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'anthropic-version' => self::API_VERSION,
                ],
                [
                    'model' => $this->model,
                    'max_tokens' => self::MAX_TOKENS,
                    'system' => $this->instructions($type),
                    'messages' => [['role' => 'user', 'content' => $prompt->text]],
                ],
                $this->timeoutSeconds,
            );
        } catch (NetworkException $e) {
            throw new AiProviderException('Mesin AI tidak bisa dihubungi. Periksa koneksi internet, lalu coba lagi.', previous: $e);
        }

        if ($response->status !== 200) {
            throw $this->errorFor($response);
        }

        $data = json_decode($response->body, true);

        if (!is_array($data)) {
            throw $this->unreadable('Isi jawaban bukan JSON', $response);
        }

        if (($data['stop_reason'] ?? null) === 'max_tokens') {
            throw new AiProviderException('Jawaban mesin AI terpotong karena terlalu panjang. Coba prompt yang lebih ringkas.');
        }

        [$title, $body] = $this->titleAndBody($this->text($data), $response);
        $usage = is_array($data['usage'] ?? null) ? $data['usage'] : [];

        return new AiResult(
            title: $title,
            body: $body,
            usage: new TokenUsage((int) ($usage['input_tokens'] ?? 0), (int) ($usage['output_tokens'] ?? 0)),
            providerName: $this->name(),
        );
    }

    /**
     * Petunjuk untuk AI: bentuk jawaban JSON, dan aturan jenis konten yang sama dengan class Content.
     */
    private function instructions(ContentType $type): string
    {
        $rule = match ($type) {
            ContentType::Article => 'body berupa artikel minimal ' . Article::MIN_BODY_LENGTH
                . ' karakter, terdiri dari beberapa paragraf yang dipisahkan baris kosong.',
            ContentType::VideoScript => 'body berupa naskah video; setiap adegan ditulis di barisnya sendiri, minimal '
                . VideoScript::MIN_SCENES . ' adegan.',
            ContentType::Caption => 'body berupa caption media sosial, maksimal ' . Caption::MAX_BODY_LENGTH
                . ' karakter dan maksimal ' . Caption::MAX_HASHTAGS . ' tagar.',
        };

        return 'Kamu adalah penulis konten berbahasa Indonesia. '
            . 'Jawab HANYA dengan satu objek JSON tanpa teks lain, berbentuk {"title": "...", "body": "..."}. '
            . 'title maksimal ' . Content::MAX_TITLE_LENGTH . ' karakter. ' . $rule;
    }

    /**
     * Menggabungkan semua blok teks di "content".
     *
     * @param array<mixed> $data
     */
    private function text(array $data): string
    {
        $text = '';

        foreach (is_array($data['content'] ?? null) ? $data['content'] : [] as $block) {
            if (is_array($block) && ($block['type'] ?? null) === 'text' && is_string($block['text'] ?? null)) {
                $text .= $block['text'];
            }
        }

        return $text;
    }

    /**
     * Mengambil title dan body dari teks jawaban. Pembungkus seperti ```json ... ``` diabaikan:
     * yang dibaca hanya bagian dari "{" pertama sampai "}" terakhir.
     *
     * @return array{string, string}
     */
    private function titleAndBody(string $text, ApiResponse $response): array
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        $json = $start === false || $end === false ? null : json_decode(substr($text, $start, $end - $start + 1), true);

        $title = is_array($json) && is_string($json['title'] ?? null) ? trim($json['title']) : '';
        $body = is_array($json) && is_string($json['body'] ?? null) ? trim($json['body']) : '';

        if ($title === '' || $body === '') {
            throw new AiProviderException(
                'Jawaban mesin AI tidak sesuai format yang diminta. Coba lagi.',
                previous: new RuntimeException('Tidak ada title/body yang sah dalam teks jawaban' . $this->requestIdOf($response)),
            );
        }

        return [$title, $body];
    }

    private function errorFor(ApiResponse $response): AiProviderException
    {
        $data = json_decode($response->body, true);
        $error = is_array($data) && is_array($data['error'] ?? null) ? $data['error'] : [];
        $type = is_string($error['type'] ?? null) ? $error['type'] : 'tidak diketahui';
        $message = is_string($error['message'] ?? null) ? $error['message'] : '';

        $userMessage = match (true) {
            $response->status === 401, $response->status === 403
                => 'API key ditolak layanan AI. Periksa AI_API_KEY di berkas .env.',
            $response->status === 402 => 'Saldo atau kuota akun layanan AI habis.',
            $response->status === 429 => 'Batas pemakaian layanan AI tercapai. Tunggu sebentar, lalu coba lagi.',
            $response->status >= 500 => 'Layanan AI sedang sibuk atau bermasalah. Coba lagi beberapa saat lagi.',
            default => 'Permintaan ke layanan AI ditolak. Periksa pengaturan AI_MODEL di berkas .env.',
        };

        // Detail untuk log pengembang: status, jenis error, pesan dari API, dan request-id.
        $detail = "HTTP {$response->status} $type" . ($message !== '' ? ": $message" : '') . $this->requestIdOf($response);

        return new AiProviderException($userMessage, previous: new RuntimeException($detail));
    }

    private function unreadable(string $reason, ApiResponse $response): AiProviderException
    {
        return new AiProviderException(
            'Jawaban mesin AI tidak bisa dibaca. Coba lagi.',
            previous: new RuntimeException($reason . $this->requestIdOf($response)),
        );
    }

    private function requestIdOf(ApiResponse $response): string
    {
        $id = $response->header('request-id');

        return $id === null ? '' : " (request-id: $id)";
    }
}
