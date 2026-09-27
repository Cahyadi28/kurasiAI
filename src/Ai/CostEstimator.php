<?php

declare(strict_types=1);

namespace KurasiAI\Ai;

use KurasiAI\Domain\TokenUsage;
use KurasiAI\Exception\ConfigException;
use KurasiAI\Support\Env;

/**
 * Perkiraan biaya pemakaian AI dari jumlah token. Harganya dibaca dari .env,
 * karena harga layanan bisa berubah kapan saja. Hasilnya perkiraan, bukan tagihan.
 */
final class CostEstimator
{
    /**
     * @param float $inputPerMillion  harga (USD) per sejuta token masuk
     * @param float $outputPerMillion harga (USD) per sejuta token keluar
     */
    public function __construct(
        public readonly float $inputPerMillion,
        public readonly float $outputPerMillion,
    ) {
        if ($inputPerMillion < 0 || $outputPerMillion < 0) {
            throw new ConfigException('Harga token tidak boleh negatif.');
        }
    }

    /**
     * Null bila harga belum diisi di .env: perkiraan biaya tidak ditampilkan.
     */
    public static function fromEnv(Env $env): ?self
    {
        $input = $env->get('AI_PRICE_INPUT_PER_MTOK');
        $output = $env->get('AI_PRICE_OUTPUT_PER_MTOK');

        if ($input === '' || $output === '') {
            return null;
        }

        if (!is_numeric($input) || !is_numeric($output)) {
            throw new ConfigException('AI_PRICE_INPUT_PER_MTOK dan AI_PRICE_OUTPUT_PER_MTOK harus berupa angka, contoh: 1 atau 0.25.');
        }

        return new self((float) $input, (float) $output);
    }

    /** Perkiraan biaya dalam USD. */
    public function estimate(TokenUsage $usage): float
    {
        return ($usage->input * $this->inputPerMillion + $usage->output * $this->outputPerMillion) / 1_000_000;
    }

    /** Contoh: "$0.0012". Empat angka di belakang koma, karena biaya per draf biasanya sangat kecil. */
    public function format(TokenUsage $usage): string
    {
        return '$' . number_format($this->estimate($usage), 4);
    }
}
