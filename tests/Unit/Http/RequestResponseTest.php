<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Http;

use KurasiAI\Http\Request;
use KurasiAI\Http\Response;
use PHPUnit\Framework\TestCase;

class RequestResponseTest extends TestCase
{
    /** @var array<mixed> */
    private array $server;

    protected function setUp(): void
    {
        $this->server = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_GET = [];
        $_POST = [];
    }

    public function testRequestFromGlobals(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'post';
        $_SERVER['REQUEST_URI'] = '/drafts/12/submit?dari=beranda';
        $_GET = ['dari' => 'beranda', 'daftar' => ['a', 'b']];
        $_POST = ['_csrf' => 'abc'];

        $request = Request::fromGlobals();

        $this->assertSame('POST', $request->method);
        $this->assertSame('/drafts/12/submit', $request->path, 'Bagian ?... bukan bagian dari path.');
        $this->assertTrue($request->isPost());
        $this->assertSame('beranda', $request->query('dari'));
        $this->assertNull($request->query('daftar'), 'Nilai berupa array dianggap tidak ada.');
        $this->assertNull($request->query('tidak-ada'));
        $this->assertSame(['_csrf' => 'abc'], $request->post());
    }

    public function testRedirectUses303(): void
    {
        $response = Response::redirect('/drafts/3');

        $this->assertSame(303, $response->status);
        $this->assertSame('/drafts/3', $response->header('Location'));
        $this->assertSame('', $response->body);
    }

    public function testWithHeaderReturnsANewResponse(): void
    {
        $original = new Response('isi');
        $changed = $original->withHeader('X-Frame-Options', 'DENY');

        $this->assertNull($original->header('X-Frame-Options'));
        $this->assertSame('DENY', $changed->header('X-Frame-Options'));
        $this->assertSame('text/html; charset=utf-8', $changed->header('Content-Type'));
    }
}
