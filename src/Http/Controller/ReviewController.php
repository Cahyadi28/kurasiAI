<?php

declare(strict_types=1);

namespace KurasiAI\Http\Controller;

use KurasiAI\Domain\Draft;
use KurasiAI\Domain\User;
use KurasiAI\Exception\ValidationException;
use KurasiAI\Http\Form\FormInput;
use KurasiAI\Http\Request;
use KurasiAI\Http\Response;
use KurasiAI\Support\IndonesianDate;

/**
 * Tombol alur review di halaman draf, dan antrean review untuk Editor.
 * Setiap tindakan diteruskan ke ReviewService; aturan hak akses dan status dijaga Draft.
 */
final class ReviewController extends Controller
{
    /**
     * @param array<string, int> $params
     */
    public function submit(Request $request, array $params): Response
    {
        return $this->act($request, $params['id'], 'dikirim ke review', function (Draft $draft, User $user): void {
            $this->app->review()->submit($draft, $user);
        });
    }

    /**
     * @param array<string, int> $params
     */
    public function approve(Request $request, array $params): Response
    {
        return $this->act($request, $params['id'], 'disetujui', function (Draft $draft, User $user): void {
            $this->app->review()->approve($draft, $user);
        });
    }

    /**
     * @param array<string, int> $params
     */
    public function reject(Request $request, array $params): Response
    {
        $note = FormInput::text($request->post(), 'note');

        return $this->act($request, $params['id'], 'ditolak', function (Draft $draft, User $user) use ($note): void {
            $this->app->review()->reject($draft, $user, $note);
        });
    }

    /**
     * @param array<string, int> $params
     */
    public function publish(Request $request, array $params): Response
    {
        return $this->act($request, $params['id'], 'diterbitkan', function (Draft $draft, User $user): void {
            $this->app->review()->publish($draft, $user);
        });
    }

    /**
     * Antrean review: draf yang menunggu Editor ini, dari yang paling lama menunggu.
     *
     * @param array<string, int> $params
     */
    public function queue(Request $request, array $params): Response
    {
        $user = $this->requireUser();
        $review = $this->app->review();

        $rows = [];

        foreach ($review->reviewQueue($user) as $draft) {
            $rows[] = ['draft' => $draft, 'waiting' => IndonesianDate::duration($review->waitingTime($draft))];
        }

        $names = [];

        foreach ($this->app->users()->all() as $account) {
            $names[$account->id] = $account->name;
        }

        return $this->render('review', ['pageTitle' => 'Antrean review', 'rows' => $rows, 'names' => $names]);
    }

    /**
     * Pola yang sama untuk keempat tombol: login, token, muat draf, lakukan, lalu kembali ke halaman draf.
     *
     * @param callable(Draft, User): void $action
     */
    private function act(Request $request, int $draftId, string $done, callable $action): Response
    {
        $user = $this->requireUser();
        $this->verifyCsrf($request);
        $draft = $this->app->drafts()->get($draftId);

        try {
            $action($draft, $user);
        } catch (ValidationException $e) {
            // Misalnya catatan penolakan kosong: tampilkan di halaman draf, jangan jadi halaman error.
            $this->app->session()->flashError($e->getMessage());

            return $this->redirect("/drafts/{$draft->id}");
        }

        return $this->redirect("/drafts/{$draft->id}", "Draf #{$draft->id} $done.");
    }
}
