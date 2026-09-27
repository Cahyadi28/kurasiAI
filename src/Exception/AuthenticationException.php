<?php

declare(strict_types=1);

namespace KurasiAI\Exception;

use RuntimeException;

/**
 * Login gagal: email atau password salah, atau terlalu banyak percobaan.
 */
final class AuthenticationException extends RuntimeException implements KurasiException
{
}
