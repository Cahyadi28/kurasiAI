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
$draft->title = 'Energi surya';
$draft->status = 'draft';

echo "#{$draft->id} {$draft->title} ({$draft->status})" . PHP_EOL;
