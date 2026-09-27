<?php

declare(strict_types=1);

namespace KurasiAI\Http;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Merender berkas tampilan di folder views/. Setiap halaman dibungkus views/layout.php.
 * Di dalam berkas tampilan, $this->e() dipakai untuk setiap nilai yang ditampilkan.
 */
final class View
{
    /**
     * @param array<string, mixed> $shared data yang tersedia di semua tampilan
     */
    public function __construct(
        private readonly string $directory,
        private array $shared = [],
    ) {
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $name, array $data = []): string
    {
        $content = $this->renderFile($name, $data);

        return $this->renderFile('layout', [...$data, 'content' => $content]);
    }

    /**
     * Escaping untuk HTML: < > & " ' menjadi entitas, sehingga teks dari pengguna
     * selalu tampil sebagai teks, tidak pernah dijalankan sebagai HTML atau JavaScript.
     */
    public function e(string|int|float|null $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderFile(string $name, array $data): string
    {
        // Nama tampilan hanya huruf kecil dan tanda hubung, supaya tidak bisa menunjuk ke berkas lain (../).
        if (preg_match('/^[a-z-]+$/', $name) !== 1) {
            throw new InvalidArgumentException("Nama tampilan tidak sah: $name");
        }

        $file = $this->directory . '/' . $name . '.php';

        if (!is_file($file)) {
            throw new RuntimeException("Tampilan tidak ditemukan: $name");
        }

        return $this->capture($file, [...$this->shared, ...$data]);
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function capture(string $file, array $vars): string
    {
        // Setiap kunci menjadi variabel di berkas tampilan: ['title' => ...] menjadi $title.
        // EXTR_SKIP: variabel yang sudah ada ($file, $vars) tidak ditimpa.
        extract($vars, EXTR_SKIP);

        ob_start();

        try {
            require $file;

            return (string) ob_get_clean();
        } catch (Throwable $e) {
            ob_end_clean();

            throw $e;
        }
    }
}
