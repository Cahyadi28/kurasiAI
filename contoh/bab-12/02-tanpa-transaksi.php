<?php

declare(strict_types=1);

// Menyimpan draf lalu riwayatnya. Langkah kedua sengaja gagal (status tidak sah).
function setUpDatabase(): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec("CREATE TABLE drafts (id INTEGER PRIMARY KEY, title TEXT)");
    $pdo->exec("CREATE TABLE status_changes (draft_id INTEGER, to_status TEXT CHECK (to_status IN ('in_review')))");

    return $pdo;
}

function saveDraft(PDO $pdo): void
{
    $pdo->exec("INSERT INTO drafts (title) VALUES ('Hemat air')");
    $pdo->exec("INSERT INTO status_changes (draft_id, to_status) VALUES (1, 'terkirim')");
}

function report(string $label, PDO $pdo): void
{
    $drafts = $pdo->query('SELECT COUNT(*) FROM drafts')->fetchColumn();
    $changes = $pdo->query('SELECT COUNT(*) FROM status_changes')->fetchColumn();
    echo "$label: $drafts draf, $changes riwayat" . PHP_EOL;
}

// Tanpa transaksi: draf tersimpan, riwayatnya tidak. Datanya setengah jadi.
$pdo = setUpDatabase();

try {
    saveDraft($pdo);
} catch (PDOException) {
    echo 'Langkah kedua gagal.' . PHP_EOL;
}

report('Tanpa transaksi', $pdo);

// Dengan transaksi: gagal di langkah mana pun, semuanya dibatalkan.
$pdo = setUpDatabase();

try {
    $pdo->beginTransaction();
    saveDraft($pdo);
    $pdo->commit();
} catch (PDOException) {
    $pdo->rollBack();
    echo 'Langkah kedua gagal, transaksi dibatalkan.' . PHP_EOL;
}

report('Dengan transaksi', $pdo);
