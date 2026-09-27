<?php

declare(strict_types=1);

namespace KurasiAI\Http\Controller;

use KurasiAI\Exception\AuthenticationException;
use KurasiAI\Http\Form\LoginForm;
use KurasiAI\Http\Request;
use KurasiAI\Http\Response;
use KurasiAI\Validation\ValidationErrors;

final class AuthController extends Controller
{
    /**
     * @param array<string, int> $params
     */
    public function showLogin(Request $request, array $params): Response
    {
        if ($this->app->user() !== null) {
            return $this->redirect('/');
        }

        return $this->loginPage(LoginForm::empty(), new ValidationErrors());
    }

    /**
     * @param array<string, int> $params
     */
    public function login(Request $request, array $params): Response
    {
        // Form login juga butuh token CSRF: tanpa itu, situs lain bisa memasukkan Anda
        // ke akun milik penyerang tanpa Anda sadari.
        $this->verifyCsrf($request);

        $form = LoginForm::fromInput($request->post());
        $errors = $this->app->validator()->validate($form);

        if ($errors->isEmpty()) {
            try {
                $user = $this->app->auth()->attempt($form->email, $form->password);
                $this->app->sessionAuth()->login($user);

                return $this->redirect('/', "Selamat datang, {$user->name}.");
            } catch (AuthenticationException $e) {
                $errors = $errors->with(ValidationErrors::FORM, $e->getMessage());
            }
        }

        return $this->loginPage($form, $errors);
    }

    /**
     * @param array<string, int> $params
     */
    public function logout(Request $request, array $params): Response
    {
        $this->verifyCsrf($request);
        $this->app->sessionAuth()->logout();

        return $this->redirect('/login', 'Anda sudah keluar.');
    }

    private function loginPage(LoginForm $form, ValidationErrors $errors): Response
    {
        return $this->render('login', [
            'pageTitle' => 'Masuk',
            // Password tidak pernah dikirim balik ke browser, bahkan saat login gagal.
            'email' => $form->email,
            'errors' => $errors,
        ]);
    }
}
