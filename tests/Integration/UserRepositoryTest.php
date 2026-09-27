<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Integration;

use DateTimeImmutable;
use KurasiAI\Domain\Role;
use KurasiAI\Exception\NotFoundException;
use KurasiAI\Exception\ValidationException;
use KurasiAI\Persistence\UserRepository;

class UserRepositoryTest extends DatabaseTestCase
{
    private UserRepository $users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = new UserRepository($this->db);
    }

    public function testAddStoresTheTidiedValues(): void
    {
        $user = $this->users->add('  Rina  ', 'Rina@KurasiAI.test', Role::Writer, 'hash', new DateTimeImmutable());

        $this->assertSame(1, $user->id);
        $this->assertSame('rina@kurasiai.test', $user->email);
        $this->assertEquals($user, $this->users->findByEmail('rina@kurasiai.test'));
        $this->assertEquals($user, $this->users->findByEmail(' RINA@kurasiai.test '));
    }

    public function testInvalidUserLeavesNoRow(): void
    {
        try {
            $this->users->add('Tono', 'tono-di-mana', Role::Writer, 'hash', new DateTimeImmutable());
            $this->fail('Email yang salah seharusnya ditolak.');
        } catch (ValidationException) {
            // memang diharapkan
        }

        $this->assertSame(0, $this->rowCount('users'), 'Baris yang sempat disimpan ikut dibatalkan.');
    }

    public function testGetAllAndNotFound(): void
    {
        $rina = $this->users->add('Rina', 'rina@kurasiai.test', Role::Writer, 'hash', new DateTimeImmutable());
        $budi = $this->users->add('Budi', 'budi@kurasiai.test', Role::Editor, 'hash', new DateTimeImmutable());

        $this->assertEquals($budi, $this->users->get($budi->id));
        $this->assertEquals([$rina, $budi], $this->users->all());
        $this->assertNull($this->users->findByEmail('tidak-ada@kurasiai.test'));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Pengguna #9 tidak ditemukan.');

        $this->users->get(9);
    }

    public function testCredentialsAndPasswordHashUpdate(): void
    {
        $user = $this->users->add('Rina', 'rina@kurasiai.test', Role::Writer, 'hash-lama', new DateTimeImmutable());

        $credentials = $this->users->findCredentials('RINA@kurasiai.test');

        $this->assertNotNull($credentials);
        $this->assertEquals($user, $credentials->user);
        $this->assertSame('hash-lama', $credentials->passwordHash);
        $this->assertNull($this->users->findCredentials('tidak-ada@kurasiai.test'));

        $this->users->updatePasswordHash($user->id, 'hash-baru');

        $this->assertSame('hash-baru', $this->users->findCredentials('rina@kurasiai.test')?->passwordHash);
    }
}
