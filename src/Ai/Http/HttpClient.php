<?php

declare(strict_types=1);

namespace KurasiAI\Ai\Http;

/**
 * Pengirim permintaan HTTP. Provider AI hanya mengenal interface ini, sehingga di tes
 * bisa diganti FakeHttpClient yang mengembalikan jawaban rekaman tanpa internet.
 */
interface HttpClient
{
    /**
     * Mengirim POST berisi JSON.
     *
     * @param array<string, string> $headers
     * @param array<string, mixed> $body
     * @throws NetworkException bila tidak ada jawaban sama sekali
     */
    public function postJson(string $url, array $headers, array $body, int $timeoutSeconds): ApiResponse;
}
