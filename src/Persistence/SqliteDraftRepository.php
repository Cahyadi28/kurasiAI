<?php

declare(strict_types=1);

namespace KurasiAI\Persistence;

use DateTimeImmutable;
use DateTimeZone;
use KurasiAI\Ai\AiResult;
use KurasiAI\Domain\Content\Content;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\Draft;
use KurasiAI\Domain\DraftStatus;
use KurasiAI\Domain\Prompt;
use KurasiAI\Domain\StatusChange;
use KurasiAI\Domain\TokenUsage;
use KurasiAI\Exception\NotFoundException;
use KurasiAI\Support\SystemClock;
use PDO;

/**
 * DraftRepository yang menyimpan ke tabel drafts, status_changes, dan usage_logs.
 * Semua SQL tentang draf ada di class ini, tidak di tempat lain.
 */
final class SqliteDraftRepository implements DraftRepository
{
    private const COLUMNS = 'id, author_id, type, title, body, prompt, status, input_tokens, output_tokens, created_at, updated_at';

    public function __construct(
        private readonly Database $db,
        private readonly DateTimeZone $timezone = new DateTimeZone(SystemClock::DEFAULT_TIMEZONE),
    ) {
    }

    public function add(
        int $authorId,
        Content $content,
        Prompt $prompt,
        DateTimeImmutable $createdAt,
        ?AiResult $source = null,
    ): Draft {
        return $this->db->transaction(function (PDO $pdo) use ($authorId, $content, $prompt, $createdAt, $source): Draft {
            $pdo->prepare(
                'INSERT INTO drafts (author_id, type, title, body, prompt, status,
                                     input_tokens, output_tokens, created_at, updated_at)
                 VALUES (:author_id, :type, :title, :body, :prompt, :status,
                         :input_tokens, :output_tokens, :created_at, :updated_at)'
            )->execute([
                'author_id' => $authorId,
                'type' => $content->type()->value,
                'title' => $content->title,
                'body' => $content->body,
                'prompt' => $prompt->text,
                'status' => DraftStatus::Draft->value,
                'input_tokens' => $source?->usage->input,
                'output_tokens' => $source?->usage->output,
                'created_at' => $createdAt->format(DATE_ATOM),
                'updated_at' => $createdAt->format(DATE_ATOM),
            ]);

            // Nomor draf diberikan database (AUTOINCREMENT).
            $id = (int) $pdo->lastInsertId();

            if ($source !== null) {
                $pdo->prepare(
                    'INSERT INTO usage_logs (user_id, draft_id, provider, input_tokens, output_tokens, created_at)
                     VALUES (:user_id, :draft_id, :provider, :input_tokens, :output_tokens, :created_at)'
                )->execute([
                    'user_id' => $authorId,
                    'draft_id' => $id,
                    'provider' => $source->providerName,
                    'input_tokens' => $source->usage->input,
                    'output_tokens' => $source->usage->output,
                    'created_at' => $createdAt->format(DATE_ATOM),
                ]);
            }

            return new Draft(
                id: $id,
                authorId: $authorId,
                content: $content,
                prompt: $prompt,
                createdAt: $createdAt,
                usage: $source?->usage,
            );
        });
    }

    public function find(int $id): ?Draft
    {
        $statement = $this->db->pdo()->prepare('SELECT ' . self::COLUMNS . ' FROM drafts WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $this->toDraft($row);
    }

    public function get(int $id): Draft
    {
        return $this->find($id) ?? throw new NotFoundException('Draf', $id);
    }

    public function save(Draft $draft): void
    {
        $this->db->transaction(function (PDO $pdo) use ($draft): void {
            $update = $pdo->prepare(
                'UPDATE drafts SET title = :title, body = :body, status = :status, updated_at = :updated_at
                 WHERE id = :id'
            );
            $update->execute([
                'id' => $draft->id,
                'title' => $draft->content()->title,
                'body' => $draft->content()->body,
                'status' => $draft->status()->value,
                'updated_at' => $draft->updatedAt()->format(DATE_ATOM),
            ]);

            if ($update->rowCount() === 0) {
                throw new NotFoundException('Draf', $draft->id);
            }

            // Riwayat tidak pernah diubah, hanya bertambah: cukup simpan baris yang belum ada.
            $count = $pdo->prepare('SELECT COUNT(*) FROM status_changes WHERE draft_id = :id');
            $count->execute(['id' => $draft->id]);
            $stored = (int) $count->fetchColumn();

            $insert = $pdo->prepare(
                'INSERT INTO status_changes (draft_id, from_status, to_status, actor_id, note, changed_at)
                 VALUES (:draft_id, :from_status, :to_status, :actor_id, :note, :changed_at)'
            );

            foreach (array_slice($draft->history(), $stored) as $change) {
                $insert->execute([
                    'draft_id' => $draft->id,
                    'from_status' => $change->from->value,
                    'to_status' => $change->to->value,
                    'actor_id' => $change->actorId,
                    'note' => $change->note,
                    'changed_at' => $change->at->format(DATE_ATOM),
                ]);
            }
        });
    }

    public function listByAuthor(
        int $authorId,
        DraftSort $sort = DraftSort::Updated,
        SortDirection $direction = SortDirection::Desc,
    ): array {
        // Nama kolom dan arah urutan tidak bisa memakai placeholder. Keduanya hanya boleh
        // berasal dari enum (whitelist), tidak pernah dari teks input pengguna.
        $orderBy = $sort->column() . ' ' . $direction->sql() . ', id ' . $direction->sql();

        $statement = $this->db->pdo()->prepare(
            'SELECT ' . self::COLUMNS . " FROM drafts WHERE author_id = :author_id ORDER BY $orderBy"
        );
        $statement->execute(['author_id' => $authorId]);

        return array_map(fn (array $row): Draft => $this->toDraft($row), $statement->fetchAll());
    }

    public function listByStatus(DraftStatus $status): array
    {
        $statement = $this->db->pdo()->prepare('SELECT ' . self::COLUMNS . ' FROM drafts WHERE status = :status ORDER BY id');
        $statement->execute(['status' => $status->value]);

        return array_map(fn (array $row): Draft => $this->toDraft($row), $statement->fetchAll());
    }

    /**
     * Pemetaan satu baris tabel drafts (beserta riwayatnya) menjadi objek Draft.
     *
     * @param array<string, mixed> $row
     */
    private function toDraft(array $row): Draft
    {
        $id = (int) $row['id'];
        $usage = $row['input_tokens'] === null
            ? null
            : new TokenUsage((int) $row['input_tokens'], (int) $row['output_tokens']);

        return Draft::restore(
            id: $id,
            authorId: (int) $row['author_id'],
            content: ContentType::from((string) $row['type'])->createContent((string) $row['title'], (string) $row['body']),
            prompt: new Prompt((string) $row['prompt']),
            status: DraftStatus::from((string) $row['status']),
            usage: $usage,
            createdAt: $this->toTime((string) $row['created_at']),
            updatedAt: $this->toTime((string) $row['updated_at']),
            history: $this->historyOf($id),
        );
    }

    /**
     * @return list<StatusChange>
     */
    private function historyOf(int $draftId): array
    {
        $statement = $this->db->pdo()->prepare(
            'SELECT from_status, to_status, actor_id, note, changed_at FROM status_changes
             WHERE draft_id = :draft_id ORDER BY id'
        );
        $statement->execute(['draft_id' => $draftId]);

        return array_map(
            fn (array $row): StatusChange => new StatusChange(
                from: DraftStatus::from((string) $row['from_status']),
                to: DraftStatus::from((string) $row['to_status']),
                actorId: (int) $row['actor_id'],
                at: $this->toTime((string) $row['changed_at']),
                note: $row['note'] === null ? null : (string) $row['note'],
            ),
            $statement->fetchAll(),
        );
    }

    /**
     * Teks DATE_ATOM hanya menyimpan selisih (+07:00), bukan nama zona.
     * Dikonversi ke zona aplikasi supaya tampilannya kembali "WIB", bukan "+07:00".
     */
    private function toTime(string $value): DateTimeImmutable
    {
        return (new DateTimeImmutable($value))->setTimezone($this->timezone);
    }
}
