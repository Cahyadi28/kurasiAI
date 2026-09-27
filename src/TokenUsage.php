<?php

declare(strict_types=1);

/**
 * Value object: jumlah token yang dipakai satu permintaan ke AI.
 */
class TokenUsage
{
    public function __construct(
        public readonly int $input,
        public readonly int $output,
    ) {
        if ($input < 0 || $output < 0) {
            throw new InvalidArgumentException('Jumlah token tidak boleh negatif.');
        }
    }

    /**
     * Named constructor: pemakaian nol, misalnya sebagai titik awal penjumlahan.
     */
    public static function zero(): self
    {
        return new self(input: 0, output: 0);
    }

    /**
     * Menjumlahkan berapa pun pemakaian menjadi satu object baru.
     */
    public static function sum(TokenUsage ...$usages): self
    {
        $total = self::zero();

        foreach ($usages as $usage) {
            $total = $total->add($usage);
        }

        return $total;
    }

    public function total(): int
    {
        return $this->input + $this->output;
    }

    /**
     * Menjumlahkan dua pemakaian menjadi object BARU. Object lama tidak berubah.
     */
    public function add(TokenUsage $other): TokenUsage
    {
        return new TokenUsage(
            input: $this->input + $other->input,
            output: $this->output + $other->output,
        );
    }

    public function equals(TokenUsage $other): bool
    {
        return $this->input === $other->input && $this->output === $other->output;
    }
}
