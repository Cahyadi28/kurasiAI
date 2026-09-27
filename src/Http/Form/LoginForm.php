<?php

declare(strict_types=1);

namespace KurasiAI\Http\Form;

use KurasiAI\Validation\MaxLength;
use KurasiAI\Validation\Required;

/**
 * Isian form login. Benar-tidaknya email dan password diperiksa AuthService, bukan di sini.
 */
final class LoginForm
{
    public function __construct(
        #[Required(message: 'Email wajib diisi.'), MaxLength(255)]
        public readonly string $email,
        #[Required(message: 'Password wajib diisi.'), MaxLength(255)]
        public readonly string $password,
    ) {
    }

    /**
     * @param array<mixed> $input
     */
    public static function fromInput(array $input): self
    {
        return new self(FormInput::text($input, 'email'), FormInput::secret($input, 'password'));
    }

    public static function empty(): self
    {
        return new self('', '');
    }
}
