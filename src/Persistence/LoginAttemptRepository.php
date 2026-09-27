<?php

declare(strict_types=1);

namespace KurasiAI\Persistence;

use DateTimeImmutable;

/**
 * Mencatat percobaan login yang gagal per email.
 */
final class LoginAttemptRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function recordFailure(string $email, DateTimeImmutable $at): void
    {
        $this->db->pdo()
            ->prepare('INSERT INTO login_attempts (email, attempted_at) VALUES (:email, :attempted_at)')
            ->execute(['email' => $email, 'attempted_at' => $at->getTimestamp()]);
    }

    public function countFailuresSince(string $email, DateTimeImmutable $since): int
    {
        $statement = $this->db->pdo()->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE email = :email AND attempted_at > :since'
        );
        $statement->execute(['email' => $email, 'since' => $since->getTimestamp()]);

        return (int) $statement->fetchColumn();
    }

    /**
     * Menghapus catatan gagal setelah login berhasil.
     */
    public function clear(string $email): void
    {
        $this->db->pdo()->prepare('DELETE FROM login_attempts WHERE email = :email')->execute(['email' => $email]);
    }
}
