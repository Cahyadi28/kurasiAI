<?php

declare(strict_types=1);

namespace KurasiAI\Validation;

/**
 * Kumpulan pesan kesalahan per kolom form. Tidak bisa diubah; with() menghasilkan objek baru.
 */
final class ValidationErrors
{
    /** Kunci untuk pesan yang tidak milik satu kolom tertentu, misalnya dari aturan domain. */
    public const FORM = '_form';

    /**
     * @param array<string, list<string>> $messages
     */
    public function __construct(private readonly array $messages = [])
    {
    }

    public function isEmpty(): bool
    {
        return $this->messages === [];
    }

    public function has(string $field): bool
    {
        return isset($this->messages[$field]);
    }

    public function first(string $field): ?string
    {
        return $this->messages[$field][0] ?? null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function all(): array
    {
        return $this->messages;
    }

    public function with(string $field, string $message): self
    {
        $messages = $this->messages;
        $messages[$field][] = $message;

        return new self($messages);
    }
}
