<?php

declare(strict_types=1);

use KurasiAI\Support\IndonesianDate;

/**
 * @var \KurasiAI\Http\View $this
 * @var string $pageTitle
 * @var list<array{draft: \KurasiAI\Domain\Draft, waiting: string}> $rows
 * @var array<int, string> $names
 */
?>
<h1><?= $this->e($pageTitle) ?></h1>

<section class="card">
    <?php if ($rows === []): ?>
        <p class="empty">Tidak ada draf yang menunggu review Anda.</p>
    <?php else: ?>
        <table>
            <tr><th>#</th><th>Judul</th><th>Penulis</th><th>Dikirim</th><th>Menunggu</th></tr>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= $this->e($row['draft']->id) ?></td>
                    <td><a href="/drafts/<?= $this->e($row['draft']->id) ?>"><?= $this->e($row['draft']->title()) ?></a></td>
                    <td><?= $this->e($names[$row['draft']->authorId] ?? '?') ?></td>
                    <td><?= $this->e(IndonesianDate::format($row['draft']->submittedAt() ?? $row['draft']->updatedAt())) ?></td>
                    <td><?= $this->e($row['waiting']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</section>
