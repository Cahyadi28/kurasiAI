<?php

declare(strict_types=1);

namespace KurasiAI;

use DateTimeZone;
use KurasiAI\Ai\AiProvider;
use KurasiAI\Ai\AnthropicProvider;
use KurasiAI\Ai\CostEstimator;
use KurasiAI\Ai\FakeAiProvider;
use KurasiAI\Ai\Http\CurlHttpClient;
use KurasiAI\Domain\User;
use KurasiAI\Exception\AccessDeniedException;
use KurasiAI\Exception\ConfigException;
use KurasiAI\Exception\InvalidStateException;
use KurasiAI\Exception\KurasiException;
use KurasiAI\Exception\NotFoundException;
use KurasiAI\Exception\ValidationException;
use KurasiAI\Http\Csrf;
use KurasiAI\Http\HttpException;
use KurasiAI\Http\LoginRequiredException;
use KurasiAI\Http\Request;
use KurasiAI\Http\Response;
use KurasiAI\Http\Router;
use KurasiAI\Http\Routes;
use KurasiAI\Http\Session;
use KurasiAI\Http\SessionAuth;
use KurasiAI\Http\View;
use KurasiAI\Persistence\Database;
use KurasiAI\Persistence\DraftRepository;
use KurasiAI\Persistence\LoginAttemptRepository;
use KurasiAI\Persistence\PromptTemplateRepository;
use KurasiAI\Persistence\SqliteDraftRepository;
use KurasiAI\Persistence\UsageLogRepository;
use KurasiAI\Persistence\UserRepository;
use KurasiAI\Service\AuthService;
use KurasiAI\Service\DraftGenerator;
use KurasiAI\Service\ReviewService;
use KurasiAI\Support\Clock;
use KurasiAI\Support\Env;
use KurasiAI\Support\SystemClock;
use KurasiAI\Validation\Validator;
use Throwable;

/**
 * Tempat merakit seluruh objek aplikasi (composition root), sekaligus kernel web:
 * menerima Request, meneruskannya ke router, dan menerjemahkan exception menjadi Response.
 * Setiap objek dibuat saat pertama kali diminta, lalu dipakai ulang selama satu permintaan.
 */
final class App
{
    private ?DateTimeZone $timezone = null;
    private ?Clock $clock = null;
    private ?Database $db = null;
    private ?UserRepository $users = null;
    private ?PromptTemplateRepository $templates = null;
    private ?DraftRepository $drafts = null;
    private ?Session $session = null;
    private ?Csrf $csrf = null;
    private ?View $view = null;
    private ?SessionAuth $sessionAuth = null;
    private ?AiProvider $aiProvider = null;

    private function __construct(
        public readonly Env $env,
        public readonly string $root,
    ) {
    }

    public static function fromEnvironment(string $root): self
    {
        return self::fromEnv(Env::fromFile($root . '/.env'), $root);
    }

    public static function fromEnv(Env $env, string $root): self
    {
        return new self($env, $root);
    }

    /**
     * Dipanggil satu kali oleh front controller (public/index.php) untuk setiap permintaan.
     */
    public static function run(string $root): void
    {
        try {
            $app = self::fromEnvironment($root);
        } catch (ConfigException $e) {
            // Tanpa .env belum ada tampilan maupun session; cukup teks biasa.
            (new Response('Konfigurasi belum lengkap: ' . $e->getMessage(), 500, ['Content-Type' => 'text/plain; charset=utf-8']))->send();

            return;
        }

        $app->handle(Request::fromGlobals())->send();
    }

    // ---- Pengganti untuk tes --------------------------------------------------------

    /** Tes memakai database :memory:, session berupa array, dan jam buatan. */
    public function replaceDatabase(Database $db): self
    {
        $this->db = $db;

        return $this;
    }

    public function replaceSession(Session $session): self
    {
        $this->session = $session;

        return $this;
    }

    public function replaceClock(Clock $clock): self
    {
        $this->clock = $clock;

        return $this;
    }

