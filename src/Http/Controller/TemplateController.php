<?php

declare(strict_types=1);

namespace KurasiAI\Http\Controller;

use KurasiAI\Domain\PromptTemplate;
use KurasiAI\Exception\ValidationException;
use KurasiAI\Http\Form\TemplateForm;
use KurasiAI\Http\Request;
use KurasiAI\Http\Response;
use KurasiAI\Validation\ValidationErrors;

final class TemplateController extends Controller
{
    /**
     * @param array<string, int> $params
     */
    public function create(Request $request, array $params): Response
    {
        $this->requireUser();

        return $this->form(null, TemplateForm::empty(), new ValidationErrors());
    }

    /**
     * @param array<string, int> $params
     */
    public function store(Request $request, array $params): Response
    {
        $user = $this->requireUser();
        $this->verifyCsrf($request);

        $form = TemplateForm::fromInput($request->post());
        $errors = $this->app->validator()->validate($form);

        if ($errors->isEmpty()) {
            try {
                $this->app->templates()->add($user, $form->name, $form->text, $form->contentType(), $this->app->clock()->now());

                return $this->redirect('/', "Template \"{$form->name}\" tersimpan.");
            } catch (ValidationException $e) {
                // Aturan domain yang tidak diketahui form tampil di atas form.
                $errors = $errors->with(ValidationErrors::FORM, $e->getMessage());
            }
        }

        return $this->form(null, $form, $errors);
    }

    /**
     * @param array<string, int> $params
     */
    public function edit(Request $request, array $params): Response
    {
        $user = $this->requireUser();
        $template = $this->app->templates()->get($params['id']);

        // Form milik orang lain bahkan tidak ditampilkan.
        $template->authorizeUpdate($user);

        return $this->form($template, TemplateForm::fromTemplate($template), new ValidationErrors());
    }

    /**
     * @param array<string, int> $params
     */
    public function update(Request $request, array $params): Response
    {
        $user = $this->requireUser();
        $this->verifyCsrf($request);
        $template = $this->app->templates()->get($params['id']);
        $template->authorizeUpdate($user);

        $form = TemplateForm::fromInput($request->post());
        $errors = $this->app->validator()->validate($form);

        if ($errors->isEmpty()) {
            try {
                $template->update($user, $form->name, $form->text, $this->app->clock()->now());
                $this->app->templates()->save($template);

                return $this->redirect('/', "Template \"{$form->name}\" tersimpan.");
            } catch (ValidationException $e) {
                $errors = $errors->with(ValidationErrors::FORM, $e->getMessage());
            }
        }

        return $this->form($template, $form, $errors);
    }

    private function form(?PromptTemplate $template, TemplateForm $form, ValidationErrors $errors): Response
    {
        return $this->render('template-form', [
            'pageTitle' => $template === null ? 'Template baru' : 'Ubah template',
            'form' => $form,
            'errors' => $errors,
            'action' => $template === null ? '/templates/new' : "/templates/{$template->id}/edit",
        ]);
    }
}
