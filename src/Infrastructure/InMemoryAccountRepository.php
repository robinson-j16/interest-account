<?php

declare(strict_types=1);

namespace Robinson\InterestAccount\Infrastructure;

use Robinson\InterestAccount\Model\Account;
use Robinson\InterestAccount\Repository\AccountRepositoryInterface;

class InMemoryAccountRepository implements AccountRepositoryInterface
{
    /**
     * @var array
     */
    private array $accounts = [];

    /**
     * @param Account $account
     * @return void
     */
    public function save(Account $account): void
    {
        $this->accounts[$account->getId()] = $account;
    }

    /**
     * @param string $accountId
     * @return Account|null
     */
    public function getById(string $accountId): ?Account
    {
        return $this->accounts[$accountId] ?? null;
    }

    /**
     * @param string $userId
     * @return Account|null
     */
    public function getByUserId(string $userId): ?Account
    {
        foreach ($this->accounts as $account) {
            if ($account->getUserId() === $userId) {
                return $account;
            }
        }
        return null;
    }

    /**
     * @param string $accountId
     * @return Account|null
     */
    public function get(string $accountId): ?Account
    {
        return $this->accounts[$accountId] ?? null;
    }
}
