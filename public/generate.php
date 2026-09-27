<?php

declare(strict_types=1);

use KurasiAI\App;
use KurasiAI\Exception\ValidationException;
use KurasiAI\Http\Form\FormInput;
use KurasiAI\Validation\ValidationErrors;

require_once __DIR__ . '/../vendor/autoload.php';

App::run(dirname(__DIR__), function (App $app): void {
    $user = $app->currentUser();
    $id = filter_var($_GET['template'] ?? null, FILTER_VALIDATE_INT);
    $template = $app->templates()->get($id === false ? 0 : $id);

    // Kolom isian dibuat dari variabel template, jadi hanya variabel itu yang dibaca.
    $submitted = is_array($_POST['values'] ?? null) ? $_POST['values'] : [];
    $values = [];

    foreach ($template->variables() as $variable) {
        $values[$variable] = FormInput::text($submitted, $variable);
    }

    $errors = new ValidationErrors();

    if ($app->isPost()) {
        $app->csrf()->verify($_POST);

        try {
            $draft = $app->generator()->generate($user, $template, $values);
            $app->session()->flash("Draf #{$draft->id} dibuat. Periksa dan rapikan isinya sebelum dikirim ke review.");
            $app->redirect("draft.php?id={$draft->id}");
        } catch (ValidationException $e) {
            // Misalnya "Variabel belum diisi: topik." dari PromptTemplate::render().
            $errors = $errors->with(ValidationErrors::FORM, $e->getMessage());
        }
    }

    $app->render('generate', [
        'pageTitle' => 'Buat draf',
        'template' => $template,
        'values' => $values,
        'errors' => $errors,
    ]);
});
