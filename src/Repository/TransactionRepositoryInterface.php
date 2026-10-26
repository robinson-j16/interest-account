<?php

declare(strict_types=1);

namespace Robinson\InterestAccount\Repository;

interface TransactionRepositoryInterface
{
    /**
     * @param string $accountId
     * @param int $amount
     * @param string $type
     * @param int $balanceAfter
     * @return void
     */
    public function add(string $accountId, int $amount, string $type, int $balanceAfter): void;

    /**
     * @param string $accountId
     * @return array
     */
    public function getAllForAccount(string $accountId): array;
}
