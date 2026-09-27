<?php

declare(strict_types=1);

namespace KurasiAI;

use DateTimeZone;
use KurasiAI\Ai\FakeAiProvider;
use KurasiAI\Domain\User;
use KurasiAI\Exception\AccessDeniedException;
use KurasiAI\Exception\ConfigException;
use KurasiAI\Exception\InvalidStateException;
use KurasiAI\Exception\KurasiException;
use KurasiAI\Exception\NotFoundException;
use KurasiAI\Exception\ValidationException;
use KurasiAI\Http\Csrf;
use KurasiAI\Http\Session;
use KurasiAI\Http\View;
use KurasiAI\Persistence\Database;
use KurasiAI\Persistence\DraftRepository;
use KurasiAI\Persistence\PromptTemplateRepository;
use KurasiAI\Persistence\SqliteDraftRepository;
use KurasiAI\Persistence\UserRepository;
use KurasiAI\Service\DraftGenerator;
use KurasiAI\Service\ReviewService;
use KurasiAI\Support\Clock;
use KurasiAI\Support\Env;
use KurasiAI\Support\SystemClock;
use KurasiAI\Validation\Validator;
use Throwable;

/**
 * Tempat merakit seluruh objek aplikasi (composition root).
 * Setiap objek dibuat saat pertama kali diminta, lalu dipakai ulang selama satu permintaan.
 * Mulai Bab 16, bagian yang mengurus permintaan web pindah ke router dan controller.
 */
final class App
{
    private const ACTING_USER_KEY = 'acting_user_id';

    private ?DateTimeZone $timezone = null;
    private ?Clock $clock = null;
    private ?Database $db = null;
    private ?UserRepository $users = null;
    private ?PromptTemplateRepository $templates = null;
    private ?DraftRepository $drafts = null;
    private ?Session $session = null;
    private ?Csrf $csrf = null;
    private ?View $view = null;

    private function __construct(
        public readonly Env $env,
        public readonly string $root,
    ) {
    }

    public static function fromEnvironment(string $root): self
    {
        return new self(Env::fromFile($root . '/.env'), $root);
    }

    /**
     * Titik awal setiap halaman web: merakit App, lalu menjalankan isi halaman lewat handle().
     *
     * @param callable(self): void $page
     */
    public static function run(string $root, callable $page): void
    {
        try {
            $app = self::fromEnvironment($root);
        } catch (ConfigException $e) {
            // Tanpa .env belum ada tampilan maupun session; cukup teks biasa.
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Konfigurasi belum lengkap: ' . $e->getMessage();

            return;
        }

        $app->handle(fn () => $page($app));
    }

    // ---- Objek aplikasi -------------------------------------------------------------

    public function timezone(): DateTimeZone
    {
        return $this->timezone ??= $this->env->timezone();
    }

    public function clock(): Clock
    {
        return $this->clock ??= new SystemClock($this->timezone());
    }

    public function db(): Database
    {
        return $this->db ??= Database::fromEnv($this->env, $this->root);
    }

    public function users(): UserRepository
    {
        return $this->users ??= new UserRepository($this->db());
    }

    public function templates(): PromptTemplateRepository
    {
        return $this->templates ??= new PromptTemplateRepository($this->db(), $this->timezone());
    }

    public function drafts(): DraftRepository
    {
        return $this->drafts ??= new SqliteDraftRepository($this->db(), $this->timezone());
    }

    public function generator(): DraftGenerator
    {
        $provider = $this->env->get('AI_PROVIDER', 'fake');

        if ($provider !== 'fake') {
            throw new ConfigException("AI_PROVIDER \"$provider\" belum tersedia. Provider sungguhan dibahas di Bab Bonus.");
        }

        return new DraftGenerator(new FakeAiProvider(), $this->drafts(), $this->clock());
    }

    public function review(): ReviewService
    {
        return new ReviewService($this->clock(), $this->drafts());
    }

    public function validator(): Validator
    {
        return new Validator();
    }

    public function session(): Session
    {
        return $this->session ??= Session::start();
    }

