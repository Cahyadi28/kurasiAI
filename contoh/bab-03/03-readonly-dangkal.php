<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

// Class kecil yang menyimpan sebuah Draft di property readonly.
class Archive
{
    public function __construct(
        public readonly Draft $draft,
    ) {
    }
}

$archive = new Archive(new Draft(
    id: 1,
    title: 'Judul awal',
    body: 'Isi awal.',
    type: 'article',
    prompt: new Prompt('Tulis sesuatu.'),
));

// $archive->draft = ...;  <- tidak boleh, property-nya readonly

// Tetapi isi object Draft di dalamnya tetap bisa berubah:
$archive->draft->revise(title: 'Judul diubah', body: 'Isi diubah.');

echo $archive->draft->title() . PHP_EOL; // Judul diubah
