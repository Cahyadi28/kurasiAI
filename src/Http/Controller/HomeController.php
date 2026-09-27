<?php

declare(strict_types=1);

namespace KurasiAI\Http\Controller;

use KurasiAI\Domain\Draft;
use KurasiAI\Domain\DraftStatus;
use KurasiAI\Exception\ValidationException;
use KurasiAI\Http\Request;
use KurasiAI\Http\Response;
use KurasiAI\Persistence\DraftSort;
use KurasiAI\Persistence\SortDirection;

final class HomeController extends Controller
{
    /**
     * Beranda: template, dan draf milik pengguna dengan filter status serta urutan dari alamat.
     *
     * @param array<string, int> $params
     */
    public function index(Request $request, array $params): Response
    {
        $user = $this->requireUser();

        // Pilihan dari alamat (?sort=title&dir=asc&status=draft) selalu lewat whitelist enum.
        $sort = DraftSort::fromInput($request->query('sort'));
        $direction = SortDirection::fromInput($request->query('dir'));
        $status = $this->statusFilter($request->query('status'));

        $drafts = $this->app->drafts()->listByAuthor($user->id, $sort, $direction);

        if ($status !== null) {
            $drafts = array_values(array_filter($drafts, fn (Draft $draft): bool => $draft->status() === $status));
        }

        return $this->render('home', [
            'pageTitle' => 'Beranda',
            'templates' => $this->app->templates()->all(),
            'drafts' => $drafts,
            'sort' => $sort,
            'direction' => $direction,
            'status' => $status,
        ]);
    }

    private function statusFilter(?string $input): ?DraftStatus
    {
        if ($input === null || $input === '') {
            return null;
        }

        return DraftStatus::tryFrom($input) ?? throw new ValidationException("Status \"$input\" tidak dikenal.");
    }
}
