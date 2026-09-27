<?php

declare(strict_types=1);

use KurasiAI\App;
use KurasiAI\Exception\AuthenticationException;
use KurasiAI\Http\Form\LoginForm;
use KurasiAI\Validation\ValidationErrors;

require_once __DIR__ . '/../vendor/autoload.php';

App::run(dirname(__DIR__), function (App $app): void {
    if ($app->user() !== null) {
        $app->redirect('index.php');
    }

    $form = LoginForm::empty();
    $errors = new ValidationErrors();

    if ($app->isPost()) {
        // Form login juga butuh token CSRF: tanpa itu, situs lain bisa memasukkan Anda
        // ke akun milik penyerang tanpa Anda sadari.
        $app->csrf()->verify($_POST);

        $form = LoginForm::fromInput($_POST);
        $errors = $app->validator()->validate($form);

        if ($errors->isEmpty()) {
            try {
                $user = $app->auth()->attempt($form->email, $form->password);
                $app->sessionAuth()->login($user);
                $app->session()->flash("Selamat datang, {$user->name}.");
                $app->redirect('index.php');
            } catch (AuthenticationException $e) {
                $errors = $errors->with(ValidationErrors::FORM, $e->getMessage());
            }
        }
    }

    $app->render('login', [
        'pageTitle' => 'Masuk',
        // Password tidak pernah dikirim balik ke browser, bahkan saat login gagal.
        'email' => $form->email,
        'errors' => $errors,
    ]);
});
