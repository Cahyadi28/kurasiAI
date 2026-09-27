<?php

declare(strict_types=1);

namespace KurasiAI\Http;

use KurasiAI\Exception\AccessDeniedException;

/**
 * Perlindungan CSRF: setiap form yang mengubah data membawa token rahasia milik session ini.
 * Form palsu dari situs lain tidak tahu tokennya, sehingga kirimannya ditolak.
 */
final class Csrf
{
    /** Nama kolom tersembunyi di setiap form. */
    public const FIELD = '_csrf';

    private const SESSION_KEY = '_csrf_token';

    public function __construct(private readonly Session $session)
    {
    }

    /**
     * Token milik session ini. Dibuat sekali, lalu dipakai ulang sampai session berakhir.
     */
    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);

        if (!is_string($token)) {
            // 32 byte acak dari sumber yang aman untuk kriptografi, ditulis sebagai 64 karakter heksadesimal.
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public function isValid(mixed $submitted): bool
    {
        $token = $this->session->get(self::SESSION_KEY);

        // hash_equals() membandingkan dengan waktu yang sama panjang, apa pun isinya,
        // sehingga penyerang tidak bisa menebak token sedikit demi sedikit dari lamanya jawaban.
        return is_string($token) && is_string($submitted) && hash_equals($token, $submitted);
    }

    /**
     * @param array<mixed> $input data kiriman form ($_POST)
     */
    public function verify(array $input): void
    {
        if (!$this->isValid($input[self::FIELD] ?? null)) {
            throw new AccessDeniedException(
                'Form ini kedaluwarsa atau tidak dikirim dari KurasiAI. Muat ulang halaman, lalu coba lagi.'
            );
        }
    }
}
