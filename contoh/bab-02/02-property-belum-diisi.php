<?php

declare(strict_types=1);

class SimpleDraft
{
    public int $id;
    public string $title;
    public string $status;
}

$draft = new SimpleDraft();
$draft->id = 1;

echo $draft->title . PHP_EOL; // Error: title belum pernah diisi
