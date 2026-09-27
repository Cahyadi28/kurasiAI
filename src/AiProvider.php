<?php

declare(strict_types=1);

/**
 * Kontrak untuk semua mesin AI. Siapa pun yang menerapkan interface ini
 * bisa dipakai KurasiAI, entah AI tiruan maupun AI sungguhan.
 */
interface AiProvider
{
    /**
     * Nama singkat penyedia, misalnya "fake".
     */
    public function name(): string;

    /**
     * Membuat konten dari prompt sesuai jenis yang diminta.
     */
    public function generate(Prompt $prompt, ContentType $type): AiResult;
}
