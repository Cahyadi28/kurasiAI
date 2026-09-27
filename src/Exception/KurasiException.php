<?php

declare(strict_types=1);

namespace KurasiAI\Exception;

use Throwable;

/**
 * Penanda semua exception milik KurasiAI.
 * Dengan catch (KurasiException $e), semua masalah yang "kita kenal" bisa ditangkap sekaligus.
 */
interface KurasiException extends Throwable
{
}
