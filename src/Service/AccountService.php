<?php

declare(strict_types=1);

namespace Robinson\InterestAccount\Service;

use Robinson\InterestAccount\Repository\TransactionRepositoryInterface;
use Robinson\InterestAccount\Repository\StatsClientInterface;
use Robinson\InterestAccount\Model\Account;
use Robinson\InterestAccount\Repository\AccountRepositoryInterface;
use Robinson\InterestAccount\Model\TransactionType;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;


class AccountService
{
    /**
     * @param AccountRepositoryInterface $accountRepo
     * @param TransactionRepositoryInterface $transactionRepo
     * @param StatsClientInterface $statsClient
     * @param InterestCalculatorInterface $interestCalculator
     * @param AccountIdGeneratorInterface $idGenerator
     */
    public function __construct(
        private AccountRepositoryInterface $accountRepo,
        private TransactionRepositoryInterface $transactionRepo,
        private StatsClientInterface $statsClient,
        private InterestCalculatorInterface $interestCalculator,
        private AccountIdGeneratorInterface $idGenerator
    ) {}

    /**
     * @param string $userId
     * @return string
     */
    public function openAccount(string $userId): string
    {
        if (!Uuid::isValid($userId)) {
            throw new \InvalidArgumentException('Invalid UUID');
        }

        if ($this->accountRepo->getByUserId($userId)) {
            throw new \RuntimeException('User already has an active account');
        }

        $rate = $this->statsClient->getInterestRateForUser($userId);

        $openedAt = new DateTimeImmutable();

        $account = new Account(
            $this->idGenerator->generate(),
            $userId,
            $rate,
            $openedAt
        );

        $this->accountRepo->save($account);
        return $account->getId();
    }

    /**
     * @param string $userId
     * @param string $accountId
     * @param int $amount in pennies
     * @return void
     */
    public function depositForUser(string $userId, string $accountId, int $amount): void
    {
        $account = $this->accountRepo->getById($accountId);
        if (!$account) {
            throw new \RuntimeException('Account not found');
        }
        if ($account->getUserId() !== $userId) {
            throw new \RuntimeException('User not authorized for this account');
        }
        $account->deposit($amount);
        $this->accountRepo->save($account);
        $this->transactionRepo->add($accountId, $amount, TransactionType::DEPOSIT, $account->getBalance());
    }

    /**
     * @param string $accountId
     * @param DateTimeImmutable|null $now
     * @return void
     */
    public function calculateInterest(string $accountId, ?DateTimeImmutable $now = null): void
    {
        $account = $this->accountRepo->getById($accountId);
        if (!$account) {
            throw new \RuntimeException('Account not found');
        }

        $now ??= new DateTimeImmutable();

        // Rely on the calculator: if $now is too soon, interest will be 0.
        $payout = $account->applyInterestForPeriod($this->interestCalculator, $now);

        $this->accountRepo->save($account);

        if ($payout > 0) {
            $this->transactionRepo->add($accountId, $payout, TransactionType::INTEREST, $account->getBalance());
        }
    }

    /**
     * @param string $accountId
     * @return array
     */
    public function getStatement(string $accountId): array
    {
        return $this->transactionRepo->getAllForAccount($accountId);
    }
}
