<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Validation;

use KurasiAI\Validation\MaxLength;
use KurasiAI\Validation\Required;
use KurasiAI\Validation\ValidationErrors;
use KurasiAI\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function testCollectsOneMessagePerInvalidField(): void
    {
        $form = new class ('', str_repeat('a', 12), 'bebas') {
            public function __construct(
                #[Required, MaxLength(10)]
                public readonly string $title,
                #[Required, MaxLength(10)]
                public readonly string $body,
                public readonly string $withoutRules,
            ) {
            }
        };

        $errors = (new Validator())->validate($form);

        $this->assertSame(
            ['title' => ['Wajib diisi.'], 'body' => ['Maksimal 10 karakter.']],
            $errors->all(),
        );
    }

    public function testValidFormHasNoErrors(): void
    {
        $form = new class ('Rina') {
            public function __construct(
                #[Required]
                public readonly string $name,
            ) {
            }
        };

        $this->assertTrue((new Validator())->validate($form)->isEmpty());
    }

    public function testErrorsAreImmutable(): void
    {
        $empty = new ValidationErrors();
        $withMessage = $empty->with(ValidationErrors::FORM, 'Caption maksimal 2200 karakter.');

        $this->assertTrue($empty->isEmpty());
        $this->assertTrue($withMessage->has(ValidationErrors::FORM));
        $this->assertSame('Caption maksimal 2200 karakter.', $withMessage->first(ValidationErrors::FORM));
        $this->assertNull($withMessage->first('title'));
    }
}
