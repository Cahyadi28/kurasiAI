<?php

declare(strict_types=1);

// Autoloader buatan sendiri: cara kerja yang sama dengan autoload Composer (PSR-4).
spl_autoload_register(function (string $class): void {
    $prefix = 'KurasiAI\\';

    if (!str_starts_with($class, $prefix)) {
        return; // bukan class kita, biarkan autoloader lain yang mencari
    }

    // KurasiAI\Domain\Content\Article  ->  src/Domain/Content/Article.php
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/../../src/' . str_replace('\\', '/', $relative) . '.php';

    echo "  [autoload] $class -> " . substr($file, strlen(__DIR__ . '/../../')) . PHP_EOL;

    if (is_file($file)) {
        require $file;
    }
});

echo 'Membuat artikel...' . PHP_EOL;
$article = new KurasiAI\Domain\Content\Article('Energi surya', str_repeat('Energi surya itu bersih. ', 15));

echo 'Membuat artikel kedua...' . PHP_EOL;
$second = new KurasiAI\Domain\Content\Article('Hemat air', str_repeat('Hemat air itu penting. ', 15));

echo $article->metaLabel() . PHP_EOL;
