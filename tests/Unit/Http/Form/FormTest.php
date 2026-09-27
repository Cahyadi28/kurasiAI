<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Http\Form;

use DateTimeImmutable;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\PromptTemplate;
use KurasiAI\Http\Form\DraftForm;
use KurasiAI\Http\Form\LoginForm;
use KurasiAI\Http\Form\TemplateForm;
use KurasiAI\Validation\Validator;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase
{
    public function testTemplateFormTidiesInput(): void
    {
        $form = TemplateForm::fromInput([
            'name' => '  Caption kampanye ',
            'text' => 'Buat caption tentang {{topik}}.',
            'target_type' => 'caption',
            'extra' => 'diabaikan',
        ]);

        $this->assertSame('Caption kampanye', $form->name);
        $this->assertTrue((new Validator())->validate($form)->isEmpty());
        $this->assertSame(ContentType::Caption, $form->contentType());
    }

    public function testNonTextInputCountsAsEmpty(): void
    {
        // Misalnya kiriman name[]=x membuat $_POST['name'] menjadi array.
        $form = TemplateForm::fromInput(['name' => ['x'], 'text' => 'Teks', 'target_type' => 'caption']);

        $this->assertSame('', $form->name);
    }

    public function testTemplateFormRules(): void
    {
        $form = TemplateForm::fromInput([
            'name' => str_repeat('n', PromptTemplate::MAX_NAME_LENGTH + 1),
            'text' => '',
            'target_type' => 'podcast',
        ]);

        $this->assertSame(
            [
                'name' => ['Maksimal ' . PromptTemplate::MAX_NAME_LENGTH . ' karakter.'],
                'text' => ['Wajib diisi.'],
                'targetType' => ['Pilihan tidak dikenal.'],
            ],
            (new Validator())->validate($form)->all(),
        );
    }

    public function testTemplateFormFromExistingTemplate(): void
    {
        $template = new PromptTemplate(1, 1, 'Artikel', 'Tulis tentang {{topik}}.', ContentType::Article, new DateTimeImmutable());

        $form = TemplateForm::fromTemplate($template);

        $this->assertSame(['Artikel', 'Tulis tentang {{topik}}.', 'article'], [$form->name, $form->text, $form->targetType]);
    }

    public function testDraftFormUsesItsOwnMessages(): void
    {
        $errors = (new Validator())->validate(DraftForm::fromInput([]));

        $this->assertSame(['title' => ['Judul wajib diisi.'], 'body' => ['Isi wajib diisi.']], $errors->all());
    }

    public function testLoginFormKeepsSpacesInThePassword(): void
    {
        $form = LoginForm::fromInput(['email' => ' rina@kurasiai.test ', 'password' => ' rahasia ']);

        $this->assertSame('rina@kurasiai.test', $form->email);
        $this->assertSame(' rahasia ', $form->password, 'Spasi adalah bagian dari password.');
    }

    public function testLoginFormRequiresBothFields(): void
    {
        $errors = (new Validator())->validate(LoginForm::fromInput([]));

        $this->assertSame('Email wajib diisi.', $errors->first('email'));
        $this->assertSame('Password wajib diisi.', $errors->first('password'));
    }
}
