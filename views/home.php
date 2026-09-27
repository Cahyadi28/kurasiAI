<?php

declare(strict_types=1);

use KurasiAI\Domain\DraftStatus;
use KurasiAI\Persistence\DraftSort;
use KurasiAI\Persistence\SortDirection;
use KurasiAI\Support\IndonesianDate;

/**
 * @var \KurasiAI\Http\View $this
 * @var \KurasiAI\Domain\User $currentUser
 * @var list<\KurasiAI\Domain\PromptTemplate> $templates
 * @var list<\KurasiAI\Domain\Draft> $drafts
 * @var DraftSort $sort
 * @var SortDirection $direction
 * @var DraftStatus|null $status
 */
?>
<h1>Halo, <?= $this->e($currentUser->name) ?> <small><?= $this->e($currentUser->role->label()) ?></small></h1>

<section class="card">
    <h2>Template prompt</h2>
    <?php if ($templates === []): ?>
        <p class="empty">Belum ada template. <a href="/templates/new">Buat yang pertama.</a></p>
    <?php else: ?>
        <table>
            <tr><th>Nama</th><th>Jenis</th><th>Aksi</th></tr>
            <?php foreach ($templates as $template): ?>
                <tr>
                    <td><?= $this->e($template->name()) ?></td>
                    <td><?= $this->e($template->targetType->label()) ?></td>
                    <td>
                        <a href="/templates/<?= $this->e($template->id) ?>/generate">Pakai</a>
                        <?php if ($template->isCreatedBy($currentUser)): ?>
                            · <a href="/templates/<?= $this->e($template->id) ?>/edit">Ubah</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</section>

<section class="card">
    <h2>Draf saya</h2>

    <form method="get" action="/" class="sort-form">
        <label>Urutkan
            <select name="sort">
                <?php foreach (DraftSort::cases() as $option): ?>
                    <option value="<?= $this->e($option->value) ?>"<?= $option === $sort ? ' selected' : '' ?>><?= $this->e($option->label()) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Status
            <select name="status">
                <option value="">Semua</option>
                <?php foreach (DraftStatus::cases() as $option): ?>
                    <option value="<?= $this->e($option->value) ?>"<?= $option === $status ? ' selected' : '' ?>><?= $this->e($option->label()) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Arah
            <select name="dir">
                <option value="desc"<?= $direction === SortDirection::Desc ? ' selected' : '' ?>>Turun</option>
                <option value="asc"<?= $direction === SortDirection::Asc ? ' selected' : '' ?>>Naik</option>
            </select>
        </label>
        <button type="submit" class="secondary">Terapkan</button>
    </form>

    <?php if ($drafts === []): ?>
        <p class="empty">Belum ada draf<?= $status !== null ? ' dengan status ini' : '' ?>. Pilih template di atas, lalu klik "Pakai".</p>
    <?php else: ?>
        <table>
            <tr><th>#</th><th>Judul</th><th>Jenis</th><th>Status</th><th>Terakhir diubah</th></tr>
            <?php foreach ($drafts as $draft): ?>
                <tr>
                    <td><?= $this->e($draft->id) ?></td>
                    <td><a href="/drafts/<?= $this->e($draft->id) ?>"><?= $this->e($draft->title()) ?></a></td>
                    <td><?= $this->e($draft->content()->type()->label()) ?></td>
                    <td><span class="status status-<?= $this->e($draft->status()->value) ?>"><?= $this->e($draft->status()->label()) ?></span></td>
                    <td><?= $this->e(IndonesianDate::format($draft->updatedAt())) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</section>
