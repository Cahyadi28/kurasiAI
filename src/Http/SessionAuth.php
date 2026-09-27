<?php

declare(strict_types=1);

namespace KurasiAI\Http;

use DateInterval;
use KurasiAI\Domain\User;
use KurasiAI\Exception\NotFoundException;
use KurasiAI\Persistence\UserRepository;
use KurasiAI\Support\Clock;

/**
 * Mengingat siapa yang sedang login di session. Pemeriksaan password ada di AuthService;
 * class ini hanya mengurus apa yang terjadi SETELAH password terbukti benar.
 */
final class SessionAuth
{
    /** Tanpa aktivitas selama ini, pengguna harus login ulang. */
    public const IDLE_TIMEOUT = 'PT30M';

    private const USER_KEY = 'auth_user_id';
    private const LAST_ACTIVITY_KEY = 'auth_last_activity';

    private ?User $user = null;

    public function __construct(
        private readonly Session $session,
        private readonly Csrf $csrf,
        private readonly UserRepository $users,
        private readonly Clock $clock,
    ) {
    }

    public function login(User $user): void
    {
        // Nomor session baru + token CSRF baru: apa pun yang diketahui orang lain
        // dari sebelum login tidak berlaku lagi (mencegah session fixation).
        $this->session->regenerate();
        $this->csrf->regenerate();

        $this->session->set(self::USER_KEY, $user->id);
        $this->touch();
        $this->user = $user;
    }

    public function logout(): void
    {
        $this->session->reset();
        $this->user = null;
    }

    /**
     * Pengguna yang sedang login, atau null. Session yang terlalu lama diam dianggap berakhir.
     */
    public function user(): ?User
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $id = $this->session->get(self::USER_KEY);
        $lastActivity = $this->session->get(self::LAST_ACTIVITY_KEY);

        if (!is_int($id) || !is_int($lastActivity)) {
            return null;
        }

        $expiresAt = $this->clock->now()->setTimestamp($lastActivity)->add(new DateInterval(self::IDLE_TIMEOUT));

        if ($this->clock->now() >= $expiresAt) {
            $this->logout();
            $this->session->flash('Sesi Anda berakhir karena terlalu lama tidak aktif. Silakan masuk lagi.');

            return null;
        }

        try {
            $this->user = $this->users->get($id);
        } catch (NotFoundException) {
            // Akun sudah tidak ada, misalnya database dibuat ulang.
            $this->logout();

            return null;
        }

        $this->touch();

        return $this->user;
    }

    private function touch(): void
    {
        $this->session->set(self::LAST_ACTIVITY_KEY, $this->clock->now()->getTimestamp());
    }
}
