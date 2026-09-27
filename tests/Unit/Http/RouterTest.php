<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Http;

use KurasiAI\Http\HttpException;
use KurasiAI\Http\Request;
use KurasiAI\Http\Response;
use KurasiAI\Http\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->router->get('/', fn (): Response => new Response('beranda'));
        $this->router->get('/drafts/{id}', fn (Request $r, array $p): Response => new Response('lihat ' . $p['id']));
        $this->router->post('/drafts/{id}/submit', fn (Request $r, array $p): Response => new Response('kirim ' . $p['id']));
        $this->router->get('/templates/new', fn (): Response => new Response('template baru'));
    }

    public function testMatchesStaticPaths(): void
    {
        $this->assertSame('beranda', $this->dispatch('GET', '/')->body);
        $this->assertSame('template baru', $this->dispatch('GET', '/templates/new')->body);
    }

    public function testPassesNumericParametersAsInt(): void
    {
        $this->assertSame('lihat 12', $this->dispatch('GET', '/drafts/12')->body);
        $this->assertSame('kirim 7', $this->dispatch('POST', '/drafts/7/submit')->body);
    }

    public function testParametersAcceptDigitsOnly(): void
    {
        foreach (['/drafts/abc', '/drafts/1a', '/drafts/-1', '/drafts/1234567890', '/drafts/12/'] as $path) {
            try {
                $this->dispatch('GET', $path);
                $this->fail("$path seharusnya tidak cocok.");
            } catch (HttpException $e) {
                $this->assertSame(404, $e->status, $path);
            }
        }
    }

    public function testWrongMethodGives405WithAllowHeader(): void
    {
        try {
            $this->dispatch('GET', '/drafts/7/submit');
            $this->fail('GET ke rute POST seharusnya ditolak.');
        } catch (HttpException $e) {
            $this->assertSame(405, $e->status);
            $this->assertSame(['Allow' => 'POST'], $e->headers);
        }
    }

    public function testUnknownPathGives404(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Halaman tidak ditemukan.');

        $this->dispatch('GET', '/admin');
    }

    private function dispatch(string $method, string $path): Response
    {
        return $this->router->dispatch(new Request($method, $path));
    }
}
