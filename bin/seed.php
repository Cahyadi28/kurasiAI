<?php

declare(strict_types=1);

/**
 * Mengisi database dengan akun dan template contoh.
 * Password semua akun diambil dari SEED_PASSWORD di .env, tidak pernah ditulis di kode.
 *
 *   php bin/seed.php
 */

use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\PromptTemplate;
use KurasiAI\Domain\Role;
use KurasiAI\Exception\ConfigException;
use KurasiAI\Exception\KurasiException;
use KurasiAI\Persistence\Database;
use KurasiAI\Support\Env;
use KurasiAI\Support\SystemClock;

require_once __DIR__ . '/../vendor/autoload.php';

const MIN_PASSWORD_LENGTH = 8;

$root = dirname(__DIR__);

$accounts = [
    ['name' => 'Rina', 'email' => 'rina@kurasiai.test', 'role' => Role::Writer],
    ['name' => 'Sari', 'email' => 'sari@kurasiai.test', 'role' => Role::Writer],
    ['name' => 'Budi', 'email' => 'budi@kurasiai.test', 'role' => Role::Editor],
];

try {
    $env = Env::fromFile($root . '/.env');
    $password = $env->required('SEED_PASSWORD');

    if (mb_strlen($password) < MIN_PASSWORD_LENGTH) {
        throw new ConfigException('SEED_PASSWORD minimal ' . MIN_PASSWORD_LENGTH . ' karakter.');
    }

    $db = Database::fromEnv($env, $root);
    $now = (new SystemClock($env->timezone()))->now();

    $existing = (int) $db->pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn();

    if ($existing > 0) {
        throw new ConfigException('Data contoh sudah ada. Untuk mengulang: php bin/setup-db.php --fresh lalu php bin/seed.php');
    }

    // Template dibuat sebagai objek dulu, supaya aturan validasinya ikut diperiksa sebelum disimpan.
    $templates = [
        new PromptTemplate('Artikel edukasi', 'Tulis artikel tentang {{topik}} untuk {{pembaca}}.', ContentType::Article, $now),
        new PromptTemplate('Caption kampanye', 'Buat caption Instagram tentang {{topik}}.', ContentType::Caption, $now),
    ];

    $db->transaction(function (PDO $pdo) use ($accounts, $templates, $password, $now): void {
        $insertUser = $pdo->prepare(
            'INSERT INTO users (name, email, role, password_hash, created_at)
             VALUES (:name, :email, :role, :password_hash, :created_at)'
        );

        $firstUserId = null;

        foreach ($accounts as $account) {
            $insertUser->execute([
                'name' => $account['name'],
                'email' => $account['email'],
                'role' => $account['role']->value,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'created_at' => $now->format(DATE_ATOM),
            ]);

            // lastInsertId(): nomor id yang baru saja dibuat database.
            $firstUserId ??= (int) $pdo->lastInsertId();
        }

        // Template contoh dibuat oleh akun pertama (Rina).
        $insertTemplate = $pdo->prepare(
            'INSERT INTO prompt_templates (name, text, target_type, created_by, created_at, updated_at)
             VALUES (:name, :text, :target_type, :created_by, :created_at, :updated_at)'
        );

        foreach ($templates as $template) {
            $insertTemplate->execute([
                'name' => $template->name(),
                'text' => $template->text(),
                'target_type' => $template->targetType->value,
                'created_by' => $firstUserId,
                'created_at' => $template->createdAt()->format(DATE_ATOM),
                'updated_at' => $template->updatedAt()->format(DATE_ATOM),
            ]);
        }
    });

    echo count($accounts) . ' akun dibuat (password: nilai SEED_PASSWORD di .env):' . PHP_EOL;

    foreach ($accounts as $account) {
        echo "  - {$account['email']} ({$account['role']->label()})" . PHP_EOL;
    }

    echo count($templates) . ' template contoh dibuat.' . PHP_EOL;
} catch (KurasiException | RuntimeException $e) {
    fwrite(STDERR, 'Gagal: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
