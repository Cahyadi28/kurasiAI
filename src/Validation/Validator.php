<?php

declare(strict_types=1);

namespace KurasiAI\Validation;

use ReflectionAttribute;
use ReflectionObject;

/**
 * Memeriksa objek form berdasarkan attribute pada property-nya.
 * Setiap kolom mendapat paling banyak satu pesan: aturan pertama yang gagal.
 */
final class Validator
{
    public function validate(object $form): ValidationErrors
    {
        $messages = [];

        foreach ((new ReflectionObject($form))->getProperties() as $property) {
            // Semua attribute yang menerapkan Rule, termasuk turunannya.
            $attributes = $property->getAttributes(Rule::class, ReflectionAttribute::IS_INSTANCEOF);

            foreach ($attributes as $attribute) {
                $message = $attribute->newInstance()->check($property->getValue($form));

                if ($message !== null) {
                    $messages[$property->getName()] = [$message];
                    break;
                }
            }
        }

        return new ValidationErrors($messages);
    }
}
