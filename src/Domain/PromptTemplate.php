<?php

declare(strict_types=1);

namespace KurasiAI\Domain;

use InvalidArgumentException;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Support\HasTimestamps;

/**
 * Template prompt yang bisa dipakai ulang, misalnya
 * "Tulis artikel tentang {{topik}} untuk {{pembaca}}."
 */
final class PromptTemplate
{
    use HasTimestamps;

    /** Nama variabel: huruf kecil dan garis bawah, di antara {{ dan }}. */
    public const VARIABLE_PATTERN = '/\{\{\s*([a-z_]+)\s*\}\}/';
    public const MAX_NAME_LENGTH = 100;

    private string $name;
    private string $text;

    public function __construct(
        string $name,
        string $text,
        public readonly ContentType $targetType,
    ) {
        $this->name = $this->validName($name);
        $this->text = $this->validText($text);
        $this->initializeTimestamps();
    }

    public function name(): string
    {
        return $this->name;
    }

    public function text(): string
    {
        return $this->text;
    }

    /**
     * Nama semua variabel di template, tanpa duplikat, sesuai urutan kemunculan.
     *
     * @return list<string>
     */
    public function variables(): array
    {
        preg_match_all(self::VARIABLE_PATTERN, $this->text, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * Mengisi semua variabel dan menghasilkan Prompt siap kirim ke AI.
     *
     * @param array<string, string> $values
     */
    public function render(array $values): Prompt
    {
        $missing = [];

        foreach ($this->variables() as $variable) {
            if (trim($values[$variable] ?? '') === '') {
                $missing[] = $variable;
            }
        }

        if ($missing !== []) {
            throw new InvalidArgumentException('Variabel belum diisi: ' . implode(', ', $missing) . '.');
        }

        $text = preg_replace_callback(
            self::VARIABLE_PATTERN,
            fn (array $match): string => trim($values[$match[1]]),
            $this->text,
        );

        return new Prompt($text ?? '');
    }

    public function update(string $name, string $text): void
    {
        $newName = $this->validName($name);
        $newText = $this->validText($text);

        $this->name = $newName;
        $this->text = $newText;
        $this->touch();
    }

    private function validName(string $name): string
    {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > self::MAX_NAME_LENGTH) {
            throw new InvalidArgumentException(
                'Nama template wajib diisi, maksimal ' . self::MAX_NAME_LENGTH . ' karakter.'
            );
        }

        return $name;
    }

    private function validText(string $text): string
    {
        $text = trim($text);

        if ($text === '' || mb_strlen($text) > Prompt::MAX_LENGTH) {
            throw new InvalidArgumentException(
                'Teks template wajib diisi, maksimal ' . Prompt::MAX_LENGTH . ' karakter.'
            );
        }

        return $text;
    }
}
