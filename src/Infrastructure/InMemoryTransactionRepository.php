<?php

declare(strict_types=1);

namespace Robinson\InterestAccount\Infrastructure;

use Robinson\InterestAccount\Repository\TransactionRepositoryInterface;
class InMemoryTransactionRepository implements TransactionRepositoryInterface
{
    /**
     * @var array
     */
    private array $transactions = [];

    /**
     * @param string $accountId
     * @param int $amount
     * @param string $type
     * @param int $balanceAfter
     * @return void
     */
    public function add(string $accountId, int $amount, string $type, ?int $balanceAfter = null): void
    {
        $this->transactions[$accountId] ??= [];
        $txn = [
            'amount' => $amount,
            'type' => $type,
            'createdAt' => new \DateTimeImmutable(),
        ];

        if ($balanceAfter !== null) {
            $txn['balance'] = $balanceAfter;
        }

        $this->transactions[$accountId][] = $txn;
    }


    /**
     * @param string $accountId
     * @return array|\mixed[][]
     */
    public function getAllForAccount(string $accountId): array
    {
        return $this->transactions[$accountId] ?? [];
    }
}
