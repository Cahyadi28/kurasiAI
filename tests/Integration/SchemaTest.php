<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Integration;

use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\DraftStatus;
use KurasiAI\Domain\Role;
use PDOException;

class SchemaTest extends DatabaseTestCase
{
    public function testCreatesAllTables(): void
    {
        $tables = $this->pdo
            ->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name")
            ->fetchAll(\PDO::FETCH_COLUMN);

        $this->assertSame(['drafts', 'login_attempts', 'prompt_templates', 'status_changes', 'usage_logs', 'users'], $tables);
    }

    public function testEmailMustBeUnique(): void
    {
        $this->insertUser('rina@kurasiai.test');

        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('UNIQUE constraint failed: users.email');

        $this->insertUser('rina@kurasiai.test');
    }

    public function testRejectsUnknownRole(): void
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('CHECK constraint failed');

        $this->insertUser('tono@kurasiai.test', 'admin');
    }

    public function testEveryEnumValueIsAllowedBySchema(): void
    {
        foreach (Role::cases() as $index => $role) {
            $this->insertUser("user$index@kurasiai.test", $role->value);
        }

        $authorId = $this->insertUser('penulis@kurasiai.test');

        foreach (DraftStatus::cases() as $status) {
            $this->insertDraft($authorId, $status->value);
        }

        $insertTemplate = $this->pdo->prepare(
            "INSERT INTO prompt_templates (name, text, target_type, created_by, created_at, updated_at)
             VALUES ('Template', 'Tulis tentang {{topik}}.', :type, :author, 'x', 'x')"
        );

        foreach (ContentType::cases() as $type) {
            $insertTemplate->execute(['type' => $type->value, 'author' => $authorId]);
        }

        $this->assertSame(count(Role::cases()) + 1, $this->rowCount('users'));
        $this->assertSame(count(DraftStatus::cases()), $this->rowCount('drafts'));
        $this->assertSame(count(ContentType::cases()), $this->rowCount('prompt_templates'));
    }

    public function testDraftNeedsAnExistingAuthor(): void
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('FOREIGN KEY constraint failed');

        $this->insertDraft(authorId: 999);
    }

    public function testDeletingADraftDeletesItsHistory(): void
    {
        $authorId = $this->insertUser('rina@kurasiai.test');
        $draftId = $this->insertDraft($authorId, 'in_review');

        $this->pdo->prepare(
            "INSERT INTO status_changes (draft_id, from_status, to_status, actor_id, changed_at)
             VALUES (:draft, 'draft', 'in_review', :actor, '2026-10-05T09:15:00+07:00')"
        )->execute(['draft' => $draftId, 'actor' => $authorId]);

        $this->pdo->prepare('DELETE FROM drafts WHERE id = :id')->execute(['id' => $draftId]);

        $this->assertSame(0, $this->rowCount('status_changes'));
    }
}
