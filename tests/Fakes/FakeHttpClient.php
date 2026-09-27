<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Fakes;

use KurasiAI\Ai\Http\ApiResponse;
use KurasiAI\Ai\Http\HttpClient;
use KurasiAI\Ai\Http\NetworkException;

/**
 * HttpClient tiruan untuk tes: mengembalikan jawaban rekaman (atau melempar NetworkException)
 * dan mencatat setiap permintaan yang dikirim, tanpa pernah menyentuh internet.
 */
final class FakeHttpClient implements HttpClient
{
    /** @var list<array{url: string, headers: array<string, string>, body: array<string, mixed>, timeout: int}> */
    public array $requests = [];

    public function __construct(private readonly ApiResponse|NetworkException $answer)
    {
    }

    public function postJson(string $url, array $headers, array $body, int $timeoutSeconds): ApiResponse
    {
        $this->requests[] = ['url' => $url, 'headers' => $headers, 'body' => $body, 'timeout' => $timeoutSeconds];

        if ($this->answer instanceof NetworkException) {
            throw $this->answer;
        }

        return $this->answer;
    }

    /**
     * Jawaban sukses dengan bentuk yang sama seperti Messages API.
     */
    public static function success(string $text, string $stopReason = 'end_turn'): self
    {
        return new self(new ApiResponse(200, (string) json_encode([
            'type' => 'message',
            'id' => 'msg_tes',
            'content' => [['type' => 'text', 'text' => $text]],
            'stop_reason' => $stopReason,
            'usage' => ['input_tokens' => 42, 'output_tokens' => 180],
        ]), ['request-id' => 'req_tes_ok']));
    }

    public static function error(int $status, string $type, string $message): self
    {
        return new self(new ApiResponse($status, (string) json_encode([
            'type' => 'error',
            'error' => ['type' => $type, 'message' => $message],
            'request_id' => 'req_tes_err',
        ]), ['request-id' => 'req_tes_err']));
    }
}
