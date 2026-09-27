<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Support;

use KurasiAI\Exception\ConfigException;
use KurasiAI\Support\Env;
use PHPUnit\Framework\TestCase;

class EnvTest extends TestCase
{
    public function testReadsKeysAndSkipsCommentsAndBlankLines(): void
    {
        $env = Env::fromString(<<<'ENV'
            # Pengaturan aplikasi
            APP_TIMEZONE=Asia/Makassar

            DB_PATH = var/kurasiai.sqlite
            ENV);

        $this->assertSame('Asia/Makassar', $env->get('APP_TIMEZONE'));
        $this->assertSame('var/kurasiai.sqlite', $env->get('DB_PATH'));
    }

    public function testQuotedValuesAreTakenAsIs(): void
    {
        $env = Env::fromString(<<<'ENV'
            APP_NAME="Kurasi AI #1"
            TAGLINE='AI membuat, manusia mengelola'
            DB_PATH=var/data.sqlite # komentar setelah nilai
            ENV);

        $this->assertSame('Kurasi AI #1', $env->get('APP_NAME'));
        $this->assertSame('AI membuat, manusia mengelola', $env->get('TAGLINE'));
        $this->assertSame('var/data.sqlite', $env->get('DB_PATH'));
    }

    public function testValueMayContainEqualsSign(): void
    {
        $env = Env::fromString('SEED_PASSWORD=abc=def==');

        $this->assertSame('abc=def==', $env->get('SEED_PASSWORD'));
    }

    public function testMissingOrEmptyKeyFallsBackToDefault(): void
    {
        $env = Env::fromString('AI_API_KEY=');

        $this->assertSame('', $env->get('AI_API_KEY'));
        $this->assertSame('fake', $env->get('AI_PROVIDER', 'fake'));
    }

    public function testRequiredKeyMustBeFilled(): void
    {
        $env = Env::fromString('SEED_PASSWORD=');

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('SEED_PASSWORD di .env wajib diisi.');

        $env->required('SEED_PASSWORD');
    }

    public function testRejectsLineWithoutEqualsSign(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Baris 2 di .env harus berbentuk KUNCI=nilai.');

        Env::fromString("APP_ENV=local\nDB_PATH");
    }

    public function testRejectsInvalidKeyName(): void
    {
        $this->expectException(ConfigException::class);

        Env::fromString('db-path=var/data.sqlite');
    }

    public function testMissingFileExplainsWhatToDo(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Salin .env.example menjadi .env');

        Env::fromFile(__DIR__ . '/tidak-ada/.env');
    }

    public function testReadsFromFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');
        $this->assertIsString($path);
        file_put_contents($path, "DB_PATH=var/test.sqlite\n");

        try {
            $this->assertSame('var/test.sqlite', Env::fromFile($path)->get('DB_PATH'));
        } finally {
            unlink($path);
        }
    }

    public function testRelativePathIsResolvedFromProjectRoot(): void
    {
        $this->assertSame('/proyek/var/data.sqlite', Env::fromString('DB_PATH=var/data.sqlite')->path('DB_PATH', '/proyek/'));
        $this->assertSame('/data/kurasi.sqlite', Env::fromString('DB_PATH=/data/kurasi.sqlite')->path('DB_PATH', '/proyek'));
        $this->assertSame('C:\data\kurasi.sqlite', Env::fromString('DB_PATH=C:\data\kurasi.sqlite')->path('DB_PATH', '/proyek'));
    }

    public function testTimezoneDefaultsToJakarta(): void
    {
        $this->assertSame('Asia/Jakarta', Env::fromString('')->timezone()->getName());
        $this->assertSame('Asia/Jayapura', Env::fromString('APP_TIMEZONE=Asia/Jayapura')->timezone()->getName());
    }

    public function testRejectsUnknownTimezone(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('APP_TIMEZONE "Jakarta" bukan zona waktu yang dikenal');

        Env::fromString('APP_TIMEZONE=Jakarta')->timezone();
    }
}
