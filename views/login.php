<?php

declare(strict_types=1);

use KurasiAI\Validation\ValidationErrors;

/**
 * @var \KurasiAI\Http\View $this
 * @var string $pageTitle
 * @var string $email
 * @var ValidationErrors $errors
 * @var string $csrfInput
 */
?>
<h1><?= $this->e($pageTitle) ?></h1>

<?php if ($errors->has(ValidationErrors::FORM)): ?>
    <p class="errors"><?= $this->e($errors->first(ValidationErrors::FORM)) ?></p>
<?php endif; ?>

<form method="post" action="login.php" class="card login">
    <?= $csrfInput ?>

    <label>Email
        <input type="email" name="email" value="<?= $this->e($email) ?>" autocomplete="username" required>
        <?php if ($errors->has('email')): ?><span class="field-error"><?= $this->e($errors->first('email')) ?></span><?php endif; ?>
    </label>

    <label>Password
        <input type="password" name="password" autocomplete="current-password" required>
        <?php if ($errors->has('password')): ?><span class="field-error"><?= $this->e($errors->first('password')) ?></span><?php endif; ?>
    </label>

    <button type="submit">Masuk</button>
</form>

<p class="empty">Akun contoh dibuat oleh <code>php bin/seed.php</code>. Passwordnya adalah nilai
    <code>SEED_PASSWORD</code> di berkas <code>.env</code> Anda.</p>
