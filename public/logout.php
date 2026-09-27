<?php

declare(strict_types=1);

use KurasiAI\App;

require_once __DIR__ . '/../vendor/autoload.php';

App::run(dirname(__DIR__), function (App $app): void {
    // Logout hanya lewat POST dengan token CSRF. Tautan biasa (GET) bisa dipicu situs lain,
    // misalnya lewat <img src=".../logout.php">.
    if (!$app->isPost()) {
        $app->redirect('index.php');
    }

    $app->csrf()->verify($_POST);
    $app->sessionAuth()->logout();
    $app->session()->flash('Anda sudah keluar.');
    $app->redirect('login.php');
});
