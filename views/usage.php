<?php

declare(strict_types=1);

/**
 * @var \KurasiAI\Http\View $this
 * @var string $pageTitle
 * @var list<\KurasiAI\Persistence\UsageTotal> $totals
 */
?>
<h1><?= $this->e($pageTitle) ?></h1>

<section class="card">
    <table>
        <tr><th>Pengguna</th><th>Generate</th><th>Token masuk</th><th>Token keluar</th><th>Total token</th></tr>
        <?php foreach ($totals as $total): ?>
            <tr>
                <td><?= $this->e($total->userName) ?></td>
                <td><?= $this->e($total->generations) ?></td>
                <td><?= $this->e($total->usage->input) ?></td>
                <td><?= $this->e($total->usage->output) ?></td>
                <td><?= $this->e($total->usage->total()) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <p class="hint">Penulis melihat pemakaiannya sendiri; Editor melihat semua pengguna.</p>
</section>
