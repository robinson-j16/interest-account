<?php

declare(strict_types=1);

namespace Robinson\InterestAccount\Tests;

use Robinson\InterestAccount\Infrastructure\InMemoryTransactionRepository;
use PHPUnit\Framework\TestCase;

final class InMemoryTransactionRepositoryTest extends TestCase
{
    public function testAddAndListTransactionsByAccount(): void
    {
        $repo = new InMemoryTransactionRepository();

        $acc1 = 'acc-1';
        $acc2 = 'acc-2';

        $repo->add($acc1, 100, 'DEPOSIT');
        $repo->add($acc1, 2, 'INTEREST');
        $repo->add($acc2, 50, 'DEPOSIT');

        $list1 = $repo->getAllForAccount($acc1);
        $list2 = $repo->getAllForAccount($acc2);
        $list3 = $repo->getAllForAccount('unknown');

        $this->assertCount(2, $list1);
        $this->assertSame(100, $list1[0]['amount']);
        $this->assertSame('DEPOSIT', $list1[0]['type']);
        $this->assertArrayHasKey('createdAt', $list1[0]);
        $this->assertInstanceOf(\DateTimeImmutable::class, $list1[0]['createdAt']);

        $this->assertCount(1, $list2);
        $this->assertSame(50, $list2[0]['amount']);
        $this->assertSame('DEPOSIT', $list2[0]['type']);

        $this->assertSame([], $list3);
    }

    public function testTransactionsPreserveInsertionOrder(): void
    {
        $repo = new InMemoryTransactionRepository();
        $acc = 'acc-x';

        $repo->add($acc, 1, 'A');
        $repo->add($acc, 2, 'B');
        $repo->add($acc, 3, 'C');

        $list = $repo->getAllForAccount($acc);

        $this->assertSame('A', $list[0]['type']);
        $this->assertSame('B', $list[1]['type']);
        $this->assertSame('C', $list[2]['type']);
    }
}
