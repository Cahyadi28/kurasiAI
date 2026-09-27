<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Integration\Ai;

use KurasiAI\Ai\Http\CurlHttpClient;
use KurasiAI\Ai\Http\NetworkException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * CurlHttpClient diuji terhadap server PHP kecil di komputer sendiri (php -S),
 * bukan layanan sungguhan: cepat, gratis, dan tetap berjalan tanpa internet.
 */
class CurlHttpClientTest extends TestCase
{
    /** @var resource|null */
    private static $server = null;
    private static int $port = 0;

    public static function setUpBeforeClass(): void
    {
        self::$port = self::freePort();
        $fixture = dirname(__DIR__, 2) . '/Fixtures/http/server.php';
        $command = [PHP_BINARY, '-S', '127.0.0.1:' . self::$port, $fixture];
        $null = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
        $process = proc_open($command, [1 => ['file', $null, 'w'], 2 => ['file', $null, 'w']], $pipes);
        self::$server = is_resource($process) ? $process : null;

        // Tunggu sampai server siap menerima sambungan (paling lama sekitar 3 detik).
        for ($i = 0; $i < 30; $i++) {
            $socket = @fsockopen('127.0.0.1', self::$port);

            if ($socket !== false) {
                fclose($socket);

                return;
            }

            usleep(100_000);
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$server !== null) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
    }

    public function testSendsJsonAndHeadersAndReadsTheAnswer(): void
    {
        $response = (new CurlHttpClient())->postJson(
            $this->url('/v1/messages'),
            ['Authorization' => 'Bearer kunci-palsu'],
            ['model' => 'model-tes', 'pesan' => 'Halo, dunia'],
            5,
        );

        $this->assertSame(200, $response->status);
        $this->assertSame('req_lokal_123', $response->header('Request-Id'), 'Nama header tidak peka huruf besar-kecil.');
        $this->assertSame([
            'method' => 'POST',
            'authorization' => 'Bearer kunci-palsu',
            'content_type' => 'application/json',
            'body' => ['model' => 'model-tes', 'pesan' => 'Halo, dunia'],
        ], json_decode($response->body, true));
    }

    public function testErrorStatusIsStillAResponse(): void
    {
        $response = (new CurlHttpClient())->postJson($this->url('/batas'), [], [], 5);

        $this->assertSame(429, $response->status);
    }

    public function testTimeoutBecomesNetworkException(): void
    {
        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Tidak ada jawaban dari 127.0.0.1');

        (new CurlHttpClient())->postJson($this->url('/lambat'), [], [], 1);
    }

    public function testUnreachableServerBecomesNetworkException(): void
    {
        $this->expectException(NetworkException::class);

        (new CurlHttpClient())->postJson('http://127.0.0.1:' . self::freePort() . '/', [], [], 2);
    }

    private function url(string $path): string
    {
        return 'http://127.0.0.1:' . self::$port . $path;
    }

    /** Nomor port yang sedang tidak dipakai, dipilih oleh sistem operasi. */
    private static function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');

        if ($socket === false) {
            throw new RuntimeException('Tidak bisa memilih port untuk server tes.');
        }
        $name = (string) stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr($name, (int) strrpos($name, ':') + 1);
    }
}
