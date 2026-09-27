<?php

declare(strict_types=1);

namespace KurasiAI\Ai\Http;

/**
 * HttpClient memakai ekstensi curl bawaan PHP, tanpa library tambahan.
 */
final class CurlHttpClient implements HttpClient
{
    public function postJson(string $url, array $headers, array $body, int $timeoutSeconds): ApiResponse
    {
        $lines = ['Content-Type: application/json', 'Accept: application/json'];

        foreach ($headers as $name => $value) {
            $lines[] = "$name: $value";
        }

        $responseHeaders = [];
        $handle = curl_init($url);

        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => $lines,
            CURLOPT_RETURNTRANSFER => true,
            // Batas waktu: menyambung paling lama 10 detik, seluruh permintaan paling lama $timeoutSeconds.
            CURLOPT_CONNECTTIMEOUT => min(10, $timeoutSeconds),
            CURLOPT_TIMEOUT => $timeoutSeconds,
            // Sertifikat HTTPS selalu diperiksa. Jangan pernah dimatikan untuk "mengatasi" error SSL.
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HEADERFUNCTION => function ($handle, string $line) use (&$responseHeaders): int {
                $parts = explode(':', $line, 2);

                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }

                return strlen($line);
            },
        ]);

        $result = curl_exec($handle);

        if (!is_string($result)) {
            // Pesan curl hanya menyebut alamat dan penyebabnya, tidak pernah header (tempat API key).
            throw new NetworkException(
                'Tidak ada jawaban dari ' . (parse_url($url, PHP_URL_HOST) ?: $url) . ': ' . curl_error($handle),
                curl_errno($handle),
            );
        }

        return new ApiResponse((int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), $result, $responseHeaders);
    }
}
