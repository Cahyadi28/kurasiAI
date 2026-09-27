<?php

declare(strict_types=1);

/**
 * Membuat database SQLite kosong dari database/schema.sql.
 *
 *   php bin/setup-db.php           membuat database baru (menolak bila sudah ada)
 *   php bin/setup-db.php --fresh   menghapus database lama lalu membuat ulang (SEMUA DATA HILANG)
 */

use KurasiAI\Exception\KurasiException;
use KurasiAI\Persistence\Database;
use KurasiAI\Support\Env;

require_once __DIR__ . '/../vendor/autoload.php';

$root = dirname(__DIR__);
$fresh = in_array('--fresh', $argv, true);

try {
    $env = Env::fromFile($root . '/.env');
    $path = $env->path('DB_PATH', $root);

    if (is_file($path)) {
        if (!$fresh) {
            fwrite(STDERR, 'Database sudah ada: ' . $env->required('DB_PATH') . PHP_EOL
                . 'Jalankan "php bin/setup-db.php --fresh" untuk menghapus dan membuat ulang (semua data hilang).' . PHP_EOL);
            exit(1);
        }

        unlink($path);
    }

    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0775, true);
    }

    $schema = file_get_contents($root . '/database/schema.sql');

    if ($schema === false) {
        throw new RuntimeException('database/schema.sql tidak bisa dibaca.');
    }

    Database::sqlite($path)->runScript($schema);

    echo 'Database dibuat: ' . $env->required('DB_PATH') . PHP_EOL;
    echo 'Langkah berikutnya: php bin/seed.php' . PHP_EOL;
} catch (KurasiException | RuntimeException $e) {
    // PDOException juga turunan RuntimeException.
    fwrite(STDERR, 'Gagal: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
