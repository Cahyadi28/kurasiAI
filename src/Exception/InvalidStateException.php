<?php

declare(strict_types=1);

namespace KurasiAI\Exception;

use DomainException;

/**
 * Tindakan tidak bisa dilakukan pada keadaan (status) object saat ini.
 */
class InvalidStateException extends DomainException implements KurasiException
{
}
