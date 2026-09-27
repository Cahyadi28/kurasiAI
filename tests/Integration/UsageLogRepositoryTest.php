<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Integration;

use KurasiAI\Domain\TokenUsage;
use KurasiAI\Persistence\UsageLogRepository;

class UsageLogRepositoryTest extends DatabaseTestCase
{
    public function testTotalsPerUserIncludeUsersWithoutUsage(): void
    {
        $rina = $this->insertUser('rina@kurasiai.test');
        $budi = $this->insertUser('budi@kurasiai.test', 'editor');
        $this->insertUsage($rina, 10, 40);
        $this->insertUsage($rina, 5, 20);

        $repository = new UsageLogRepository($this->db);
        $totals = $repository->totalsPerUser();

        $this->assertCount(2, $totals);
        $this->assertSame([$rina, 2], [$totals[0]->userId, $totals[0]->generations]);
        $this->assertEquals(new TokenUsage(15, 60), $totals[0]->usage);
        $this->assertSame([$budi, 0], [$totals[1]->userId, $totals[1]->generations]);
        $this->assertEquals(TokenUsage::zero(), $totals[1]->usage);
        $this->assertEquals(new TokenUsage(15, 60), $repository->totalFor($rina));
    }

    public function testTotalsCanBeLimitedToOneProvider(): void
    {
        $rina = $this->insertUser('rina@kurasiai.test');
        $this->insertUsage($rina, 10, 40);
        $this->insertUsage($rina, 100, 400, 'anthropic');

        $totals = (new UsageLogRepository($this->db))->totalsPerUser('anthropic');

        $this->assertSame(1, $totals[0]->generations);
        $this->assertEquals(new TokenUsage(100, 400), $totals[0]->usage);
    }

    private function insertUsage(int $userId, int $input, int $output, string $provider = 'fake'): void
    {
        $this->pdo->prepare(
            "INSERT INTO usage_logs (user_id, provider, input_tokens, output_tokens, created_at)
             VALUES (:user, :provider, :input, :output, '2026-10-05T09:00:00+07:00')"
        )->execute(['user' => $userId, 'provider' => $provider, 'input' => $input, 'output' => $output]);
    }
}
