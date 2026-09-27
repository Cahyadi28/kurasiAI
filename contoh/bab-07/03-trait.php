<?php

declare(strict_types=1);

// Trait: sepotong kode yang "ditempelkan" ke class yang memakainya.
trait Describable
{
    // Class pemakai WAJIB punya method label().
    abstract public function label(): string;

    public function describe(): string
    {
        return static::class . ': ' . $this->label();
    }
}

// Dua class yang sama sekali bukan satu keluarga.
final class Student
{
    use Describable;

    public function label(): string
    {
        return 'Rina, kelas XI';
    }
}

final class Room
{
    use Describable;

    public function label(): string
    {
        return 'Laboratorium komputer';
    }
}

echo (new Student())->describe() . PHP_EOL; // Student: Rina, kelas XI
echo (new Room())->describe() . PHP_EOL;    // Room: Laboratorium komputer
