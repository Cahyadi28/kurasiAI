<?php

declare(strict_types=1);

// Satu berkas berisi dua namespace, hanya untuk contoh. Di proyek sungguhan: satu class per berkas.

namespace Sekolah {
    final class User
    {
        public function __construct(
            public readonly string $studentNumber,
        ) {
        }
    }
}

namespace {
    require_once __DIR__ . '/../../vendor/autoload.php';

    use KurasiAI\Domain\Role;
    use KurasiAI\Domain\User;
    use Sekolah\User as Student; // nama sama, beri alias supaya tidak bentrok

    $writer = new User(1, 'Rina', 'rina@kurasiai.test', Role::Writer);
    $student = new Student('2026-0142');

    echo $writer::class . PHP_EOL;  // KurasiAI\Domain\User
    echo $student::class . PHP_EOL; // Sekolah\User
}
