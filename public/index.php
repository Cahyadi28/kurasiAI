<?php

declare(strict_types=1);

use KurasiAI\App;
use KurasiAI\Persistence\DraftSort;
use KurasiAI\Persistence\SortDirection;

require_once __DIR__ . '/../vendor/autoload.php';

App::run(dirname(__DIR__), function (App $app): void {
    $user = $app->currentUser();

    // Pilihan urutan dari alamat (?sort=title&dir=asc) melewati whitelist enum (Bab 13).
    $sort = DraftSort::fromInput(is_string($_GET['sort'] ?? null) ? $_GET['sort'] : null);
    $direction = SortDirection::fromInput(is_string($_GET['dir'] ?? null) ? $_GET['dir'] : null);

    $app->render('home', [
        'pageTitle' => 'Beranda',
        'templates' => $app->templates()->all(),
        'drafts' => $app->drafts()->listByAuthor($user->id, $sort, $direction),
        'sort' => $sort,
        'direction' => $direction,
    ]);
});
