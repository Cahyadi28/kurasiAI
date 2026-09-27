<?php

declare(strict_types=1);

namespace KurasiAI\Exception;

use RuntimeException;

/**
 * Konfigurasi aplikasi tidak lengkap atau salah, misalnya berkas .env belum dibuat.
 */
final class ConfigException extends RuntimeException implements KurasiException
{
}
