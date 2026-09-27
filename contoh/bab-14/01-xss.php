<?php

declare(strict_types=1);

// Judul draf yang diketik pengguna (atau dibuat AI yang "dibujuk" lewat prompt).
$title = '<img src=x onerror="alert(document.cookie)"> Hemat air';

// 1. Tanpa escaping: browser membaca teks ini sebagai tag <img> dan menjalankan onerror.
echo 'Tanpa escaping : <h1>' . $title . '</h1>' . PHP_EOL;

// 2. Dengan escaping: < > " menjadi entitas, sehingga browser menampilkannya sebagai teks biasa.
echo 'Dengan escaping: <h1>' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h1>' . PHP_EOL;

// 3. Di dalam atribut, tanda kutip juga berbahaya: nilai bisa "keluar" dari atribut.
$search = '" autofocus onfocus="alert(1)';
echo 'Atribut aman   : <input value="' . htmlspecialchars($search, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' . PHP_EOL;
