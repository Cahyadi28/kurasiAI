<?php

declare(strict_types=1);

class Greeting
{
    public function __construct(
        public string $name,
    ) {
    }

    public function text(): string
    {
        return "Halo, {$this->name}!";
    }
}

// FormalGreeting mewarisi semua isi Greeting, lalu mengganti (override) text().
class FormalGreeting extends Greeting
{
    public function text(): string
    {
        return 'Selamat pagi. ' . parent::text();
    }
}

$casual = new Greeting('Rina');
$formal = new FormalGreeting('Bapak Andi');

echo $casual->text() . PHP_EOL; // Halo, Rina!
echo $formal->text() . PHP_EOL; // Selamat pagi. Halo, Bapak Andi!
echo $formal->name . PHP_EOL;   // Bapak Andi (property diwarisi dari Greeting)
