<?php

declare(strict_types=1);

namespace KurasiAI\Persistence;

use DateTimeImmutable;
use DateTimeZone;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\PromptTemplate;
use KurasiAI\Domain\User;
use KurasiAI\Exception\NotFoundException;
use KurasiAI\Support\SystemClock;
use PDO;

/**
 * Menyimpan dan mengambil template prompt di tabel prompt_templates.
 */
final class PromptTemplateRepository
{
    private const COLUMNS = 'id, created_by, name, text, target_type, created_at, updated_at';

    public function __construct(
        private readonly Database $db,
        private readonly DateTimeZone $timezone = new DateTimeZone(SystemClock::DEFAULT_TIMEZONE),
    ) {
    }

    public function add(User $creator, string $name, string $text, ContentType $targetType, DateTimeImmutable $createdAt): PromptTemplate
    {
        return $this->db->transaction(function (PDO $pdo) use ($creator, $name, $text, $targetType, $createdAt): PromptTemplate {
            $pdo->prepare(
                'INSERT INTO prompt_templates (name, text, target_type, created_by, created_at, updated_at)
                 VALUES (:name, :text, :target_type, :created_by, :created_at, :updated_at)'
            )->execute([
                'name' => $name,
                'text' => $text,
                'target_type' => $targetType->value,
                'created_by' => $creator->id,
                'created_at' => $createdAt->format(DATE_ATOM),
                'updated_at' => $createdAt->format(DATE_ATOM),
            ]);

            // Validasi nama dan teks ada di PromptTemplate. Kalau gagal, barisnya ikut dibatalkan.
            $template = new PromptTemplate((int) $pdo->lastInsertId(), $creator->id, $name, $text, $targetType, $createdAt);

            $pdo->prepare('UPDATE prompt_templates SET name = :name, text = :text WHERE id = :id')
                ->execute(['id' => $template->id, 'name' => $template->name(), 'text' => $template->text()]);

            return $template;
        });
    }

    public function get(int $id): PromptTemplate
    {
        $statement = $this->db->pdo()->prepare('SELECT ' . self::COLUMNS . ' FROM prompt_templates WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? throw new NotFoundException('Template', $id) : $this->toTemplate($row);
    }

    /**
     * @return list<PromptTemplate> urut menurut nama
     */
    public function all(): array
    {
        $rows = $this->db->pdo()->query('SELECT ' . self::COLUMNS . ' FROM prompt_templates ORDER BY name, id')->fetchAll();

        return array_map(fn (array $row): PromptTemplate => $this->toTemplate($row), $rows);
    }

    public function save(PromptTemplate $template): void
    {
        $statement = $this->db->pdo()->prepare(
            'UPDATE prompt_templates SET name = :name, text = :text, updated_at = :updated_at WHERE id = :id'
        );
        $statement->execute([
            'id' => $template->id,
            'name' => $template->name(),
            'text' => $template->text(),
            'updated_at' => $template->updatedAt()->format(DATE_ATOM),
        ]);

        if ($statement->rowCount() === 0) {
            throw new NotFoundException('Template', $template->id);
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function toTemplate(array $row): PromptTemplate
    {
        return PromptTemplate::restore(
            id: (int) $row['id'],
            createdBy: (int) $row['created_by'],
            name: (string) $row['name'],
            text: (string) $row['text'],
            targetType: ContentType::from((string) $row['target_type']),
            createdAt: (new DateTimeImmutable((string) $row['created_at']))->setTimezone($this->timezone),
            updatedAt: (new DateTimeImmutable((string) $row['updated_at']))->setTimezone($this->timezone),
        );
    }
}
