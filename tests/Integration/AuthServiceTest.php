<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Integration;

use DateInterval;
use KurasiAI\Domain\Role;
use KurasiAI\Domain\User;
use KurasiAI\Exception\AuthenticationException;
use KurasiAI\Persistence\LoginAttemptRepository;
use KurasiAI\Persistence\UserRepository;
use KurasiAI\Service\AuthService;
use KurasiAI\Support\FixedClock;

class AuthServiceTest extends DatabaseTestCase
{
    /** Password data tes, bukan rahasia sungguhan. */
    private const PASSWORD = 'rina-belajar-oop';

    private FixedClock $clock;
    private UserRepository $users;
    private LoginAttemptRepository $attempts;
    private AuthService $auth;
    private User $rina;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock = FixedClock::at('2026-10-05 09:00');
        $this->users = new UserRepository($this->db);
        $this->attempts = new LoginAttemptRepository($this->db);
        $this->auth = new AuthService($this->users, $this->attempts, $this->clock);

        // Cost rendah supaya tes cepat; sekaligus menjadi "hash lama" untuk tes rehash.
        $hash = password_hash(self::PASSWORD, PASSWORD_BCRYPT, ['cost' => 4]);
        $this->rina = $this->users->add('Rina', 'rina@kurasiai.test', Role::Writer, $hash, $this->clock->now());
    }

    public function testCorrectPasswordReturnsTheUser(): void
    {
        $user = $this->auth->attempt(' RINA@kurasiai.test ', self::PASSWORD);

        $this->assertEquals($this->rina, $user);
    }

    public function testWrongPasswordAndUnknownEmailGetTheSameMessage(): void
    {
        $messages = [];

        foreach ([['rina@kurasiai.test', 'salah-tebak'], ['tidak-ada@kurasiai.test', self::PASSWORD]] as [$email, $password]) {
            try {
                $this->auth->attempt($email, $password);
                $this->fail('Login seharusnya gagal.');
            } catch (AuthenticationException $e) {
                $messages[] = $e->getMessage();
            }
        }

        $this->assertSame(['Email atau password salah.', 'Email atau password salah.'], $messages);
    }

    public function testTooManyFailuresLockTheEmailEvenWithTheRightPassword(): void
    {
        $this->failTimes(AuthService::MAX_FAILURES, 'rina@kurasiai.test');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Terlalu banyak percobaan login yang gagal.');

        $this->auth->attempt('rina@kurasiai.test', self::PASSWORD);
    }

    public function testLockEndsAfterTheLockPeriod(): void
    {
        $this->failTimes(AuthService::MAX_FAILURES, 'rina@kurasiai.test');

        $this->clock->advance(new DateInterval(AuthService::LOCK_PERIOD));

        $this->assertEquals($this->rina, $this->auth->attempt('rina@kurasiai.test', self::PASSWORD));
    }

    public function testFailuresAreCountedPerEmail(): void
    {
        $this->failTimes(AuthService::MAX_FAILURES, 'penyerang@kurasiai.test');

        $this->assertEquals($this->rina, $this->auth->attempt('rina@kurasiai.test', self::PASSWORD));
    }

    public function testSuccessfulLoginClearsTheFailures(): void
    {
        $this->failTimes(AuthService::MAX_FAILURES - 1, 'rina@kurasiai.test');
        $this->auth->attempt('rina@kurasiai.test', self::PASSWORD);

        $this->assertSame(0, $this->attempts->countFailuresSince('rina@kurasiai.test', $this->clock->now()->modify('-1 hour')));
    }

    public function testOldHashIsUpgradedOnLogin(): void
    {
        $this->auth->attempt('rina@kurasiai.test', self::PASSWORD);

        $newHash = $this->users->findCredentials('rina@kurasiai.test')->passwordHash ?? '';

        $this->assertFalse(password_needs_rehash($newHash, PASSWORD_DEFAULT), 'Hash sudah memakai pengaturan terbaru.');
        $this->assertTrue(password_verify(self::PASSWORD, $newHash), 'Password yang sama tetap bisa dipakai.');
    }

    private function failTimes(int $times, string $email): void
    {
        for ($i = 0; $i < $times; $i++) {
            try {
                $this->auth->attempt($email, 'tebakan-ke-' . $i);
            } catch (AuthenticationException) {
                // memang diharapkan
            }
        }
    }
}
