<?php

declare(strict_types=1);

use KurasiAI\App;
use KurasiAI\Exception\AccessDeniedException;
use KurasiAI\Exception\ValidationException;
use KurasiAI\Http\Form\DraftForm;
use KurasiAI\Validation\ValidationErrors;

require_once __DIR__ . '/../vendor/autoload.php';

App::run(dirname(__DIR__), function (App $app): void {
    $user = $app->requireUser();
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    $draft = $app->drafts()->get($id === false ? 0 : $id);

    if (!$draft->isVisibleTo($user)) {
        throw new AccessDeniedException('Anda tidak berhak melihat draf ini.');
    }

    $canEdit = $draft->isOwnedBy($user) && $draft->status()->isEditable();
    $form = DraftForm::fromDraft($draft);
    $errors = new ValidationErrors();

    if ($app->isPost()) {
        $app->csrf()->verify($_POST);

        $form = DraftForm::fromInput($_POST);
        $errors = $app->validator()->validate($form);

        if ($errors->isEmpty()) {
            try {
                // Aturan jenis konten (panjang artikel, jumlah adegan, tagar) diperiksa objek Content.
                $content = $draft->content()->type()->createContent($form->title, $form->body);

                // Hak akses dan status diperiksa Draft, walaupun form disembunyikan untuk yang tidak berhak.
                $app->review()->revise($draft, $user, $content);

                $app->session()->flash('Revisi tersimpan.');
                $app->redirect("draft.php?id={$draft->id}");
            } catch (ValidationException $e) {
                $errors = $errors->with(ValidationErrors::FORM, $e->getMessage());
            }
        }
    }

    $names = [];

    foreach ($app->users()->all() as $account) {
        $names[$account->id] = $account->name;
    }

    $app->render('draft', [
        'pageTitle' => $draft->title(),
        'draft' => $draft,
        'canEdit' => $canEdit,
        'form' => $form,
        'errors' => $errors,
        'names' => $names,
    ]);
});
