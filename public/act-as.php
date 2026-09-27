<?php

declare(strict_types=1);

/**
 * SEMENTARA (Bab 14): mengganti pengguna aktif tanpa password, hanya di APP_ENV=local.
 * Bab 15 menggantinya dengan login sungguhan.
 */

use KurasiAI\App;
use KurasiAI\Exception\ValidationException;

require_once __DIR__ . '/../vendor/autoload.php';

App::run(dirname(__DIR__), function (App $app): void {
    if (!$app->isPost()) {
        $app->redirect('index.php');
    }

    $app->csrf()->verify($_POST);

    $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);

    if ($userId === false) {
        throw new ValidationException('Pilih pengguna dari daftar.');
    }

    $user = $app->actAs($userId);
    $app->session()->flash("Sekarang bertindak sebagai {$user->name} ({$user->role->label()}).");
    $app->redirect('index.php');
});
