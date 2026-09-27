<?php

declare(strict_types=1);

namespace KurasiAI\Exception;

use OutOfBoundsException;

/**
 * Data yang dicari berdasarkan nomornya tidak ada, misalnya draf #12.
 */
final class NotFoundException extends OutOfBoundsException implements KurasiException
{
    /**
     * @param string $entity nama jenis data untuk pesan, misalnya 'Draf' atau 'Template'
     */
    public function __construct(public readonly string $entity, public readonly int $id)
    {
        parent::__construct("$entity #$id tidak ditemukan.");
    }
}
