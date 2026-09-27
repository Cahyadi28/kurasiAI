<?php

declare(strict_types=1);

namespace KurasiAI\Exception;

use RuntimeException;

/**
 * Mesin AI gagal memberi hasil, misalnya karena jaringan atau layanan sedang bermasalah.
 */
final class AiProviderException extends RuntimeException implements KurasiException
{
}
