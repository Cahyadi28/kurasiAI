<?php

declare(strict_types=1);

use KurasiAI\App;
use KurasiAI\Exception\ValidationException;
use KurasiAI\Http\Form\TemplateForm;
use KurasiAI\Validation\ValidationErrors;

require_once __DIR__ . '/../vendor/autoload.php';

App::run(dirname(__DIR__), function (App $app): void {
    $user = $app->currentUser();
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

    // Mode ubah: template harus ada dan milik pengguna ini. Diperiksa juga saat GET,
    // supaya form milik orang lain bahkan tidak ditampilkan.
    $template = $id === false ? null : $app->templates()->get($id);
    $template?->authorizeUpdate($user);

    $form = $template === null ? TemplateForm::empty() : TemplateForm::fromTemplate($template);
    $errors = new ValidationErrors();

    if ($app->isPost()) {
        $app->csrf()->verify($_POST);

        $form = TemplateForm::fromInput($_POST);
        $errors = $app->validator()->validate($form);

        if ($errors->isEmpty()) {
            try {
                if ($template === null) {
                    $app->templates()->add($user, $form->name, $form->text, $form->contentType(), $app->clock()->now());
                } else {
                    $template->update($user, $form->name, $form->text, $app->clock()->now());
                    $app->templates()->save($template);
                }

                $app->session()->flash("Template \"{$form->name}\" tersimpan.");
                $app->redirect('index.php');
            } catch (ValidationException $e) {
                // Aturan domain yang tidak diketahui form tampil di atas form.
                $errors = $errors->with(ValidationErrors::FORM, $e->getMessage());
            }
        }
    }

    $app->render('template-form', [
        'pageTitle' => $template === null ? 'Template baru' : 'Ubah template',
        'form' => $form,
        'errors' => $errors,
        'action' => $template === null ? 'template-form.php' : "template-form.php?id={$template->id}",
    ]);
});
