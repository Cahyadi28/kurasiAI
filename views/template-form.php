<?php

declare(strict_types=1);

use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Validation\ValidationErrors;

/**
 * @var \KurasiAI\Http\View $this
 * @var string $pageTitle
 * @var \KurasiAI\Http\Form\TemplateForm $form
 * @var ValidationErrors $errors
 * @var string $action
 * @var string $csrfInput
 */
?>
<h1><?= $this->e($pageTitle) ?></h1>

<?php if ($errors->has(ValidationErrors::FORM)): ?>
    <p class="errors"><?= $this->e($errors->first(ValidationErrors::FORM)) ?></p>
<?php endif; ?>

<form method="post" action="<?= $this->e($action) ?>" class="card">
    <?= $csrfInput ?>

    <label>Nama template
        <input type="text" name="name" value="<?= $this->e($form->name) ?>">
        <?php if ($errors->has('name')): ?><span class="field-error"><?= $this->e($errors->first('name')) ?></span><?php endif; ?>
    </label>

    <label>Jenis konten
        <select name="target_type">
            <option value="">— pilih —</option>
            <?php foreach (ContentType::cases() as $type): ?>
                <option value="<?= $this->e($type->value) ?>"<?= $type->value === $form->targetType ? ' selected' : '' ?>><?= $this->e($type->label()) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($errors->has('targetType')): ?><span class="field-error"><?= $this->e($errors->first('targetType')) ?></span><?php endif; ?>
    </label>

    <label>Teks prompt
        <textarea name="text"><?= $this->e($form->text) ?></textarea>
        <span class="hint">Tulis variabel dengan kurung kurawal ganda, misalnya {{topik}} atau {{pembaca}}.</span>
        <?php if ($errors->has('text')): ?><span class="field-error"><?= $this->e($errors->first('text')) ?></span><?php endif; ?>
    </label>

    <button type="submit">Simpan template</button>
</form>
