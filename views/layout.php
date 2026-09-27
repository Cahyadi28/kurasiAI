<?php

declare(strict_types=1);

/**
 * Kerangka semua halaman.
 *
 * @var \KurasiAI\Http\View $this
 * @var string $pageTitle
 * @var string $content      hasil render tampilan halaman (sudah berupa HTML)
 * @var string $csrfInput    kolom tersembunyi berisi token CSRF (sudah berupa HTML)
 * @var bool $isLocal
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
        <nav>
            <a href="index.php">Beranda</a>
            <a href="template-form.php">Template baru</a>
        </nav>
        <?php if (isset($currentUser, $users) && $isLocal): ?>
            <form method="post" action="act-as.php" class="act-as">
                <?= $csrfInput ?>
                <label>Bertindak sebagai <span class="badge">sementara</span>
                    <select name="user_id">
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $this->e($user->id) ?>"<?= $user->isSameAs($currentUser) ? ' selected' : '' ?>>
                                <?= $this->e($user->name) ?> (<?= $this->e($user->role->label()) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" class="secondary">Ganti</button>
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
