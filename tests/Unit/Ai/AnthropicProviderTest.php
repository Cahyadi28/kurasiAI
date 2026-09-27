<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Ai;

use KurasiAI\Ai\AnthropicProvider;
use KurasiAI\Ai\Http\ApiResponse;
use KurasiAI\Ai\Http\NetworkException;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\Prompt;
use KurasiAI\Exception\AiProviderException;
use KurasiAI\Tests\Fakes\FakeHttpClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AnthropicProviderTest extends TestCase
{
    /** Key palsu untuk tes; tes memastikan key ini tidak pernah muncul di pesan kesalahan. */
    private const API_KEY = 'sk-ant-kunci-palsu-untuk-tes';

    public function testSendsAWellFormedRequest(): void
    {
        $http = FakeHttpClient::success('{"title": "Hemat Air", "body": "Tutup keran. #hematair"}');

        $this->provider($http)->generate(new Prompt('Buat caption tentang hemat air.'), ContentType::Caption);

        $request = $http->requests[0];
        $this->assertSame('https://api.anthropic.com/v1/messages', $request['url']);
        $this->assertSame('Bearer ' . self::API_KEY, $request['headers']['Authorization']);
        $this->assertSame('2023-06-01', $request['headers']['anthropic-version']);
        $this->assertSame('model-tes', $request['body']['model']);
        $this->assertSame([['role' => 'user', 'content' => 'Buat caption tentang hemat air.']], $request['body']['messages']);
        $this->assertIsString($request['body']['system']);
        $this->assertStringContainsString('maksimal 2200 karakter', $request['body']['system'], 'Aturan Caption ikut dikirim.');
        $this->assertSame(20, $request['timeout']);
    }

    public function testReadsTitleBodyAndUsage(): void
    {
        $result = $this->provider(FakeHttpClient::success('{"title": " Hemat Air ", "body": "Tutup keran. #hematair"}'))
            ->generate(new Prompt('Buat caption tentang hemat air.'), ContentType::Caption);

        $this->assertSame('Hemat Air', $result->title);
        $this->assertSame('Tutup keran. #hematair', $result->body);
        $this->assertSame([42, 180], [$result->usage->input, $result->usage->output]);
        $this->assertSame('anthropic', $result->providerName);
    }

    public function testJsonWrappedInACodeFenceIsStillRead(): void
    {
        $text = "Berikut hasilnya:\n```json\n{\"title\": \"Kompos\", \"body\": \"Sisa dapur jadi pupuk.\"}\n```";

        $result = $this->provider(FakeHttpClient::success($text))->generate(new Prompt('Buat caption.'), ContentType::Caption);

        $this->assertSame('Kompos', $result->title);
    }

    /**
     * @return array<string, array{FakeHttpClient, string}>
     */
    public static function failures(): array
    {
        return [
            'key salah' => [FakeHttpClient::error(401, 'authentication_error', 'invalid x-api-key'), 'API key ditolak'],
            'tanpa izin' => [FakeHttpClient::error(403, 'permission_error', 'no access'), 'API key ditolak'],
            'saldo habis' => [FakeHttpClient::error(402, 'billing_error', 'credit balance too low'), 'Saldo atau kuota'],
            'batas pemakaian' => [FakeHttpClient::error(429, 'rate_limit_error', 'slow down'), 'Batas pemakaian'],
            'server error' => [FakeHttpClient::error(500, 'api_error', 'oops'), 'sedang sibuk atau bermasalah'],
            'kelebihan beban' => [FakeHttpClient::error(529, 'overloaded_error', 'overloaded'), 'sedang sibuk atau bermasalah'],
            'model salah' => [FakeHttpClient::error(404, 'not_found_error', 'model: model-tes'), 'Periksa pengaturan AI_MODEL'],
            'jaringan putus' => [new FakeHttpClient(new NetworkException('Tidak ada jawaban dari api.anthropic.com: timeout')), 'tidak bisa dihubungi'],
            'bukan JSON' => [new FakeHttpClient(new ApiResponse(200, '<html>Bad gateway</html>')), 'tidak bisa dibaca'],
            'teks tanpa JSON' => [FakeHttpClient::success('Maaf, saya tidak bisa membantu.'), 'tidak sesuai format'],
            'title kosong' => [FakeHttpClient::success('{"title": "", "body": "Isi."}'), 'tidak sesuai format'],
            'terpotong' => [FakeHttpClient::success('{"title": "Panjang", "body": "Isi yang ter', 'max_tokens'), 'terpotong'],
        ];
    }

    #[DataProvider('failures')]
    public function testFailuresBecomeFriendlyAiProviderExceptions(FakeHttpClient $http, string $expectedMessage): void
    {
        try {
            $this->provider($http)->generate(new Prompt('Buat caption.'), ContentType::Caption);
            $this->fail('Seharusnya gagal.');
        } catch (AiProviderException $e) {
            $this->assertStringContainsString($expectedMessage, $e->getMessage());

            // API key tidak pernah ikut di pesan untuk pengguna maupun di detail untuk log.
            $this->assertStringNotContainsString(self::API_KEY, $e->getMessage());
            $this->assertStringNotContainsString(self::API_KEY, (string) $e->getPrevious()?->getMessage());
        }
    }

    public function testErrorDetailIsKeptForTheLog(): void
    {
        try {
            $this->provider(FakeHttpClient::error(429, 'rate_limit_error', 'slow down'))
                ->generate(new Prompt('Buat caption.'), ContentType::Caption);
            $this->fail('Seharusnya gagal.');
        } catch (AiProviderException $e) {
            $this->assertSame('HTTP 429 rate_limit_error: slow down (request-id: req_tes_err)', $e->getPrevious()?->getMessage());
        }
    }

    private function provider(FakeHttpClient $http): AnthropicProvider
    {
        return new AnthropicProvider($http, self::API_KEY, 'model-tes', 20);
    }
}
