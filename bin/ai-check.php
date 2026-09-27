<?php

declare(strict_types=1);

/**
 * Mengirim SATU permintaan kecil ke mesin AI sesuai AI_PROVIDER di .env,
 * untuk memastikan API key, model, dan koneksi sudah benar.
 *
 *   php bin/ai-check.php
 *
 * Perhatian: dengan provider sungguhan, permintaan ini memakai kuota atau saldo akun Anda.
 */

use KurasiAI\App;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\Prompt;
use KurasiAI\Exception\AiProviderException;
use KurasiAI\Exception\KurasiException;

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $app = App::fromEnvironment(dirname(__DIR__));
    $provider = $app->aiProvider();

    echo "Provider : {$provider->name()}" . PHP_EOL;
    echo 'Mengirim satu permintaan kecil...' . PHP_EOL;

    $started = hrtime(true);
    $result = $provider->generate(new Prompt('Buat caption Instagram singkat tentang hemat air di sekolah.'), ContentType::Caption);
    $seconds = (hrtime(true) - $started) / 1_000_000_000;

    // Jawaban juga diperiksa aturan Caption, seperti saat dipakai di aplikasi.
    $content = ContentType::Caption->createContent($result->title, $result->body);

    printf('Berhasil dalam %.1f detik.' . PHP_EOL, $seconds);
    echo "Judul    : {$content->title}" . PHP_EOL;
    echo 'Isi      : ' . mb_strimwidth(str_replace("\n", ' ', $content->body), 0, 120, '...') . PHP_EOL;
    echo "Token    : {$result->usage->input} masuk, {$result->usage->output} keluar" . PHP_EOL;

    $costs = $app->costEstimator();

    if ($costs !== null && $provider->name() !== 'fake') {
        echo 'Perkiraan biaya: ' . $costs->format($result->usage) . PHP_EOL;
    }
} catch (AiProviderException $e) {
    fwrite(STDERR, 'Gagal: ' . $e->getMessage() . PHP_EOL);

    // Detail teknis untuk Anda sebagai pengembang. Tidak pernah berisi API key.
    if ($e->getPrevious() !== null) {
        fwrite(STDERR, 'Detail: ' . $e->getPrevious()->getMessage() . PHP_EOL);
    }

    exit(1);
} catch (KurasiException $e) {
    fwrite(STDERR, 'Gagal: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
