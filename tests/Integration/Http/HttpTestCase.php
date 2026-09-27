<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Integration\Http;

use KurasiAI\Ai\AiProvider;
use KurasiAI\App;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\Role;
use KurasiAI\Http\Csrf;
use KurasiAI\Http\Request;
use KurasiAI\Http\Response;
use KurasiAI\Http\Session;
use KurasiAI\Persistence\Database;
use KurasiAI\Persistence\PromptTemplateRepository;
use KurasiAI\Persistence\UserRepository;
use KurasiAI\Support\Env;
use KurasiAI\Support\FixedClock;
use PHPUnit\Framework\TestCase;

/**
 * Tes HTTP tanpa server: Request dikirim langsung ke kernel (App::handle()), lalu Response diperiksa.
 * Database di memori, session berupa array, jam buatan. Setiap permintaan memakai objek App baru,
 * seperti di server sungguhan; yang bertahan hanya database dan isi session.
 */
abstract class HttpTestCase extends TestCase
{
    /** Password data tes, bukan rahasia sungguhan. */
    protected const PASSWORD = 'password-tes-kurasi';

    private static ?string $passwordHash = null;

    protected Database $db;
    protected FixedClock $clock;

    /** @var array<mixed> isi session, seperti $_SESSION */
    protected array $session = [];

    /** Mesin AI pengganti untuk tes tertentu; null berarti sesuai .env tes (FakeAiProvider). */
    protected ?AiProvider $aiProvider = null;

    protected function setUp(): void
    {
        $schema = file_get_contents(dirname(__DIR__, 3) . '/database/schema.sql');
        $this->assertIsString($schema);

        $this->db = Database::inMemory();
        $this->db->runScript($schema);
        $this->clock = FixedClock::at('2026-10-05 09:00');

        // Hash dibuat sekali untuk semua tes, dengan pengaturan bawaan (tidak perlu diperbarui saat login).
        self::$passwordHash ??= password_hash(self::PASSWORD, PASSWORD_DEFAULT);

        $users = new UserRepository($this->db);
        $rina = $users->add('Rina', 'rina@kurasiai.test', Role::Writer, self::$passwordHash, $this->clock->now());
        $users->add('Sari', 'sari@kurasiai.test', Role::Writer, self::$passwordHash, $this->clock->now());
        $users->add('Budi', 'budi@kurasiai.test', Role::Editor, self::$passwordHash, $this->clock->now());

        $templates = new PromptTemplateRepository($this->db);
        $templates->add($rina, 'Artikel edukasi', 'Tulis artikel tentang {{topik}} untuk {{pembaca}}.', ContentType::Article, $this->clock->now());
        $templates->add($rina, 'Caption kampanye', 'Buat caption Instagram tentang {{topik}}.', ContentType::Caption, $this->clock->now());
    }

    protected function app(): App
    {
        $app = App::fromEnv(Env::fromString('APP_ENV=testing'), dirname(__DIR__, 3))
            ->replaceDatabase($this->db)
            ->replaceSession(new Session($this->session))
            ->replaceClock($this->clock);

        return $this->aiProvider === null ? $app : $app->replaceAiProvider($this->aiProvider);
    }

    /**
     * @param array<string, string> $query
     */
    protected function get(string $path, array $query = []): Response
    {
        return $this->app()->handle(new Request('GET', $path, $query));
    }

    /**
     * Mengirim form. Token CSRF milik session tes ditambahkan otomatis, kecuali $withToken = false.
     *
     * @param array<string, mixed> $data
     */
    protected function post(string $path, array $data = [], bool $withToken = true): Response
    {
        if ($withToken) {
            $data[Csrf::FIELD] = (new Csrf(new Session($this->session)))->token();
        }

        return $this->app()->handle(new Request('POST', $path, [], $data));
    }

    protected function loginAs(string $email): void
    {
        $response = $this->post('/login', ['email' => $email, 'password' => self::PASSWORD]);

        $this->assertSame(303, $response->status, "Login sebagai $email seharusnya berhasil.");
    }

    protected function logout(): void
    {
        $this->post('/logout');
    }

    protected function assertRedirect(string $location, Response $response): void
    {
        $this->assertSame(303, $response->status);
        $this->assertSame($location, $response->header('Location'));
    }

    protected function draftStatus(int $id): string
    {
        $statement = $this->db->pdo()->prepare('SELECT status FROM drafts WHERE id = :id');
        $statement->execute(['id' => $id]);

        return (string) $statement->fetchColumn();
    }
}
