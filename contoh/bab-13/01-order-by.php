<?php

declare(strict_types=1);

$pdo = new PDO('sqlite::memory:', options: [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('CREATE TABLE drafts (id INTEGER PRIMARY KEY, title TEXT, secret TEXT)');
$pdo->exec("INSERT INTO drafts (title, secret) VALUES ('Kompos', 'b'), ('Air bersih', 'c'), ('Listrik', 'a')");

$titles = fn (array $rows): string => implode(', ', array_column($rows, 'title'));

// 1. Placeholder untuk nama kolom: tidak error, tetapi tidak mengurutkan apa-apa.
//    Yang terkirim adalah TEKS 'title', jadi semua baris dianggap bernilai sama.
$statement = $pdo->prepare('SELECT title FROM drafts ORDER BY :column');
$statement->execute(['column' => 'title']);
echo 'Placeholder      : ' . $titles($statement->fetchAll()) . PHP_EOL;

// 2. Menempel input langsung: urutannya benar, tetapi pengguna bisa mengurutkan
//    berdasarkan kolom yang seharusnya tidak ia ketahui.
$input = 'secret';
echo 'Tempel input     : ' . $titles($pdo->query("SELECT title FROM drafts ORDER BY $input")->fetchAll())
    . "   <- diam-diam urut menurut kolom \"$input\"" . PHP_EOL;

// 3. Whitelist: input hanya dipakai untuk MEMILIH salah satu nama kolom yang sudah disiapkan.
$allowed = ['title' => 'title', 'newest' => 'id DESC'];

foreach (['title', 'secret'] as $input) {
    if (!isset($allowed[$input])) {
        echo "Whitelist        : urutan \"$input\" ditolak" . PHP_EOL;
        continue;
    }

    echo 'Whitelist        : ' . $titles($pdo->query("SELECT title FROM drafts ORDER BY {$allowed[$input]}")->fetchAll()) . PHP_EOL;
}
