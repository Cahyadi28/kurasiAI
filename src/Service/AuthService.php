<?php

declare(strict_types=1);

namespace KurasiAI\Service;

use DateInterval;
use DateTimeImmutable;
use KurasiAI\Domain\User;
use KurasiAI\Exception\AuthenticationException;
use KurasiAI\Persistence\LoginAttemptRepository;
use KurasiAI\Persistence\UserRepository;
use KurasiAI\Support\Clock;

/**
 * Memeriksa email dan password. Tidak tahu apa-apa tentang session atau halaman web.
 */
final class AuthService
{
    public const MAX_FAILURES = 5;
    public const LOCK_PERIOD = 'PT15M';

    /** Satu pesan untuk semua kegagalan, supaya tidak membocorkan email mana yang terdaftar. */
    private const WRONG_CREDENTIALS = 'Email atau password salah.';

    public function __construct(
        private readonly UserRepository $users,
        private readonly LoginAttemptRepository $attempts,
        private readonly Clock $clock,
    ) {
    }

    /**
     * @throws AuthenticationException bila gagal atau sedang dikunci
     */
    public function attempt(string $email, string $password): User
    {
        $email = strtolower(trim($email));
        $now = $this->clock->now();

        // Dihitung per email, termasuk email yang tidak terdaftar.
        $since = $now->sub(new DateInterval(self::LOCK_PERIOD));

        if ($this->attempts->countFailuresSince($email, $since) >= self::MAX_FAILURES) {
            throw new AuthenticationException('Terlalu banyak percobaan login yang gagal. Coba lagi dalam 15 menit.');
        }

        $credentials = $this->users->findCredentials($email);

        if ($credentials === null) {
            // Email tidak terdaftar. password_hash() tetap dijalankan supaya waktu jawabannya sama
            // lamanya dengan password_verify(): lama jawaban tidak boleh membocorkan email mana yang ada.
            password_hash($password, PASSWORD_DEFAULT);
            $this->fail($email, $now);
        }

        if (!password_verify($password, $credentials->passwordHash)) {
            $this->fail($email, $now);
        }

        $this->attempts->clear($email);

        // Hash dibuat dengan pengaturan lama (misalnya cost yang lebih rendah)? Perbarui selagi
        // password aslinya ada di tangan. Hanya bisa dilakukan saat login.
        if (password_needs_rehash($credentials->passwordHash, PASSWORD_DEFAULT)) {
            $this->users->updatePasswordHash($credentials->user->id, password_hash($password, PASSWORD_DEFAULT));
        }

        return $credentials->user;
    }

    private function fail(string $email, DateTimeImmutable $now): never
    {
        $this->attempts->recordFailure($email, $now);

        throw new AuthenticationException(self::WRONG_CREDENTIALS);
    }
}
