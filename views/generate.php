<?php

declare(strict_types=1);

use KurasiAI\Validation\ValidationErrors;

/**
 * @var \KurasiAI\Http\View $this
 * @var \KurasiAI\Domain\PromptTemplate $template
 * @var array<string, string> $values
 * @var ValidationErrors $errors
 * @var string $csrfInput
 */
?>
<h1>Buat draf: <?= $this->e($template->name()) ?></h1>

<?php if ($errors->has(ValidationErrors::FORM)): ?>
    <p class="errors"><?= $this->e($errors->first(ValidationErrors::FORM)) ?></p>
<?php endif; ?>

<form method="post" action="generate.php?template=<?= $this->e($template->id) ?>" class="card">
    <?= $csrfInput ?>

    <p class="hint">Prompt: <?= $this->e($template->text()) ?></p>
    <p class="hint">Jenis konten: <?= $this->e($template->targetType->label()) ?></p>

    <?php foreach ($template->variables() as $variable): ?>
        <label><?= $this->e($variable) ?>
            <input type="text" name="values[<?= $this->e($variable) ?>]" value="<?= $this->e($values[$variable] ?? '') ?>">
        </label>
    <?php endforeach; ?>

    <button type="submit">Buat draf dengan AI</button>
</form>
