<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

session_start();

// 1. Menangani form (POST), lalu kembali ke halaman utama.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        add_draft((string) ($_POST['topic'] ?? ''), (string) ($_POST['type'] ?? 'article'));
    } elseif ($action === 'change_status') {
        change_status((int) ($_POST['id'] ?? 0), (string) ($_POST['status'] ?? ''));
    } elseif ($action === 'clear') {
        save_all_drafts([]);
    }

    header('Location: index.php');
    exit;
}

// 2. Menampilkan halaman (GET).
$drafts = get_all_drafts();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KurasiAI — Prototipe</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main>
    <h1>KurasiAI <small>prototipe prosedural</small></h1>

    <section class="card">
        <h2>Buat draf baru</h2>
        <form method="post">
            <input type="hidden" name="action" value="create">
            <label>
                Topik
                <input type="text" name="topic" placeholder="misalnya: energi surya">
            </label>
            <label>
                Jenis konten
                <select name="type">
                    <option value="article">Artikel</option>
                    <option value="video_script">Naskah Video</option>
                    <option value="caption">Caption</option>
                </select>
            </label>
            <button type="submit">Generate dengan AI</button>
        </form>
    </section>

    <h2>Daftar draf (<?= count($drafts) ?>)</h2>

    <?php if (count($drafts) === 0): ?>
        <p class="empty">Belum ada draf. Buat satu di atas.</p>
    <?php endif; ?>

    <?php foreach ($drafts as $draft): ?>
        <article class="card">
            <header>
                <h3>#<?= $draft['id'] ?> <?= e($draft['title']) ?></h3>
                <span class="label"><?= e(type_label($draft['type'])) ?></span>
                <span class="status status-<?= e($draft['status']) ?>"><?= e(status_label($draft['status'])) ?></span>
            </header>
            <p class="body"><?= nl2br(e($draft['body'])) ?></p>
            <div class="actions">
                <?php foreach (available_actions($draft['status']) as $targetStatus => $buttonText): ?>
                    <form method="post">
                        <input type="hidden" name="action" value="change_status">
                        <input type="hidden" name="id" value="<?= $draft['id'] ?>">
                        <button type="submit" name="status" value="<?= e($targetStatus) ?>"><?= e($buttonText) ?></button>
                    </form>
                <?php endforeach; ?>
            </div>
        </article>
    <?php endforeach; ?>

    <?php if (count($drafts) > 0): ?>
        <form method="post">
            <input type="hidden" name="action" value="clear">
            <button type="submit" class="secondary">Kosongkan semua draf</button>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
