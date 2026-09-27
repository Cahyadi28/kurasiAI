<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Integration;

use PDO;
use PDOException;
use RuntimeException;

class DatabaseTest extends DatabaseTestCase
{
    public function testRowsAreAssociativeArraysWithNativeTypes(): void
    {
        $id = $this->insertUser('rina@kurasiai.test', 'editor');

        $statement = $this->pdo->prepare('SELECT id, email, role FROM users WHERE id = :id');
        $statement->execute(['id' => $id]);

        // Sejak PHP 8.1, angka dari SQLite dikembalikan sebagai int, bukan string.
        $this->assertSame(['id' => $id, 'email' => 'rina@kurasiai.test', 'role' => 'editor'], $statement->fetch());
    }

    public function testErrorsBecomeExceptions(): void
    {
        $this->expectException(PDOException::class);

        $this->pdo->query('SELECT * FROM tabel_yang_tidak_ada');
    }

    public function testTransactionCommitsAndReturnsTheResult(): void
    {
        $id = $this->db->transaction(function (PDO $pdo): int {
            $pdo->exec("INSERT INTO users (name, email, role, password_hash, created_at)
                        VALUES ('Rina', 'rina@kurasiai.test', 'writer', 'hash', 'x')");

            return (int) $pdo->lastInsertId();
        });

        $this->assertSame(1, $id);
        $this->assertSame(1, $this->rowCount('users'));
    }

    public function testTransactionRollsBackEverythingWhenOneStepFails(): void
    {
        $authorId = $this->insertUser('rina@kurasiai.test');

        try {
            $this->db->transaction(function () use ($authorId): void {
                $this->insertDraft($authorId);
                $this->insertDraft($authorId, 'status_ngawur');
            });
            $this->fail('Status yang tidak sah seharusnya ditolak database.');
        } catch (PDOException) {
            // memang diharapkan
        }

        $this->assertSame(0, $this->rowCount('drafts'), 'Draf pertama ikut dibatalkan.');
        $this->assertFalse($this->pdo->inTransaction());
    }

    public function testTransactionRethrowsTheOriginalException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Mesin AI gagal.');

        $this->db->transaction(function (): never {
            throw new RuntimeException('Mesin AI gagal.');
        });
    }
}
