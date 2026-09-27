<?php

declare(strict_types=1);

namespace KurasiAI\Http;

use KurasiAI\Exception\KurasiException;
use RuntimeException;

/**
 * Halaman ini butuh login. Kernel menerjemahkannya menjadi redirect ke /login,
 * sehingga controller tidak perlu tahu caranya.
 */
final class LoginRequiredException extends RuntimeException implements KurasiException
{
}
