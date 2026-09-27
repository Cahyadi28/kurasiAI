<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

// TANPA dependency injection: class membuat sendiri kebutuhannya.
final class HardwiredGenerator
{
    private FakeAiProvider $ai;

    public function __construct()
    {
        $this->ai = new FakeAiProvider(); // terkunci pada FakeAiProvider selamanya
    }

    public function providerName(): string
    {
        return $this->ai->name();
    }
}

// DENGAN dependency injection: kebutuhan dikirim dari luar.
final class InjectedGenerator
{
    public function __construct(
        private readonly AiProvider $ai, // provider apa pun boleh
    ) {
    }

    public function providerName(): string
    {
        return $this->ai->name();
    }
}

echo (new HardwiredGenerator())->providerName() . PHP_EOL;                  // fake, tidak bisa diganti
echo (new InjectedGenerator(new FakeAiProvider()))->providerName() . PHP_EOL; // fake, bisa diganti kapan saja
