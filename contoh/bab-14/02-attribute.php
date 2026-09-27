<?php

declare(strict_types=1);

// 1. Membuat attribute: class biasa yang diberi #[Attribute].
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Label
{
    public function __construct(public readonly string $text)
    {
    }
}

// 2. Memasang attribute pada property. Attribute tidak melakukan apa-apa dengan sendirinya;
//    ia hanya keterangan yang menempel pada kode.
final class ContactForm
{
    #[Label('Nama lengkap')]
    public string $name = '';

    #[Label('Alamat email')]
    public string $email = '';

    public string $withoutLabel = '';
}

// 3. Membaca attribute dengan Reflection, lalu membuat objeknya dengan newInstance().
foreach ((new ReflectionClass(ContactForm::class))->getProperties() as $property) {
    $attributes = $property->getAttributes(Label::class);
    $label = $attributes === [] ? '(tanpa label)' : $attributes[0]->newInstance()->text;

    echo str_pad($property->getName(), 14) . '-> ' . $label . PHP_EOL;
}
