<?php

declare(strict_types=1);

namespace KurasiAI\Http\Form;

/**
 * Membaca nilai dari data kiriman form dengan aman.
 */
final class FormInput
{
    private function __construct()
    {
    }

    /**
     * Teks yang sudah dibuang spasi di awal dan akhirnya. Kunci yang tidak ada,
     * atau nilai yang bukan teks (misalnya name[]=x menjadi array), dianggap kosong.
     *
     * @param array<mixed> $input
     */
    public static function text(array $input, string $key): string
    {
        $value = $input[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }
}
