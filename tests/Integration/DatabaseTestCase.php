<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Integration;

use KurasiAI\Persistence\Database;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Induk tes integrasi: setiap tes mendapat database SQLite baru di memori
 * dengan skema yang sama persis seperti aplikasi.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected Database $db;
    protected PDO $pdo;

    protected function setUp(): void
    {
        $schema = file_get_contents(__DIR__ . '/../../database/schema.sql');
        $this->assertIsString($schema);

        $this->db = Database::inMemory();
        $this->db->runScript($schema);
        $this->pdo = $this->db->pdo();
    }

    protected function insertUser(string $email, string $role = 'writer'): int
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO users (name, email, role, password_hash, created_at)
             VALUES ('Pengguna', :email, :role, 'hash', '2026-10-05T09:00:00+07:00')"
        );
        $statement->execute(['email' => $email, 'role' => $role]);

        return (int) $this->pdo->lastInsertId();
    }

    protected function insertDraft(int $authorId, string $status = 'draft'): int
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO drafts (author_id, type, title, body, prompt, status, created_at, updated_at)
             VALUES (:author_id, 'caption', 'Hemat air', 'Matikan keran. #hematair', 'Buat caption.', :status,
                     '2026-10-05T09:00:00+07:00', '2026-10-05T09:00:00+07:00')"
        );
        $statement->execute(['author_id' => $authorId, 'status' => $status]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Nama tabel ditulis langsung di kode tes, tidak pernah berasal dari input pengguna.
     */
    protected function rowCount(string $table): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    }
}
