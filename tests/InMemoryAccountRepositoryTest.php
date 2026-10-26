<?php

declare(strict_types=1);

namespace Robinson\InterestAccount\Tests;

use Robinson\InterestAccount\Infrastructure\InMemoryAccountRepository;
use Robinson\InterestAccount\Model\Account;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class InMemoryAccountRepositoryTest extends TestCase
{
    public function testSaveAndGetById(): void
    {
        $repo = new InMemoryAccountRepository();
        $id = Uuid::uuid4()->toString();
        $userId = Uuid::uuid4()->toString();
        $account = new Account($id, $userId, 1.02, new \DateTimeImmutable());

        $repo->save($account);
        $fetched = $repo->get($id);

        $this->assertNotNull($fetched);
        $this->assertSame($id, $fetched->getId());
        $this->assertSame($userId, $fetched->getUserId());
        $this->assertSame(1.02, $fetched->getInterestRate());
    }

    public function testGetByIdReturnsNullWhenNotFound(): void
    {
        $repo = new InMemoryAccountRepository();
        $this->assertNull($repo->get('non-existent'));
    }

    public function testGetByUserIdReturnsCorrectAccount(): void
    {
        $repo = new InMemoryAccountRepository();

        $userA = Uuid::uuid4()->toString();
        $userB = Uuid::uuid4()->toString();

        $accA = new Account(Uuid::uuid4()->toString(), $userA, 0.93, new \DateTimeImmutable());
        $accB = new Account(Uuid::uuid4()->toString(), $userB, 1.02, new \DateTimeImmutable());

        $repo->save($accA);
        $repo->save($accB);

        $foundA = $repo->getByUserId($userA);
        $foundB = $repo->getByUserId($userB);

        $this->assertNotNull($foundA);
        $this->assertNotNull($foundB);
        $this->assertSame($userA, $foundA->getUserId());
        $this->assertSame($userB, $foundB->getUserId());
    }

    public function testGetByUserIdReturnsNullWhenNotFound(): void
    {
        $repo = new InMemoryAccountRepository();
        $this->assertNull($repo->getByUserId(Uuid::uuid4()->toString()));
    }
}
