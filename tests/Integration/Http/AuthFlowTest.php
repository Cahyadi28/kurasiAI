<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Integration\Http;

class AuthFlowTest extends HttpTestCase
{
    public function testGuestIsSentToTheLoginPage(): void
    {
        $this->assertRedirect('/login', $this->get('/'));

        $page = $this->get('/login');

        $this->assertSame(200, $page->status);
        $this->assertStringContainsString('Silakan masuk dulu.', $page->body);
    }

    public function testWrongPasswordShowsOneGenericMessage(): void
    {
        $response = $this->post('/login', ['email' => 'rina@kurasiai.test', 'password' => 'tebakan']);

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('Email atau password salah.', $response->body);
        $this->assertStringNotContainsString('tebakan', $response->body, 'Password tidak dikirim balik.');
    }

    public function testLoginAndLogout(): void
    {
        $this->loginAs('rina@kurasiai.test');

        $home = $this->get('/');
        $this->assertSame(200, $home->status);
        $this->assertStringContainsString('Selamat datang, Rina.', $home->body);

        $this->assertRedirect('/login', $this->post('/logout'));
        $this->assertRedirect('/login', $this->get('/'));
    }

    public function testLogoutOnlyWithPost(): void
    {
        $response = $this->get('/logout');

        $this->assertSame(405, $response->status);
        $this->assertSame('POST', $response->header('Allow'));
    }

    public function testFormsWithoutCsrfTokenAreRejected(): void
    {
        $response = $this->post('/login', ['email' => 'rina@kurasiai.test', 'password' => self::PASSWORD], withToken: false);

        $this->assertSame(403, $response->status);
    }

    public function testEveryResponseHasSecurityHeaders(): void
    {
        $response = $this->get('/login');

        $this->assertSame("default-src 'self'", $response->header('Content-Security-Policy'));
        $this->assertSame('DENY', $response->header('X-Frame-Options'));
        $this->assertSame('nosniff', $response->header('X-Content-Type-Options'));
    }
}
