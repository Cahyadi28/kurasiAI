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

/**
 * Tempat menyimpan dan mengambil draf. Pemakainya tidak perlu tahu di mana datanya disimpan.
 */
interface DraftRepository
{
    /**
     * Menyimpan draf baru dan mengembalikannya lengkap dengan nomor dari tempat penyimpanan.
     * Bila draf dibuat AI, $source diisi: pemakaian tokennya ikut dicatat.
     */
    public function add(
        int $authorId,
        Content $content,
        Prompt $prompt,
        DateTimeImmutable $createdAt,
        ?AiResult $source = null,
    ): Draft;

    public function find(int $id): ?Draft;

    /**
     * @throws NotFoundException bila draf dengan nomor itu tidak ada
     */
    public function get(int $id): Draft;

    /**
     * Menyimpan perubahan isi, status, dan riwayat draf yang sudah ada.
     *
     * @throws NotFoundException bila draf belum pernah disimpan
     */
    public function save(Draft $draft): void;

    /**
     * @return list<Draft>
     */
    public function listByAuthor(
        int $authorId,
        DraftSort $sort = DraftSort::Updated,
        SortDirection $direction = SortDirection::Desc,
    ): array;

    /**
     * @return list<Draft> urut dari nomor terkecil
     */
    public function listByStatus(DraftStatus $status): array;
}
