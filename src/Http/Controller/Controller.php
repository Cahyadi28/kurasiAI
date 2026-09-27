<?php

declare(strict_types=1);

namespace KurasiAI\Http\Controller;

use KurasiAI\App;
use KurasiAI\Domain\User;
use KurasiAI\Http\LoginRequiredException;
use KurasiAI\Http\Request;
use KurasiAI\Http\Response;

/**
 * Induk semua controller: bantuan kecil yang dipakai hampir setiap halaman.
 * App dikirim utuh sebagai "kotak peralatan"; setiap controller mengambil yang ia butuhkan.
 */
abstract class Controller
{
    public function __construct(protected readonly App $app)
    {
    }

    /**
     * Pengguna yang login. Yang belum login dilempar LoginRequiredException,
     * yang oleh kernel diterjemahkan menjadi redirect ke /login.
     */
    protected function requireUser(): User
    {
        return $this->app->user() ?? throw new LoginRequiredException();
    }

    /**
     * Setiap POST yang mengubah data wajib membawa token CSRF (Bab 14).
     */
    protected function verifyCsrf(Request $request): void
    {
        $this->app->csrf()->verify($request->post());
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function render(string $view, array $data = [], int $status = 200): Response
    {
        return $this->app->page($view, $data, $status);
    }

    protected function redirect(string $location, ?string $flash = null): Response
    {
        if ($flash !== null) {
            $this->app->session()->flash($flash);
        }

        return Response::redirect($location);
    }
}
