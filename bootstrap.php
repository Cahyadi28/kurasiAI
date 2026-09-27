<?php

declare(strict_types=1);

// Memuat semua class proyek.
// Setiap class baru harus ditambahkan di sini. Mulai Bab 9, berkas ini
// digantikan oleh autoload Composer.

require_once __DIR__ . '/src/Prompt.php';
require_once __DIR__ . '/src/TokenUsage.php';
require_once __DIR__ . '/src/DraftStatus.php';
require_once __DIR__ . '/src/ContentType.php';
require_once __DIR__ . '/src/Content.php';
require_once __DIR__ . '/src/Article.php';
require_once __DIR__ . '/src/VideoScript.php';
require_once __DIR__ . '/src/Caption.php';
require_once __DIR__ . '/src/Draft.php';
require_once __DIR__ . '/src/AiResult.php';
require_once __DIR__ . '/src/AiProvider.php';
require_once __DIR__ . '/src/FakeAiProvider.php';