    public function csrf(): Csrf
    {
        return $this->csrf ??= new Csrf($this->session());
    }

    public function view(): View
    {
        if ($this->view === null) {
            $this->view = new View($this->root . '/views', ['isLocal' => $this->isLocal()]);
            // Kolom tersembunyi berisi token CSRF, siap ditempel di setiap form (variabel $csrfInput).
            $this->view->share('csrfInput', sprintf(
                '<input type="hidden" name="%s" value="%s">',
                Csrf::FIELD,
                $this->view->e($this->csrf()->token()),
            ));
        }

        return $this->view;
    }

    // ---- Pengguna aktif (SEMENTARA, diganti login di Bab 15) -------------------------

    public function isLocal(): bool
    {
        return $this->env->get('APP_ENV') === 'local';
    }

    /**
     * Pengguna yang sedang "bertindak". Tanpa password, jadi hanya boleh di APP_ENV=local.
     */
    public function currentUser(): User
    {
        $this->ensureLocal();

        $id = $this->session()->get(self::ACTING_USER_KEY);

        if (is_int($id)) {
            try {
                return $this->users()->get($id);
            } catch (NotFoundException) {
                // Akun sudah tidak ada (misalnya database dibuat ulang): kembali ke akun pertama.
                $this->session()->remove(self::ACTING_USER_KEY);
            }
        }

        return $this->users()->all()[0] ?? throw new ConfigException('Belum ada akun. Jalankan dulu: php bin/seed.php');
    }

    public function actAs(int $userId): User
    {
        $this->ensureLocal();

        $user = $this->users()->get($userId);
        $this->session()->set(self::ACTING_USER_KEY, $user->id);

        return $user;
    }

    private function ensureLocal(): void
    {
        if (!$this->isLocal()) {
            throw new ConfigException('Menu "Bertindak sebagai" hanya untuk APP_ENV=local. Login tersedia mulai Bab 15.');
        }
    }

    // ---- Bantuan untuk halaman web --------------------------------------------------

    public function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    /**
     * Pindah ke halaman lain setelah POST berhasil (pola Post/Redirect/Get).
     */
    public function redirect(string $path): never
    {
        header('Location: ' . $path, true, 303);
        exit;
    }

    /**
     * Menampilkan halaman lengkap: tampilan, pengguna aktif, daftar akun, dan pesan flash.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $view, array $data = []): void
    {
        echo $this->view()->render($view, [
            ...$data,
            'currentUser' => $this->currentUser(),
            'users' => $this->users()->all(),
            'flashes' => $this->session()->takeFlashes(),
        ]);
    }

    /**
     * Menjalankan isi halaman dan menerjemahkan setiap jenis masalah menjadi respons yang tepat.
     */
    public function handle(callable $page): void
    {
        // Pertahanan tambahan: browser menolak skrip dari luar situs dan halaman dibingkai situs lain.
        header("Content-Security-Policy: default-src 'self'");
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');

        try {
            $page();
        } catch (NotFoundException $e) {
            $this->error(404, 'Tidak ditemukan', $e->getMessage());
        } catch (AccessDeniedException $e) {
            $this->error(403, 'Akses ditolak', $e->getMessage());
        } catch (InvalidStateException $e) {
            $this->error(409, 'Belum bisa dilakukan', $e->getMessage());
        } catch (ValidationException $e) {
            $this->error(422, 'Permintaan tidak sah', $e->getMessage());
        } catch (KurasiException $e) {
            $this->error(500, 'Terjadi masalah', $e->getMessage());
        } catch (Throwable $e) {
            // Masalah tak terduga (misalnya PDOException): detailnya hanya dicatat di log server,
            // pengguna cukup melihat pesan umum. Detail teknis bisa membocorkan isi aplikasi.
            error_log((string) $e);
            $this->error(500, 'Terjadi masalah', 'Terjadi kesalahan di server. Coba lagi nanti.');
        }
    }

    private function error(int $status, string $title, string $message): void
    {
        http_response_code($status);
        echo $this->view()->render('error', ['pageTitle' => $title, 'status' => $status, 'message' => $message]);
    }
}
