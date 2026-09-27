<?php

declare(strict_types=1);

class SimpleDraft
{
    public int $id;
    public string $title;
    public string $status;

    public function __construct(int $id, string $title, string $status = 'draft')
    {
        $this->id = $id;
        $this->title = $title;
        $this->status = $status;
    }
}

$draft = new SimpleDraft(1, 'Energi surya');

echo "#{$draft->id} {$draft->title} ({$draft->status})" . PHP_EOL;