    public function replaceAiProvider(AiProvider $provider): self
    {
        $this->aiProvider = $provider;

        return $this;
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

    /**
     * Mesin AI sesuai AI_PROVIDER di .env. Bagian lain aplikasi hanya mengenal interface AiProvider.
     */
    public function aiProvider(): AiProvider
    {
        return $this->aiProvider ??= match ($this->env->get('AI_PROVIDER', 'fake')) {
            'fake' => new FakeAiProvider(),
            'anthropic' => new AnthropicProvider(
                new CurlHttpClient(),
                $this->env->required('AI_API_KEY'),
                $this->env->required('AI_MODEL'),
                $this->env->positiveInt('AI_TIMEOUT', 30),
            ),
            default => throw new ConfigException(
                'AI_PROVIDER "' . $this->env->get('AI_PROVIDER') . '" tidak dikenal. Pilihan: fake, anthropic.'
            ),
        };
    }

    /** Perkiraan biaya, atau null bila harga belum diisi di .env. */
    public function costEstimator(): ?CostEstimator
    {
        return CostEstimator::fromEnv($this->env);
    }

    public function generator(): DraftGenerator
    {
        return new DraftGenerator($this->aiProvider(), $this->drafts(), $this->clock());
    }

    public function review(): ReviewService
    {
        return new ReviewService($this->clock(), $this->drafts());
    }

    public function usage(): UsageLogRepository
    {
        return new UsageLogRepository($this->db());
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
            $this->view = new View($this->root . '/views');
            // Kolom tersembunyi berisi token CSRF, siap ditempel di setiap form (variabel $csrfInput).
            $this->view->share('csrfInput', sprintf(
                '<input type="hidden" name="%s" value="%s">',
                Csrf::FIELD,
                $this->view->e($this->csrf()->token()),
            ));
        }

        return $this->view;
    }

    public function auth(): AuthService
    {
        return new AuthService($this->users(), new LoginAttemptRepository($this->db()), $this->clock());
    }

    public function sessionAuth(): SessionAuth
    {
        return $this->sessionAuth ??= new SessionAuth($this->session(), $this->csrf(), $this->users(), $this->clock());
    }

    // ---- Pengguna yang sedang login -------------------------------------------------

    /** Pengguna yang sedang login, atau null. */
    public function user(): ?User
    {
        return $this->sessionAuth()->user();
    }

    // ---- Kernel web -----------------------------------------------------------------

    public function router(): Router
    {
        $router = new Router();
        Routes::register($router, $this);

        return $router;
    }

    /**
     * Mengubah satu Request menjadi satu Response. Semua jenis masalah diterjemahkan di sini.
     */
    public function handle(Request $request): Response
    {
        try {
            $response = $this->router()->dispatch($request);
        } catch (LoginRequiredException) {
            $this->session()->flash('Silakan masuk dulu.');
            $response = Response::redirect('/login');
        } catch (HttpException $e) {
            $response = $this->error($e->status, $e->status === 404 ? 'Tidak ditemukan' : 'Permintaan tidak sah', $e->getMessage());

            foreach ($e->headers as $name => $value) {
                $response = $response->withHeader($name, $value);
            }
        } catch (NotFoundException $e) {
            $response = $this->error(404, 'Tidak ditemukan', $e->getMessage());
        } catch (AccessDeniedException $e) {
            $response = $this->error(403, 'Akses ditolak', $e->getMessage());
        } catch (InvalidStateException $e) {
            $response = $this->error(409, 'Belum bisa dilakukan', $e->getMessage());
        } catch (ValidationException $e) {
            $response = $this->error(422, 'Permintaan tidak sah', $e->getMessage());
        } catch (KurasiException $e) {
            $response = $this->error(500, 'Terjadi masalah', $e->getMessage());
        } catch (Throwable $e) {
            // Masalah tak terduga (misalnya PDOException): detailnya hanya dicatat di log server,
            // pengguna cukup melihat pesan umum. Detail teknis bisa membocorkan isi aplikasi.
            error_log((string) $e);
            $response = $this->error(500, 'Terjadi masalah', 'Terjadi kesalahan di server. Coba lagi nanti.');
        }

        // Pertahanan tambahan: browser menolak skrip dari luar situs dan halaman dibingkai situs lain.
        return $response
            ->withHeader('Content-Security-Policy', "default-src 'self'")
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('X-Content-Type-Options', 'nosniff');
    }

    /**
     * Halaman lengkap: tampilan, pengguna yang login, dan pesan flash.
     *
     * @param array<string, mixed> $data
     */
    public function page(string $view, array $data = [], int $status = 200): Response
    {
        return new Response($this->view()->render($view, [
            ...$data,
            'currentUser' => $this->user(),
            'flashes' => $this->session()->takeFlashes(),
            'flashErrors' => $this->session()->takeErrors(),
        ]), $status);
    }

    private function error(int $status, string $title, string $message): Response
    {
        try {
            return $this->page('error', ['pageTitle' => $title, 'status' => $status, 'message' => $message], $status);
        } catch (Throwable) {
            // Tampilan pun gagal (misalnya database rusak saat membaca pengguna): cukup teks biasa.
            return new Response("$title: $message", $status, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
    }
}
