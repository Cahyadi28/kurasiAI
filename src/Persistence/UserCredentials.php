<?php

declare(strict_types=1);

namespace KurasiAI\Persistence;

use KurasiAI\Domain\User;

/**
 * Akun beserta hash password-nya. Hanya dipakai saat login;
 * objek User sendiri sengaja tidak menyimpan hash.
 */
final class UserCredentials
{
    public function __construct(
        public readonly User $user,
        public readonly string $passwordHash,
    ) {
    }
}
