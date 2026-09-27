<?php

declare(strict_types=1);

namespace KurasiAI\Http\Controller;

use KurasiAI\Domain\Draft;
use KurasiAI\Domain\DraftStatus;
use KurasiAI\Domain\User;
use KurasiAI\Exception\AccessDeniedException;
use KurasiAI\Exception\ValidationException;
use KurasiAI\Http\Form\DraftForm;
use KurasiAI\Http\Form\FormInput;
use KurasiAI\Http\Request;
use KurasiAI\Http\Response;
use KurasiAI\Validation\ValidationErrors;

final class DraftController extends Controller
{
    /**
     * Form isian variabel template, lalu AI membuat drafnya.
     *
     * @param array<string, int> $params
     */
    public function generateForm(Request $request, array $params): Response
    {
        $this->requireUser();
        $template = $this->app->templates()->get($params['id']);

        return $this->render('generate', [
            'pageTitle' => 'Buat draf',
            'template' => $template,
            'values' => array_fill_keys($template->variables(), ''),
            'errors' => new ValidationErrors(),
        ]);
    }

    /**
     * @param array<string, int> $params
     */
    public function generate(Request $request, array $params): Response
    {
        $user = $this->requireUser();
        $this->verifyCsrf($request);
        $template = $this->app->templates()->get($params['id']);

        // Kolom isian dibuat dari variabel template, jadi hanya variabel itu yang dibaca.
        $submitted = $request->post()['values'] ?? [];
        $submitted = is_array($submitted) ? $submitted : [];
        $values = [];

        foreach ($template->variables() as $variable) {
            $values[$variable] = FormInput::text($submitted, $variable);
        }

        try {
            $draft = $this->app->generator()->generate($user, $template, $values);

            return $this->redirect(
                "/drafts/{$draft->id}",
                "Draf #{$draft->id} dibuat. Periksa dan rapikan isinya sebelum dikirim ke review.",
            );
        } catch (ValidationException $e) {
            // Misalnya "Variabel belum diisi: topik." dari PromptTemplate::render().
            return $this->render('generate', [
                'pageTitle' => 'Buat draf',
                'template' => $template,
                'values' => $values,
                'errors' => (new ValidationErrors())->with(ValidationErrors::FORM, $e->getMessage()),
            ]);
        }
    }

    /**
     * @param array<string, int> $params
     */
    public function show(Request $request, array $params): Response
    {
        $user = $this->requireUser();
        $draft = $this->visibleDraft($params['id'], $user);

        return $this->page($draft, $user, DraftForm::fromDraft($draft), new ValidationErrors());
    }

    /**
     * @param array<string, int> $params
     */
    public function revise(Request $request, array $params): Response
    {
        $user = $this->requireUser();
        $this->verifyCsrf($request);
        $draft = $this->visibleDraft($params['id'], $user);

        $form = DraftForm::fromInput($request->post());
        $errors = $this->app->validator()->validate($form);

        if ($errors->isEmpty()) {
            try {
                // Aturan jenis konten (panjang artikel, jumlah adegan, tagar) diperiksa objek Content.
                $content = $draft->content()->type()->createContent($form->title, $form->body);

                // Hak akses dan status diperiksa Draft, walaupun form disembunyikan untuk yang tidak berhak.
                $this->app->review()->revise($draft, $user, $content);

                return $this->redirect("/drafts/{$draft->id}", 'Revisi tersimpan.');
            } catch (ValidationException $e) {
                $errors = $errors->with(ValidationErrors::FORM, $e->getMessage());
            }
        }

        return $this->page($draft, $user, $form, $errors);
    }

    private function visibleDraft(int $id, User $user): Draft
    {
        $draft = $this->app->drafts()->get($id);

        if (!$draft->isVisibleTo($user)) {
            throw new AccessDeniedException('Anda tidak berhak melihat draf ini.');
        }

        return $draft;
    }

    private function page(Draft $draft, User $user, DraftForm $form, ValidationErrors $errors): Response
    {
        $names = [];

        foreach ($this->app->users()->all() as $account) {
            $names[$account->id] = $account->name;
        }

        $isOwner = $draft->isOwnedBy($user);
        $isReviewer = $user->role->canReview() && !$isOwner;
        $status = $draft->status();

        return $this->render('draft', [
            'pageTitle' => $draft->title(),
            'draft' => $draft,
            'canEdit' => $isOwner && $status->isEditable(),
            // Tombol hanya ditampilkan bila masuk akal. Ini kenyamanan, bukan pengaman:
            // setiap tindakan tetap diperiksa lagi oleh Draft di server.
            'actions' => [
                'submit' => $isOwner && $status->canTransitionTo(DraftStatus::InReview),
                'approve' => $isReviewer && $status->canTransitionTo(DraftStatus::Approved),
                'reject' => $isReviewer && $status->canTransitionTo(DraftStatus::Rejected),
                'publish' => $isOwner && $status->canTransitionTo(DraftStatus::Published),
            ],
            'form' => $form,
            'errors' => $errors,
            'names' => $names,
        ]);
    }
}
