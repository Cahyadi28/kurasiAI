<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

// POLA YANG KITA HINDARI: Singleton, yaitu satu-satunya object yang diambil dari mana saja.
final class AiRegistry
{
    private static ?AiProvider $instance = null;

    public static function getInstance(): AiProvider
    {
        if (self::$instance === null) {
            self::$instance = new FakeAiProvider();
        }

        return self::$instance;
    }

    // Untuk tes, orang terpaksa menambahkan "pintu belakang" seperti ini.
    public static function replace(AiProvider $provider): void
    {
        self::$instance = $provider;
    }
}

// Class ini TERLIHAT tidak butuh apa-apa, padahal diam-diam bergantung pada AiRegistry.
final class HiddenDependencyGenerator
{
    public function providerName(): string
    {
        return AiRegistry::getInstance()->name();
    }
}

echo (new HiddenDependencyGenerator())->providerName() . PHP_EOL; // fake
