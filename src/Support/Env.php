<?php

declare(strict_types=1);

namespace KurasiAI\Support;

use DateTimeZone;
use Exception;
use KurasiAI\Exception\ConfigException;

/**
 * Isi berkas .env: pasangan KUNCI=nilai. Objek ini dikirim ke bagian yang membutuhkannya;
 * tidak ada yang ditulis ke putenv() atau $_ENV.
 */
final class Env
{
    private const KEY_PATTERN = '/^[A-Z][A-Z0-9_]*$/';

    /**
     * @param array<string, string> $values
     */
    public function __construct(private readonly array $values)
    {
    }

    public static function fromFile(string $path): self
    {
        if (!is_file($path)) {
            throw new ConfigException(
                'Berkas ' . basename($path) . ' tidak ditemukan. Salin .env.example menjadi .env lalu sesuaikan isinya.'
            );
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new ConfigException('Berkas ' . basename($path) . ' tidak bisa dibaca.');
        }

        return self::fromString($content);
    }

    public static function fromString(string $content): self
    {
        $values = [];
        $lines = preg_split('/\R/', $content) ?: [];

        foreach ($lines as $index => $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $lineNumber = $index + 1;

            if (!str_contains($line, '=')) {
                throw new ConfigException("Baris $lineNumber di .env harus berbentuk KUNCI=nilai.");
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);

            if (preg_match(self::KEY_PATTERN, $key) !== 1) {
                throw new ConfigException("Nama kunci \"$key\" di baris $lineNumber tidak sah. Pakai huruf besar, angka, dan _.");
            }

            $values[$key] = self::cleanValue(trim($value));
        }

        return new self($values);
    }

    /**
     * Nilai sebuah kunci, atau $default bila kunci tidak ada atau kosong.
     */
    public function get(string $key, string $default = ''): string
    {
        $value = $this->values[$key] ?? '';

        return $value === '' ? $default : $value;
    }

    /**
     * Nilai yang wajib diisi. Kunci yang tidak ada atau kosong dianggap salah konfigurasi.
     */
    public function required(string $key): string
    {
        $value = $this->get($key);

        if ($value === '') {
            throw new ConfigException("$key di .env wajib diisi.");
        }

        return $value;
    }

    /**
     * Bilangan bulat positif, misalnya AI_TIMEOUT=30. Kosong berarti $default.
     */
    public function positiveInt(string $key, int $default): int
    {
        $value = $this->get($key);

        if ($value === '') {
            return $default;
        }

        if (preg_match('/^[1-9][0-9]*$/', $value) !== 1) {
            throw new ConfigException("$key di .env harus berupa bilangan bulat positif, contoh: $default.");
        }

        return (int) $value;
    }

    /**
     * Path berkas dari .env. Path relatif dianggap relatif terhadap folder proyek.
     */
    public function path(string $key, string $projectRoot): string
    {
        $path = $this->required($key);

        // Path absolut: diawali / (macOS/Linux) atau huruf drive seperti C:\ (Windows).
        $isAbsolute = preg_match('#^([A-Za-z]:)?[\\\\/]#', $path) === 1;

        return $isAbsolute ? $path : rtrim($projectRoot, '/\\') . '/' . $path;
    }

    public function timezone(): DateTimeZone
    {
        $name = $this->get('APP_TIMEZONE', SystemClock::DEFAULT_TIMEZONE);

        try {
            return new DateTimeZone($name);
        } catch (Exception $e) {
            throw new ConfigException("APP_TIMEZONE \"$name\" bukan zona waktu yang dikenal, contoh yang benar: Asia/Jakarta.", previous: $e);
        }
    }

    /**
     * Nilai berkutip diambil apa adanya; nilai tanpa kutip dibuang komentarnya (" #...").
     */
    private static function cleanValue(string $value): string
    {
        if (preg_match('/^"(.*)"$/', $value, $match) === 1 || preg_match("/^'(.*)'$/", $value, $match) === 1) {
            return $match[1];
        }

        $commentStart = strpos($value, ' #');

        return $commentStart === false ? $value : rtrim(substr($value, 0, $commentStart));
    }
}
