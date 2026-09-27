<?php

declare(strict_types=1);

/**
 * Pengguna KurasiAI: Penulis atau Editor.
 */
final class User
{
    public const MAX_NAME_LENGTH = 100;

    public readonly string $name;
    public readonly string $email;

    public function __construct(
        public readonly int $id,
        string $name,
        string $email,
        public readonly Role $role,
    ) {
        $name = trim($name);
        $email = strtolower(trim($email));

        if ($name === '' || mb_strlen($name) > self::MAX_NAME_LENGTH) {
            throw new InvalidArgumentException(
                'Nama pengguna wajib diisi, maksimal ' . self::MAX_NAME_LENGTH . ' karakter.'
            );
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Format email tidak valid.');
        }

        $this->name = $name;
        $this->email = $email;
    }

    public function isSameAs(User $other): bool
    {
        return $this->id === $other->id;
    }
}
