<?php

declare(strict_types=1);

namespace Robinson\InterestAccount\Model;

use Robinson\InterestAccount\Service\InterestCalculatorInterface;
use DateTimeImmutable;

class Account
{
    /**
     * @param string $id
     * @param string $userId
     * @param float $interestRate
     * @param DateTimeImmutable $openedAt
     * @param int $balance
     * @param int $accumulatedInterest
     * @param DateTimeImmutable|null $lastInterestRunAt
     */
    public function __construct(
        private string $id,
        private string $userId,
        private float $interestRate,
        private \DateTimeImmutable $openedAt = new \DateTimeImmutable(),
        private int $balance = 0,
        private int $accumulatedInterest = 0,
        private ?\DateTimeImmutable $lastInterestRunAt = null
    ) {}

    /**
     * @return string
     */
    public function getId(): string {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUserId(): string {
        return $this->userId;
    }

    /**
     * @return float
     */
    public function getInterestRate(): float {
        return $this->interestRate;
    }

    /**
     * @return int
     */
    public function getBalance(): int {
        return $this->balance;
    }

    /**
     * @param int $amount
     * @return void
     */
    public function deposit(int $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Deposit amount must be positive');
        }
        $this->balance += $amount;
    }

    /**
     * @param int $amount
     * @return void
     */
    public function addAccumulatedInterest(int $amount): void
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Accumulated interest must be non-negative');
        }
        $this->accumulatedInterest += $amount;
    }

    /**
     * @return int
     */
    public function extractPayout(): int
    {
        // If less than 1p, no payout.
        if ($this->accumulatedInterest < 1_000_000) {
            return 0;
        }

        // Divide by 1,000,000 to get full pennies.
        $payout = intdiv($this->accumulatedInterest, 1_000_000);

        // Remove the full pennies from accumulated interest.
        $this->accumulatedInterest -= $payout * 1_000_000;
        return $payout;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getLastInterestRunAt(): ?\DateTimeImmutable
    {
        return $this->lastInterestRunAt;
    }

    /**
     * @param DateTimeImmutable $runAt
     * @return void
     */
    public function setLastInterestRunAt(\DateTimeImmutable $runAt): void
    {
        $this->lastInterestRunAt = $runAt;
    }

    /**
     * @param InterestCalculatorInterface $calculator
     * @param DateTimeImmutable $now
     * @return int
     */
    public function applyInterestForPeriod(InterestCalculatorInterface $calculator, DateTimeImmutable $now): int
    {
        // Get the last time this has run, or the time it was opened if it has not run yet.
        $lastRunAt = $this->lastInterestRunAt ?? $this->openedAt;

        $rawInterest = $calculator->calculateInterest(
            $this->balance,
            $this->interestRate,
            $lastRunAt,
            $now
        );

        $this->addAccumulatedInterest($rawInterest);
        $payout = $this->extractPayout();

        if ($payout > 0) {
            $this->deposit($payout);
        }

        // If this was a DB we would need to depend on the DB transaction to ensure user was paid.
        $this->setLastInterestRunAt($now);

        return $payout;
    }
}
