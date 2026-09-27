<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

/**
 * Meminta AI membuat konten, lalu membungkusnya menjadi Draft.
 * Parameter $ai bertipe interface: provider apa pun boleh dipakai.
 */
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

$ai = new FakeAiProvider();

$requests = [
    ['Tulis artikel singkat tentang energi surya di rumah.', ContentType::Article],
    ['Buat naskah video pendek tentang belajar PHP.', ContentType::VideoScript],
    ['Buat caption Instagram tentang hemat air.', ContentType::Caption],
];

echo "Provider: {$ai->name()}" . PHP_EOL . PHP_EOL;

$drafts = [];
$totalUsage = new TokenUsage(input: 0, output: 0);

foreach ($requests as $index => [$promptText, $type]) {
    $draft = generateDraft($ai, $index + 1, new Prompt($promptText), $type);
    $drafts[] = $draft;

    $content = $draft->content();
    $usage = $draft->usage ?? new TokenUsage(input: 0, output: 0);
    $totalUsage = $totalUsage->add($usage);

    echo "#{$draft->id} {$draft->title()}" . PHP_EOL;
    echo "   {$content->type()->label()} | {$content->metaLabel()} | {$usage->total()} token" . PHP_EOL;
    echo "   {$content->summary(70)}" . PHP_EOL . PHP_EOL;
}

echo "Total token: {$totalUsage->total()} (input {$totalUsage->input}, output {$totalUsage->output})" . PHP_EOL;
