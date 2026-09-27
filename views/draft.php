<?php

declare(strict_types=1);

use KurasiAI\Support\IndonesianDate;
use KurasiAI\Validation\ValidationErrors;

/**
 * @var \KurasiAI\Http\View $this
 * @var \KurasiAI\Domain\Draft $draft
 * @var bool $canEdit
 * @var array{submit: bool, approve: bool, reject: bool, publish: bool} $actions
 * @var \KurasiAI\Http\Form\DraftForm $form
 * @var ValidationErrors $errors
 * @var array<int, string> $names   nama pengguna berdasarkan nomornya
 * @var string $csrfInput
 */
?>
<h1><?= $this->e($draft->title()) ?> <small>#<?= $this->e($draft->id) ?></small></h1>

<section class="card">
    <header>
        <span class="label"><?= $this->e($draft->content()->type()->label()) ?></span>
        <span class="status status-<?= $this->e($draft->status()->value) ?>"><?= $this->e($draft->status()->label()) ?></span>
        <span class="label"><?= $this->e($draft->content()->metaLabel()) ?></span>
    </header>
    <p class="hint">
        Penulis: <?= $this->e($names[$draft->authorId] ?? '?') ?> ·
        dibuat <?= $this->e(IndonesianDate::format($draft->createdAt())) ?> ·
        diubah <?= $this->e(IndonesianDate::format($draft->updatedAt())) ?>
        <?php if ($draft->usage !== null): ?> · <?= $this->e($draft->usage->total()) ?> token<?php endif; ?>
    </p>
    <p class="hint">Prompt: <?= $this->e($draft->prompt->text) ?></p>

    <?php if ($draft->rejectionNote() !== null): ?>
        <p class="errors">Catatan penolakan terakhir: <?= $this->e($draft->rejectionNote()) ?></p>
    <?php endif; ?>
</section>

<?php if (in_array(true, $actions, true)): ?>
    <section class="card review-actions">
        <h2>Tindakan</h2>
        <div class="actions">
            <?php if ($actions['submit']): ?>
                <form method="post" action="/drafts/<?= $this->e($draft->id) ?>/submit">
                    <?= $csrfInput ?>
                    <button type="submit">Kirim ke review</button>
                </form>
            <?php endif; ?>
            <?php if ($actions['approve']): ?>
                <form method="post" action="/drafts/<?= $this->e($draft->id) ?>/approve">
                    <?= $csrfInput ?>
                    <button type="submit">Setujui</button>
                </form>
            <?php endif; ?>
            <?php if ($actions['publish']): ?>
                <form method="post" action="/drafts/<?= $this->e($draft->id) ?>/publish">
                    <?= $csrfInput ?>
                    <button type="submit">Terbitkan</button>
                </form>
            <?php endif; ?>
        </div>
        <?php if ($actions['reject']): ?>
            <form method="post" action="/drafts/<?= $this->e($draft->id) ?>/reject" class="reject-form">
                <?= $csrfInput ?>
                <label>Catatan penolakan (wajib)
                    <textarea name="note" rows="3"></textarea>
                </label>
                <button type="submit" class="danger">Tolak</button>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($canEdit): ?>
    <?php if ($errors->has(ValidationErrors::FORM)): ?>
        <p class="errors"><?= $this->e($errors->first(ValidationErrors::FORM)) ?></p>
    <?php endif; ?>

    <form method="post" action="/drafts/<?= $this->e($draft->id) ?>" class="card">
        <?= $csrfInput ?>

        <label>Judul
            <input type="text" name="title" value="<?= $this->e($form->title) ?>">
            <?php if ($errors->has('title')): ?><span class="field-error"><?= $this->e($errors->first('title')) ?></span><?php endif; ?>
        </label>

        <label>Isi
            <textarea name="body"><?= $this->e($form->body) ?></textarea>
            <?php if ($errors->has('body')): ?><span class="field-error"><?= $this->e($errors->first('body')) ?></span><?php endif; ?>
        </label>

        <button type="submit">Simpan revisi</button>
    </form>
<?php else: ?>
    <section class="card">
        <!-- nl2br() dipanggil SETELAH escaping, sehingga hanya <br> buatan kita yang menjadi HTML. -->
        <div class="body"><?= nl2br($this->e($draft->content()->body)) ?></div>
    </section>
<?php endif; ?>

<section class="card">
    <h2>Riwayat</h2>
    <?php if ($draft->history() === []): ?>
        <p class="empty">Belum pernah dikirim ke review.</p>
    <?php else: ?>
        <ol class="history">
            <?php foreach ($draft->history() as $change): ?>
                <li>
                    <?= $this->e(IndonesianDate::format($change->at)) ?> ·
                    <?= $this->e($names[$change->actorId] ?? '?') ?>:
                    <?= $this->e($change->from->label()) ?> → <?= $this->e($change->to->label()) ?>
                    <?php if ($change->note !== null): ?>(“<?= $this->e($change->note) ?>”)<?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</section>
