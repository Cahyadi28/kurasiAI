<?php

declare(strict_types=1);

namespace KurasiAI\Ai\Http;

use RuntimeException;

/**
 * Permintaan HTTP tidak mendapat jawaban sama sekali: server tidak bisa dihubungi, koneksi putus,
 * atau waktu habis. Beda dengan jawaban berstatus error (misalnya 429), yang tetap berupa ApiResponse.
 */
final class NetworkException extends RuntimeException
{
}
