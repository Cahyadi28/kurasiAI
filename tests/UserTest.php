<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testNormalizesNameAndEmail(): void
    {
        $user = new User(1, '  Rina  ', '  Rina@Contoh.ID ', Role::Writer);

        $this->assertSame('Rina', $user->name);
        $this->assertSame('rina@contoh.id', $user->email);
    }

    public function testRejectsEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new User(1, '   ', 'rina@contoh.id', Role::Writer);
    }

    public function testRejectsInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Format email tidak valid.');

        new User(1, 'Rina', 'rina-di-contoh', Role::Writer);
    }

    public function testOnlyEditorsCanReview(): void
    {
        $this->assertTrue(Role::Editor->canReview());
        $this->assertFalse(Role::Writer->canReview());
    }

    public function testComparesUsersById(): void
    {
        $rina = new User(1, 'Rina', 'rina@contoh.id', Role::Writer);

        $this->assertTrue($rina->isSameAs(new User(1, 'Rina A.', 'rina@contoh.id', Role::Writer)));
        $this->assertFalse($rina->isSameAs(new User(2, 'Rina', 'rina2@contoh.id', Role::Writer)));
    }
}
