<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Http;

use KurasiAI\Exception\AccessDeniedException;
use KurasiAI\Http\Csrf;
use KurasiAI\Http\Session;
use PHPUnit\Framework\TestCase;

class SessionCsrfTest extends TestCase
{
    public function testSessionWritesIntoTheOriginalArray(): void
    {
        $data = [];
        $session = new Session($data);

        $session->set('theme', 'gelap');

        $this->assertSame(['theme' => 'gelap'], $data);
        $this->assertSame('gelap', $session->get('theme'));

        $session->remove('theme');

        $this->assertNull($session->get('theme'));
    }

    public function testFlashMessagesAreShownOnce(): void
    {
        $data = [];
        $session = new Session($data);

        $session->flash('Template tersimpan.');
        $session->flash('Draf #3 dibuat.');

        $this->assertSame(['Template tersimpan.', 'Draf #3 dibuat.'], $session->takeFlashes());
        $this->assertSame([], $session->takeFlashes());
    }

    public function testTokenIsRandomAndStaysTheSameInOneSession(): void
    {
        $data = [];
        $csrf = new Csrf(new Session($data));

        $token = $csrf->token();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        $this->assertSame($token, $csrf->token());

        $otherData = [];
        $this->assertNotSame($token, (new Csrf(new Session($otherData)))->token(), 'Session lain mendapat token lain.');
    }

    public function testOnlyTheRightTokenIsValid(): void
    {
        $data = [];
        $csrf = new Csrf(new Session($data));
        $token = $csrf->token();

        $this->assertTrue($csrf->isValid($token));
        $this->assertFalse($csrf->isValid('salah'));
        $this->assertFalse($csrf->isValid(null));
        $this->assertFalse($csrf->isValid([$token]));
    }

    public function testNoTokenInSessionMeansNothingIsValid(): void
    {
        $data = [];

        $this->assertFalse((new Csrf(new Session($data)))->isValid(''));
    }

    public function testResetEmptiesTheSession(): void
    {
        $data = ['auth_user_id' => 1, 'theme' => 'gelap'];
        $session = new Session($data);

        $session->reset();

        $this->assertSame([], $data);
    }

    public function testRegeneratedTokenReplacesTheOldOne(): void
    {
        $data = [];
        $csrf = new Csrf(new Session($data));
        $old = $csrf->token();

        $new = $csrf->regenerate();

        $this->assertNotSame($old, $new);
        $this->assertFalse($csrf->isValid($old));
        $this->assertTrue($csrf->isValid($new));
    }

    public function testVerifyRejectsFormWithoutToken(): void
    {
        $data = [];
        $csrf = new Csrf(new Session($data));
        $csrf->token();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Form ini kedaluwarsa');

        $csrf->verify(['name' => 'Template palsu']);
    }
}
