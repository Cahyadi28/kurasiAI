<?php

declare(strict_types=1);

namespace KurasiAI\Persistence;

use DateTimeImmutable;
use KurasiAI\Ai\AiResult;
use KurasiAI\Domain\Content\Content;
use KurasiAI\Domain\Draft;
use KurasiAI\Domain\DraftStatus;
use KurasiAI\Domain\Prompt;
use KurasiAI\Exception\NotFoundException;
use KurasiAI\Support\IdSequence;

/**
 * DraftRepository di memori, untuk tes dan demo tanpa database.
 * Objek disimpan sebagai salinan (clone), sehingga perubahan yang belum di-save()
 * tidak ikut "tersimpan", sama seperti versi SQLite.
 */
final class InMemoryDraftRepository implements DraftRepository
{
    /** @var array<int, Draft> */
    private array $drafts = [];

    public function __construct(private readonly IdSequence $ids = new IdSequence())
    {
    }

    public function add(
        int $authorId,
        Content $content,
        Prompt $prompt,
        DateTimeImmutable $createdAt,
        ?AiResult $source = null,
    ): Draft {
        $draft = new Draft(
            id: $this->ids->next(),
            authorId: $authorId,
            content: $content,
            prompt: $prompt,
            createdAt: $createdAt,
            usage: $source?->usage,
        );
        $this->drafts[$draft->id] = clone $draft;

        return $draft;
    }

    public function find(int $id): ?Draft
    {
        return isset($this->drafts[$id]) ? clone $this->drafts[$id] : null;
    }

    public function get(int $id): Draft
    {
        return $this->find($id) ?? throw new NotFoundException('Draf', $id);
    }

    public function save(Draft $draft): void
    {
        if (!isset($this->drafts[$draft->id])) {
            throw new NotFoundException('Draf', $draft->id);
        }

        $this->drafts[$draft->id] = clone $draft;
    }

    public function listByAuthor(
        int $authorId,
        DraftSort $sort = DraftSort::Updated,
        SortDirection $direction = SortDirection::Desc,
    ): array {
        $drafts = array_values(array_filter(
            $this->drafts,
            fn (Draft $draft): bool => $draft->authorId === $authorId,
        ));

        usort($drafts, function (Draft $a, Draft $b) use ($sort, $direction): int {
            $result = match ($sort) {
                DraftSort::Title => strcmp($a->title(), $b->title()),
                DraftSort::Created => $a->createdAt() <=> $b->createdAt(),
                DraftSort::Updated => $a->updatedAt() <=> $b->updatedAt(),
                DraftSort::Status => strcmp($a->status()->value, $b->status()->value),
            };
            // Nilai sama: urutkan menurut nomor, seperti ", id" di ORDER BY versi SQLite.
            $result = $result !== 0 ? $result : $a->id <=> $b->id;

            return $direction === SortDirection::Asc ? $result : -$result;
        });

        return array_map(fn (Draft $draft): Draft => clone $draft, $drafts);
    }

    public function listByStatus(DraftStatus $status): array
    {
        $drafts = array_filter($this->drafts, fn (Draft $draft): bool => $draft->status() === $status);
        ksort($drafts);

        return array_values(array_map(fn (Draft $draft): Draft => clone $draft, $drafts));
    }
}
