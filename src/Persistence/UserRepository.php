<?php

declare(strict_types=1);

namespace KurasiAI\Persistence;

use DateTimeImmutable;
use KurasiAI\Domain\Role;
use KurasiAI\Domain\User;
use KurasiAI\Exception\NotFoundException;
use PDO;

/**
 * Menyimpan dan mencari akun di tabel users.
 */
final class UserRepository
{
    private const COLUMNS = 'id, name, email, role';

    public function __construct(private readonly Database $db)
    {
    }

    /**
     * Menyimpan akun baru. $passwordHash adalah hasil password_hash(), bukan password asli.
     */
    public function add(string $name, string $email, Role $role, string $passwordHash, DateTimeImmutable $createdAt): User
    {
        return $this->db->transaction(function (PDO $pdo) use ($name, $email, $role, $passwordHash, $createdAt): User {
            // 1. Simpan baris untuk mendapatkan nomor dari database.
            $pdo->prepare(
                'INSERT INTO users (name, email, role, password_hash, created_at)
                 VALUES (:name, :email, :role, :password_hash, :created_at)'
            )->execute([
                'name' => $name,
                'email' => $email,
                'role' => $role->value,
                'password_hash' => $passwordHash,
                'created_at' => $createdAt->format(DATE_ATOM),
            ]);

            // 2. Bangun objeknya. Kalau validasi gagal, transaksi dibatalkan dan barisnya hilang.
            $user = new User((int) $pdo->lastInsertId(), $name, $email, $role);

            // 3. Simpan versi yang sudah dirapikan objek (spasi dibuang, email huruf kecil).
            $pdo->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id')
                ->execute(['id' => $user->id, 'name' => $user->name, 'email' => $user->email]);

            return $user;
        });
    }

    public function get(int $id): User
    {
        $statement = $this->db->pdo()->prepare('SELECT ' . self::COLUMNS . ' FROM users WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? throw new NotFoundException('Pengguna', $id) : $this->toUser($row);
    }

    public function findByEmail(string $email): ?User
    {
        $statement = $this->db->pdo()->prepare('SELECT ' . self::COLUMNS . ' FROM users WHERE email = :email');
        // Email disimpan dalam huruf kecil (aturan class User), jadi yang dicari juga dirapikan.
        $statement->execute(['email' => strtolower(trim($email))]);
        $row = $statement->fetch();

        return $row === false ? null : $this->toUser($row);
    }

    /**
     * Akun beserta hash password-nya, untuk login. Null bila email tidak terdaftar.
     */
    public function findCredentials(string $email): ?UserCredentials
    {
        $statement = $this->db->pdo()->prepare('SELECT ' . self::COLUMNS . ', password_hash FROM users WHERE email = :email');
        $statement->execute(['email' => strtolower(trim($email))]);
        $row = $statement->fetch();

        return $row === false ? null : new UserCredentials($this->toUser($row), (string) $row['password_hash']);
    }

    public function updatePasswordHash(int $userId, string $passwordHash): void
    {
        $this->db->pdo()
            ->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id')
            ->execute(['id' => $userId, 'password_hash' => $passwordHash]);
    }

    /**
     * @return list<User>
     */
    public function all(): array
    {
        $rows = $this->db->pdo()->query('SELECT ' . self::COLUMNS . ' FROM users ORDER BY id')->fetchAll();

        return array_map(fn (array $row): User => $this->toUser($row), $rows);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function toUser(array $row): User
    {
        return new User((int) $row['id'], (string) $row['name'], (string) $row['email'], Role::from((string) $row['role']));
    }
}
