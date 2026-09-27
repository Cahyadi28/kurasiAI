<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Exception;

use DomainException;
use InvalidArgumentException;
use KurasiAI\Domain\DraftStatus;
use KurasiAI\Exception\AccessDeniedException;
use KurasiAI\Exception\AiProviderException;
use KurasiAI\Exception\InvalidStateException;
use KurasiAI\Exception\InvalidTransitionException;
use KurasiAI\Exception\KurasiException;
use KurasiAI\Exception\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

/**
 * Janji yang dipegang kode pemanggil: setiap exception KurasiAI bisa ditangkap sebagai
 * KurasiException DAN sebagai exception bawaan PHP yang menjadi induknya.
 */
class ExceptionTest extends TestCase
{
    /**
     * @return array<string, array{Throwable, class-string<Throwable>}>
     */
    public static function exceptionsAndParents(): array
    {
        return [
            'validasi' => [new ValidationException('x'), InvalidArgumentException::class],
            'akses ditolak' => [new AccessDeniedException('x'), DomainException::class],
            'status tidak sah' => [new InvalidStateException('x'), DomainException::class],
            'transisi tidak sah' => [
                new InvalidTransitionException(DraftStatus::Draft, DraftStatus::Published),
                InvalidStateException::class,
            ],
            'mesin AI gagal' => [new AiProviderException('x'), RuntimeException::class],
        ];
    }

    /**
     * @param class-string<Throwable> $parent
     */
    #[DataProvider('exceptionsAndParents')]
    public function testCanBeCaughtAsKurasiExceptionAndAsItsParent(Throwable $exception, string $parent): void
    {
        $this->assertInstanceOf(KurasiException::class, $exception);
        $this->assertInstanceOf($parent, $exception);
    }

    public function testInvalidTransitionDescribesBothStatuses(): void
    {
        $exception = new InvalidTransitionException(DraftStatus::Approved, DraftStatus::Draft);

        $this->assertSame(DraftStatus::Approved, $exception->from);
        $this->assertSame(DraftStatus::Draft, $exception->to);
        $this->assertSame('Draf berstatus Disetujui tidak bisa diubah menjadi Draf.', $exception->getMessage());
    }

    public function testKeepsThePreviousException(): void
    {
        $cause = new RuntimeException('Connection timed out after 30 seconds');
        $exception = new AiProviderException('Mesin AI sedang tidak bisa dihubungi.', previous: $cause);

        $this->assertSame($cause, $exception->getPrevious());
    }
}
