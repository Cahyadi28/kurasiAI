<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

function generateDraft(AiProvider $ai, int $id, Prompt $prompt, ContentType $type): Draft
{
    $result = $ai->generate($prompt, $type);

    return new Draft(
        id: $id,
        content: $type->createContent($result->title, $result->body),
        prompt: $prompt,
        usage: $result->usage,
    );
}

$templates = [
    new PromptTemplate(
        'Artikel edukasi',
        'Tulis artikel singkat tentang {{topik}} untuk {{pembaca}}.',
        ContentType::Article,
    ),
    new PromptTemplate(
        'Video satu menit',
        'Buat naskah video pendek tentang {{topik}}.',
        ContentType::VideoScript,
    ),
    new PromptTemplate(
        'Caption kampanye',
        'Buat caption Instagram tentang {{topik}} dengan nada {{nada}}.',
        ContentType::Caption,
    ),
];

$values = [
    ['topik' => 'energi surya di rumah', 'pembaca' => 'pelajar SMA'],
    ['topik' => 'belajar PHP'],
    ['topik' => 'hemat air', 'nada' => 'ceria'],
];

echo 'Perpustakaan template:' . PHP_EOL;

foreach ($templates as $template) {
    $variables = implode(', ', $template->variables());
    echo "   - {$template->name()} ({$template->targetType->label()}) variabel: $variables" . PHP_EOL;
}

$ai = new FakeAiProvider();
$drafts = [];

echo PHP_EOL . 'Draf dari template:' . PHP_EOL;

foreach ($templates as $index => $template) {
    $prompt = $template->render($values[$index]);
    $draft = generateDraft($ai, $index + 1, $prompt, $template->targetType);
    $drafts[] = $draft;

    echo "#{$draft->id} {$draft->title()}" . PHP_EOL;
    echo "   Prompt: {$prompt->text}" . PHP_EOL;
}

$usages = array_map(fn (Draft $draft): TokenUsage => $draft->usage ?? TokenUsage::zero(), $drafts);
$total = TokenUsage::sum(...$usages);

echo PHP_EOL . "Total token: {$total->total()}" . PHP_EOL;

$drafts[0]->submit();
$updatedAfterCreated = $drafts[0]->updatedAt() >= $drafts[0]->createdAt() ? 'ya' : 'tidak';
echo "Draf #1 dikirim ke review; waktu ubah tidak lebih awal dari waktu dibuat: $updatedAfterCreated" . PHP_EOL;

echo PHP_EOL . 'Mencoba template dengan variabel yang kurang:' . PHP_EOL;

try {
    $templates[0]->render(['topik' => 'kebun sekolah']);
} catch (InvalidArgumentException $e) {
    echo '   Ditolak: ' . $e->getMessage() . PHP_EOL;
}
