<?php

declare(strict_types=1);

namespace KurasiAI\Exception;

use DomainException;

/**
 * Pelaku tidak berhak melakukan tindakan ini.
 */
final class AccessDeniedException extends DomainException implements KurasiException
{
}
