<?php

declare(strict_types=1);

/**
 * @var \KurasiAI\Http\View $this
 * @var string $pageTitle
 * @var int $status
 * @var string $message
 */
?>
<section class="card">
    <h1><?= $this->e($pageTitle) ?> <small><?= $this->e($status) ?></small></h1>
    <p><?= $this->e($message) ?></p>
    <p><a href="/">Kembali ke beranda</a></p>
</section>
