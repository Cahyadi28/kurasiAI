<?php

declare(strict_types=1);

/**
 * Kerangka semua halaman.
 *
 * @var \KurasiAI\Http\View $this
 * @var string $pageTitle
 * @var string $content      hasil render tampilan halaman (sudah berupa HTML)
 * @var string $csrfInput    kolom tersembunyi berisi token CSRF (sudah berupa HTML)
 * @var \KurasiAI\Domain\User|null $currentUser
 */
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->e($pageTitle) ?> · KurasiAI</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main>
    <header class="topbar">
        <a href="index.php" class="brand">KurasiAI</a>
        <?php if (isset($currentUser)): ?>
            <nav>
                <a href="index.php">Beranda</a>
                <a href="template-form.php">Template baru</a>
            </nav>
            <form method="post" action="logout.php" class="account">
                <?= $csrfInput ?>
                <span><?= $this->e($currentUser->name) ?> <span class="badge"><?= $this->e($currentUser->role->label()) ?></span></span>
                <button type="submit" class="secondary">Keluar</button>
            </form>
        <?php endif; ?>
    </header>

    <?php foreach ($flashes ?? [] as $flash): ?>
        <p class="flash"><?= $this->e($flash) ?></p>
    <?php endforeach; ?>

    <?= $content ?>
</main>
</body>
</html>
