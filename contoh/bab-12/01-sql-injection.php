<?php

declare(strict_types=1);

// Database latihan di memori: dua draf milik penulis yang berbeda.
$pdo = new PDO('sqlite::memory:', options: [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec("CREATE TABLE drafts (id INTEGER PRIMARY KEY, author_id INTEGER, title TEXT)");
$pdo->exec("INSERT INTO drafts (author_id, title) VALUES (1, 'Hemat air di rumah'), (2, 'Rahasia strategi kampanye')");

// Rina (author_id 1) mencari draf MILIKNYA berdasarkan judul.

// BERBAHAYA: input pengguna ditempel langsung ke dalam SQL.
function searchUnsafe(PDO $pdo, int $authorId, string $keyword): array
{
    $sql = "SELECT id, title FROM drafts WHERE author_id = $authorId AND title LIKE '%$keyword%'";

    return $pdo->query($sql)->fetchAll();
}

// AMAN: SQL dan data dikirim terpisah lewat placeholder.
function searchSafe(PDO $pdo, int $authorId, string $keyword): array
{
    $statement = $pdo->prepare('SELECT id, title FROM drafts WHERE author_id = :author AND title LIKE :keyword');
    $statement->bindValue('author', $authorId, PDO::PARAM_INT);
    $statement->bindValue('keyword', "%$keyword%");
    $statement->execute();

    return $statement->fetchAll();
}

$attack = "' OR 1=1 --";

echo 'Tidak aman, kata "hemat" : ' . json_encode(searchUnsafe($pdo, 1, 'hemat')) . PHP_EOL;
echo 'Tidak aman, kata serangan: ' . json_encode(searchUnsafe($pdo, 1, $attack)) . PHP_EOL;
echo 'Aman, kata "hemat"       : ' . json_encode(searchSafe($pdo, 1, 'hemat')) . PHP_EOL;
echo 'Aman, kata serangan      : ' . json_encode(searchSafe($pdo, 1, $attack)) . PHP_EOL;
