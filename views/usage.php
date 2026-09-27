<?php

declare(strict_types=1);

/**
 * @var \KurasiAI\Http\View $this
 * @var string $pageTitle
 * @var list<\KurasiAI\Persistence\UsageTotal> $totals
 * @var string $provider
 * @var \KurasiAI\Ai\CostEstimator|null $costs
 */
?>
<h1><?= $this->e($pageTitle) ?> <small><?= $this->e($provider) ?></small></h1>

<section class="card">
    <table>
        <tr>
            <th>Pengguna</th><th>Generate</th><th>Token masuk</th><th>Token keluar</th><th>Total token</th>
            <?php if ($costs !== null): ?><th>Perkiraan biaya</th><?php endif; ?>
        </tr>
        <?php foreach ($totals as $total): ?>
            <tr>
                <td><?= $this->e($total->userName) ?></td>
                <td><?= $this->e($total->generations) ?></td>
                <td><?= $this->e($total->usage->input) ?></td>
                <td><?= $this->e($total->usage->output) ?></td>
                <td><?= $this->e($total->usage->total()) ?></td>
                <?php if ($costs !== null): ?><td><?= $this->e($costs->format($total->usage)) ?></td><?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </table>
    <p class="hint">Penulis melihat pemakaiannya sendiri; Editor melihat semua pengguna.
        <?php if ($costs !== null): ?>
            Biaya dihitung dari harga di <code>.env</code> (<?= $this->e($costs->inputPerMillion) ?> dan
            <?= $this->e($costs->outputPerMillion) ?> USD per sejuta token masuk/keluar); angkanya perkiraan, bukan tagihan.
        <?php endif; ?>
    </p>
</section>
