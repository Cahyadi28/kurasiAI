<?php

declare(strict_types=1);

namespace KurasiAI\Exception;

use InvalidArgumentException;

/**
 * Data yang diberikan tidak sah, misalnya judul kosong atau email salah format.
 */
final class ValidationException extends InvalidArgumentException implements KurasiException
{
}
