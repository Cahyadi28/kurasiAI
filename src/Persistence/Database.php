<?php

declare(strict_types=1);

namespace KurasiAI\Persistence;

use KurasiAI\Exception\ConfigException;
use KurasiAI\Support\Env;
use PDO;
use Throwable;

/**
 * Satu pintu ke database: membuat koneksi PDO dengan pengaturan yang sama di semua tempat.
 */
final class Database
{
    private const OPTIONS = [
        // Setiap kesalahan SQL menjadi PDOException, bukan nilai false yang mudah terlewat.
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // Setiap baris hasil query berupa array asosiatif: ['id' => 1, 'name' => 'Rina'].
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    private function __construct(private readonly PDO $pdo)
    {
        // SQLite baru menjaga foreign key kalau diminta, per koneksi.
        $this->pdo->exec('PRAGMA foreign_keys = ON');
    }

    public static function sqlite(string $path): self
    {
        return new self(new PDO('sqlite:' . $path, options: self::OPTIONS));
    }

    /**
     * Database sementara di memori. Hilang begitu objeknya tidak dipakai lagi; cocok untuk tes.
     */
    public static function inMemory(): self
    {
        return self::sqlite(':memory:');
    }

    /**
     * Membuka database aplikasi dari DB_PATH di .env. Berkasnya harus sudah dibuat bin/setup-db.php.
     */
    public static function fromEnv(Env $env, string $projectRoot): self
    {
        $path = $env->path('DB_PATH', $projectRoot);

        // SQLite diam-diam membuat berkas kosong kalau belum ada; lebih baik gagal dengan pesan jelas.
        if (!is_file($path)) {
            throw new ConfigException('Database belum dibuat. Jalankan dulu: php bin/setup-db.php');
        }

        return self::sqlite($path);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Menjalankan berkas SQL berisi banyak perintah, misalnya database/schema.sql.
     */
    public function runScript(string $sql): void
    {
        $this->pdo->exec($sql);
    }

    /**
     * Menjalankan $work sebagai satu kesatuan: semua perintahnya tersimpan, atau tidak satu pun.
     *
     * @template T
     * @param callable(PDO): T $work
     * @return T
     */
    public function transaction(callable $work): mixed
    {
        $this->pdo->beginTransaction();

        try {
            $result = $work($this->pdo);
            $this->pdo->commit();

            return $result;
        } catch (Throwable $e) {
            $this->pdo->rollBack();

            throw $e;
        }
    }
}
