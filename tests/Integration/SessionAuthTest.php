<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Integration;

use DateInterval;
use KurasiAI\Domain\Role;
use KurasiAI\Domain\User;
use KurasiAI\Http\Csrf;
use KurasiAI\Http\Session;
use KurasiAI\Http\SessionAuth;
use KurasiAI\Persistence\UserRepository;
use KurasiAI\Support\FixedClock;

class SessionAuthTest extends DatabaseTestCase
{
    /** @var array<mixed> isi session, seperti $_SESSION */
    private array $data = [];
    private Session $session;
    private Csrf $csrf;
    private FixedClock $clock;
    private UserRepository $users;
    private User $rina;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock = FixedClock::at('2026-10-05 09:00');
        $this->session = new Session($this->data);
        $this->csrf = new Csrf($this->session);
        $this->users = new UserRepository($this->db);
        $this->rina = $this->users->add('Rina', 'rina@kurasiai.test', Role::Writer, 'hash', $this->clock->now());
    }

    public function testNobodyIsLoggedInAtFirst(): void
    {
        $this->assertNull($this->auth()->user());
    }

    public function testLoginIsRememberedInTheSession(): void
    {
        $tokenBefore = $this->csrf->token();

        $this->auth()->login($this->rina);

        // Permintaan berikutnya: objek baru, isi session yang sama.
        $this->assertEquals($this->rina, $this->auth()->user());
        $this->assertNotSame($tokenBefore, $this->csrf->token(), 'Token CSRF diganti saat login.');
    }

    public function testActivityKeepsTheSessionAlive(): void
    {
        $this->auth()->login($this->rina);

        $this->clock->advance(new DateInterval('PT29M'));
        $this->assertNotNull($this->auth()->user());

        $this->clock->advance(new DateInterval('PT29M'));
        $this->assertNotNull($this->auth()->user(), 'Setiap permintaan memperpanjang batas waktu.');
    }

    public function testIdleSessionExpires(): void
    {
        $this->auth()->login($this->rina);

        $this->clock->advance(new DateInterval(SessionAuth::IDLE_TIMEOUT));

        $this->assertNull($this->auth()->user());
        $this->assertSame(['Sesi Anda berakhir karena terlalu lama tidak aktif. Silakan masuk lagi.'], $this->session->takeFlashes());
    }

    public function testLogoutForgetsEverything(): void
    {
        $auth = $this->auth();
        $auth->login($this->rina);

        $auth->logout();

        $this->assertNull($auth->user());
        $this->assertNull($this->auth()->user());
        $this->assertSame([], $this->data);
    }

    public function testDeletedAccountIsLoggedOut(): void
    {
        $this->auth()->login($this->rina);
        $this->pdo->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $this->rina->id]);

        $this->assertNull($this->auth()->user());
    }

    private function auth(): SessionAuth
    {
        return new SessionAuth($this->session, $this->csrf, $this->users, $this->clock);
    }
}
