<?php

declare(strict_types=1);

namespace Robinson\InterestAccount\Repository;

use Robinson\InterestAccount\Model\Account;

interface AccountRepositoryInterface
{
    /**
     * @param Account $account
     * @return void
     */
    public function save(Account $account): void;

    /**
     * @param string $accountId
     * @return Account|null
     */
    public function getById(string $accountId): ?Account;

    /**
     * @param string $userId
     * @return Account|null
     */
    public function getByUserId(string $userId): ?Account;
}
