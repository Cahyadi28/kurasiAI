<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Domain;

use DomainException;
use KurasiAI\Domain\Content\Caption;
use KurasiAI\Domain\Draft;
use KurasiAI\Domain\DraftStatus;
use KurasiAI\Domain\Prompt;
use KurasiAI\Domain\Role;
use KurasiAI\Domain\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Matriks peran-aksi (Bab 8): siapa boleh melakukan apa.
 * Setiap tindakan diuji pada status yang memang mengizinkannya,
 * sehingga yang diuji hanya hak aksesnya.
 */
class DraftPermissionTest extends TestCase
{
    /**
     * @return array<string, array{'revise'|'submit'|'approve'|'reject'|'publish', 'penulis pemilik'|'penulis lain'|'editor pemilik'|'editor lain', bool}>
     */
    public static function matrix(): array
    {
        // tindakan => [penulis pemilik, penulis lain, editor pemilik, editor lain]
        $allowed = [
            'revise' => [true, false, true, false],
            'submit' => [true, false, true, false],
            'approve' => [false, false, false, true],
            'reject' => [false, false, false, true],
            'publish' => [true, false, true, false],
        ];
        $actors = ['penulis pemilik', 'penulis lain', 'editor pemilik', 'editor lain'];

        $cases = [];

        foreach ($allowed as $action => $results) {
            foreach ($actors as $index => $actor) {
                $cases["$action oleh $actor"] = [$action, $actor, $results[$index]];
            }
        }

        return $cases;
    }

    /**
     * @param 'revise'|'submit'|'approve'|'reject'|'publish' $action
     * @param 'penulis pemilik'|'penulis lain'|'editor pemilik'|'editor lain' $actorKind
     */
    #[DataProvider('matrix')]
    public function testWhoMayDoWhat(string $action, string $actorKind, bool $expectedAllowed): void
    {
        $writer = new User(1, 'Rina', 'rina@contoh.id', Role::Writer);
        $otherWriter = new User(2, 'Sari', 'sari@contoh.id', Role::Writer);
        $editor = new User(3, 'Budi', 'budi@contoh.id', Role::Editor);

        [$author, $actor] = match ($actorKind) {
            'penulis pemilik' => [$writer, $writer],
            'penulis lain' => [$writer, $otherWriter],
            'editor pemilik' => [$editor, $editor],
            'editor lain' => [$writer, $editor],
        };

        $startStatus = match ($action) {
            'revise', 'submit' => DraftStatus::Draft,
            'approve', 'reject' => DraftStatus::InReview,
            'publish' => DraftStatus::Approved,
        };

        $draft = new Draft(
            id: 1,
            authorId: $author->id,
            content: new Caption('Hemat air', 'Matikan keran. #hematair'),
            prompt: new Prompt('Buat caption tentang hemat air.'),
            status: $startStatus,
        );

        if (!$expectedAllowed) {
            $this->expectException(DomainException::class);
        }

        match ($action) {
            'revise' => $draft->revise($actor, new Caption('Hemat air', 'Tutup keran rapat-rapat. #hematair')),
            'submit' => $draft->submit($actor),
            'approve' => $draft->approve($actor),
            'reject' => $draft->reject($actor, 'Perlu data pendukung.'),
            'publish' => $draft->publish($actor),
        };

        $this->assertTrue($expectedAllowed);
    }
}
