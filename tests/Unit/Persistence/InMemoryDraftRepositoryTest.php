<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Persistence;

use KurasiAI\Persistence\DraftRepository;
use KurasiAI\Persistence\InMemoryDraftRepository;
use KurasiAI\Tests\Contract\DraftRepositoryContract;

class InMemoryDraftRepositoryTest extends DraftRepositoryContract
{
    protected function createRepository(): DraftRepository
    {
        return new InMemoryDraftRepository();
    }
}
