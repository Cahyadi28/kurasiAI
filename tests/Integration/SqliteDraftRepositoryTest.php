<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Integration;

use KurasiAI\Exception\InvalidStateException;
use KurasiAI\Persistence\Database;
use KurasiAI\Persistence\DraftRepository;
use KurasiAI\Persistence\SqliteDraftRepository;
use KurasiAI\Tests\Contract\DraftRepositoryContract;

class SqliteDraftRepositoryTest extends DraftRepositoryContract
{
    private Database $db;

    protected function createRepository(): DraftRepository
    {
        $schema = file_get_contents(__DIR__ . '/../../database/schema.sql');
        $this->assertIsString($schema);

        $this->db = Database::inMemory();
        $this->db->runScript($schema);
        $this->db->pdo()->exec(
            "INSERT INTO users (name, email, role, password_hash, created_at) VALUES
             ('Rina', 'rina@kurasiai.test', 'writer', 'hash', 'x'),
             ('Sari', 'sari@kurasiai.test', 'writer', 'hash', 'x'),
             ('Budi', 'budi@kurasiai.test', 'editor', 'hash', 'x')"
        );

        return new SqliteDraftRepository($this->db);
    }

    public function testAiDraftAlsoWritesAUsageLog(): void
    {
        $draft = $this->addDraft($this->rina, 'Hemat air');

        $row = $this->db->pdo()->query('SELECT user_id, draft_id, provider, input_tokens, output_tokens FROM usage_logs')->fetch();

        $this->assertSame(
            ['user_id' => 1, 'draft_id' => $draft->id, 'provider' => 'fake', 'input_tokens' => 12, 'output_tokens' => 30],
            $row,
        );
    }

    public function testLoadedTimesUseTheApplicationTimezone(): void
    {
        $draft = $this->addDraft($this->rina, 'Hemat air');

        $loaded = $this->repository->get($draft->id);

        $this->assertSame('Asia/Jakarta', $loaded->createdAt()->getTimezone()->getName());
        $this->assertSame('09:00 WIB', $loaded->createdAt()->format('H:i T'));
    }

    public function testDataFromAnotherRepositoryObjectIsTheSame(): void
    {
        $draft = $this->addDraft($this->rina, 'Hemat air');
        $draft->submit($this->rina, $this->clock->now());
        $this->repository->save($draft);

        // Seperti permintaan berikutnya: objek repository baru, database yang sama.
        $again = (new SqliteDraftRepository($this->db))->get($draft->id);

        $this->assertEquals($draft->history(), $again->history());
    }

    public function testRejectsInconsistentData(): void
    {
        $draft = $this->addDraft($this->rina, 'Hemat air');
        $this->db->pdo()->prepare("UPDATE drafts SET status = 'published' WHERE id = :id")->execute(['id' => $draft->id]);

        $this->expectException(InvalidStateException::class);
        $this->expectExceptionMessage('Data draf #1 tidak konsisten');

        $this->repository->get($draft->id);
    }
}
