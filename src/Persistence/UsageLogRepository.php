<?php

declare(strict_types=1);

namespace KurasiAI\Persistence;

use KurasiAI\Domain\TokenUsage;

/**
 * Membaca log pemakaian AI. Barisnya ditulis SqliteDraftRepository::add()
 * bersama draf yang dibuat AI, dalam satu transaksi.
 */
final class UsageLogRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /**
     * Total pemakaian setiap pengguna, termasuk yang belum pernah generate (nilainya nol).
     * Bila $provider diisi (misalnya 'anthropic'), hanya pemakaian provider itu yang dihitung.
     *
     * @return list<UsageTotal>
     */
    public function totalsPerUser(?string $provider = null): array
    {
        // Syarat provider ditaruh di ON, bukan WHERE, supaya pengguna tanpa pemakaian tetap muncul.
        $statement = $this->db->pdo()->prepare(
            'SELECT u.id, u.name,
                    COUNT(l.id) AS generations,
                    COALESCE(SUM(l.input_tokens), 0) AS input_tokens,
                    COALESCE(SUM(l.output_tokens), 0) AS output_tokens
             FROM users u
             LEFT JOIN usage_logs l ON l.user_id = u.id AND (:provider IS NULL OR l.provider = :provider)
             GROUP BY u.id, u.name
             ORDER BY u.id'
        );
        $statement->execute(['provider' => $provider]);
        $rows = $statement->fetchAll();

        return array_map(
            fn (array $row): UsageTotal => new UsageTotal(
                userId: (int) $row['id'],
                userName: (string) $row['name'],
                generations: (int) $row['generations'],
                usage: new TokenUsage((int) $row['input_tokens'], (int) $row['output_tokens']),
            ),
            $rows,
        );
    }

    public function totalFor(int $userId): TokenUsage
    {
        $statement = $this->db->pdo()->prepare(
            'SELECT COALESCE(SUM(input_tokens), 0) AS input_tokens, COALESCE(SUM(output_tokens), 0) AS output_tokens
             FROM usage_logs WHERE user_id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);
        $row = $statement->fetch();

        return $row === false
            ? TokenUsage::zero()
            : new TokenUsage((int) $row['input_tokens'], (int) $row['output_tokens']);
    }
}
