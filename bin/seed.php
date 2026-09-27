<?php

declare(strict_types=1);

/**
 * Mengisi database dengan akun dan template contoh.
 * Password semua akun diambil dari SEED_PASSWORD di .env, tidak pernah ditulis di kode.
 *
 *   php bin/seed.php
 */

use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\Role;
use KurasiAI\Exception\ConfigException;
use KurasiAI\Exception\KurasiException;
use KurasiAI\Persistence\Database;
use KurasiAI\Persistence\PromptTemplateRepository;
use KurasiAI\Persistence\UserRepository;
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
    $users = new UserRepository($db);
    $templates = new PromptTemplateRepository($db, $env->timezone());
    $now = (new SystemClock($env->timezone()))->now();

    if ($users->all() !== []) {
        throw new ConfigException('Data contoh sudah ada. Untuk mengulang: php bin/setup-db.php --fresh lalu php bin/seed.php');
    }

    // Semua data contoh tersimpan bersama, atau tidak sama sekali.
    // Transaksi di dalam repository ikut menjadi bagian transaksi luar ini.
    $created = $db->transaction(function () use ($accounts, $users, $templates, $password, $now): array {
        $created = [];

        foreach ($accounts as $account) {
            $created[] = $users->add(
                $account['name'],
                $account['email'],
                $account['role'],
                password_hash($password, PASSWORD_DEFAULT),
                $now,
            );
        }

        // Template contoh dibuat oleh akun pertama (Rina).
        $templates->add($created[0], 'Artikel edukasi', 'Tulis artikel tentang {{topik}} untuk {{pembaca}}.', ContentType::Article, $now);
        $templates->add($created[0], 'Caption kampanye', 'Buat caption Instagram tentang {{topik}}.', ContentType::Caption, $now);

        return $created;
    });

    echo count($created) . ' akun dibuat (password: nilai SEED_PASSWORD di .env):' . PHP_EOL;

    foreach ($created as $user) {
        echo "  - #{$user->id} {$user->email} ({$user->role->label()})" . PHP_EOL;
    }

    echo count($templates->all()) . ' template contoh dibuat.' . PHP_EOL;
} catch (KurasiException | RuntimeException $e) {
    fwrite(STDERR, 'Gagal: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
