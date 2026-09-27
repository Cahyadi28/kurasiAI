<?php

declare(strict_types=1);

namespace KurasiAI\Support;

use DateTimeImmutable;

/**
 * Sumber waktu "sekarang". Class yang butuh jam meminta Clock lewat constructor,
 * bukan memanggil new DateTimeImmutable() sendiri, supaya waktunya bisa diatur saat tes.
 */
interface Clock
{
    public function now(): DateTimeImmutable;
}
